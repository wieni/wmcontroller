<?php

namespace Drupal\wmcontroller\Service\Cache\Storage;

use Drupal\wmcontroller\Entity\Cache;
use Drupal\wmcontroller\Exception\NoSuchCacheEntryException;
use Drupal\Core\Database\Connection;

class Database implements StorageInterface
{
    const TX = 'wmcontroller_cache';
    const TABLE_ENTRIES = 'wmcontroller_cache';
    const TABLE_TAGS = 'wmcontroller_cache_tags';

    protected $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getByTag($tag)
    {
        $q = $this->db->select(self::TABLE_ENTRIES, 'c')
            ->fields(
                'c',
                ['id', 'uri', 'method', 'expiry']
            )
            ->condition('c.expiry', time(), '>=');

        $q->innerJoin(self::TABLE_TAGS, 't', 't.id = c.id');

        $stmt = $q->condition('t.tag', $tag)
            ->execute();

        $items = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $items[] = $this->assocRowToEntry($row);
        }

        return $items;
    }

    /**
     * @return Cache
     *
     * @throws NoSuchCacheEntryException;
     */
    public function get($uri, $method = 'GET')
    {
        $method = strtoupper($method);
        $id = $this->cacheId($uri, $method);

        $raw = $this->db->select(self::TABLE_ENTRIES, 'c')
            ->fields('c', ['uri', 'method',  'headers', 'content', 'expiry'])
            ->condition('c.expiry', time(), '>=')
            ->condition('c.id', $id)
            ->execute()->fetch(\PDO::FETCH_ASSOC);

        if (!$raw) {
            throw new NoSuchCacheEntryException($method, $uri);
        }

        return $this->assocRowToEntry($raw);
    }

    public function set(Cache $cache, array $tags)
    {
        $id = $this->cacheId($cache->getUri(), $cache->getMethod());
        $tx = $this->db->startTransaction(self::TX);

        try {
            // Add cache entry
            $this->db->upsert(self::TABLE_ENTRIES)
                ->key($id)
                ->fields(
                    [
                        'id' => $id,
                        'method' => $cache->getMethod(),
                        'uri' => $cache->getUri(),
                        'headers' => serialize($cache->getHeaders()),
                        'content' => $cache->getBody(),
                        'expiry' => $cache->getExpiry(),
                    ]
                )
                ->execute();

            // Delete old tags
            $this->db->delete(self::TABLE_TAGS)
                ->condition('id', $id)
                ->execute();

            // Add new tags
            $insert = $this->db->insert(self::TABLE_TAGS)
                ->fields(['id', 'tag']);

            foreach ($tags as $tag) {
                $insert->values([$id, $tag]);
            }

            $insert->execute();
        } catch (\Exception $e) {
            $tx->rollback();
            // TODO add the fact that we rollbacked to the exception.
            throw $e;
        }

        unset($tx); // commit, btw this is marginaal AS FUCK.
    }

    public function purge($amount)
    {
        $stmt = $this->db->select(self::TABLE_ENTRIES, 'c')
            ->fields('c', ['id', 'uri', 'method', 'expiry'])
            ->condition('expiry', time(), '<')
            ->range(0, (int) $amount)
            ->execute();

        $items = [];
        $ids = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $items[] = $this->assocRowToEntry($row);
            $ids[] = $row['id'];
        }

        $this->delete($ids);

        return $items;
    }

    public function purgeByTag($tag)
    {
        $q = $this->db->select(self::TABLE_ENTRIES, 'c')
            ->fields('c', ['id', 'uri', 'method', 'expiry']);

        $q->innerJoin(self::TABLE_TAGS, 't', 't.id = c.id');

        $stmt = $q->condition('t.tag', $tag)
            ->groupBy('id')
            ->execute();

        $items = [];
        $ids = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $items[] = $this->assocRowToEntry($row);
            $ids[] = $row['id'];
        }

        $this->delete($ids);

        return $items;
    }

    protected function delete(array $ids)
    {
        if (empty($ids)) {
            return;
        }

        $tx = $this->db->startTransaction(self::TX);

        try {
            $this->db->delete(self::TABLE_ENTRIES)
                ->condition('id', $ids, 'IN')
                ->execute();

            $this->db->delete(self::TABLE_TAGS)
                ->condition('id', $ids, 'IN')
                ->execute();
        } catch (\Exception $e) {
            $tx->rollback();
            // TODO add the fact that we rollbacked to the exception.
            throw $e;
        }

        unset($tx); // commit, btw this is marginaal AS FUCK.
    }

    protected function assocRowToEntry(array $row)
    {
        return new Cache(
            $row['uri'],
            $row['method'],
            empty($row['content']) ? null : $row['content'],
            empty($row['headers']) ? [] : unserialize($row['headers']),
            $row['expiry']
        );
    }

    protected function cacheId($uri, $method)
    {
        return sha1($method . ':' . $uri);
    }
}

