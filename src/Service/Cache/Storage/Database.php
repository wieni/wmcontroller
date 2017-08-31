<?php

namespace Drupal\wmcontroller\Service\Cache\Storage;

use Drupal\wmcontroller\Entity\Cache;
use Drupal\wmcontroller\Exception\NoSuchCacheEntryException;
use Drupal\Core\Database\Connection;
use Drupal\wmcontroller\Service\Cache\Purger\PurgerInterface;

class Database implements StorageInterface, PurgerInterface
{
    const TX = 'wmcontroller_cache';
    const TABLE_ENTRIES = 'wmcontroller_cache';
    const TABLE_TAGS = 'wmcontroller_cache_tags';

    protected $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getExpired($amount)
    {
        $stmt = $this->db->select(self::TABLE_ENTRIES, 'c')
            ->fields('c', ['id', 'uri', 'method', 'expiry'])
            ->condition('expiry', time(), '<')
            ->range(0, (int)$amount)
            ->execute();

        $items = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $items[] = $this->assocRowToEntry($row);
        }

        return $items;
    }

    public function getByTags(array $tags)
    {
        if (!$tags) {
            return [];
        }

        $q = $this->db->select(self::TABLE_ENTRIES, 'c')
            ->fields('c', ['id', 'uri', 'method', 'expiry'])
            ->condition('c.expiry', time(), '>=');

        $q->innerJoin(self::TABLE_TAGS, 't', 't.id = c.id');

        $stmt = $q->condition('t.tag', $tags, 'IN')
            ->execute();

        $items = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $items[] = $this->assocRowToEntry($row);
        }

        return $items;
    }

    public function get($uri, $method = 'GET')
    {
        $method = strtoupper($method);
        $id = $this->cacheId($uri, $method);

        $raw = $this->db->select(self::TABLE_ENTRIES, 'c')
            ->fields('c', ['uri', 'method',  'headers', 'content', 'expiry'])
            ->condition('c.id', $id)
            ->condition('c.expiry', time(), '>=')
            ->execute()->fetch(\PDO::FETCH_ASSOC);

        if (!$raw) {
            throw new NoSuchCacheEntryException($method, $uri);
        }

        return $this->assocRowToEntry($raw);
    }

    public function set(Cache $item, array $tags)
    {
        $id = $this->getCacheId($item);
        $tx = $this->db->startTransaction(self::TX);

        try {
            // Add cache entry
            $this->db->upsert(self::TABLE_ENTRIES)
                ->key($id)
                ->fields(
                    [
                        'id' => $id,
                        'method' => $item->getMethod(),
                        'uri' => $item->getUri(),
                        'headers' => serialize($item->getHeaders()),
                        'content' => $item->getBody(),
                        'expiry' => $item->getExpiry(),
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

    public function expire(array $items)
    {
        $this->expireIds(
            array_map([$this, 'getCacheId'], $items)
        );
    }

    public function remove(array $items)
    {
        $ids = array_map(
            function(Cache $item) {
                return $this->cacheId($item->getUri(), $item->getMethod());
            },
            $items
        );
        $this->delete($ids);
    }

    public function flush()
    {
        // Keep it transactional or risk a race with truncate?
        $ids = $this->db->select(self::TABLE_ENTRIES, 'c')
            ->fields('c', ['id'])
            ->execute()->fetchCol();

        while (!empty($ids)) {
            $this->delete(array_splice($ids, 0, 50));
        }
    }

    public function purge(array $items)
    {
        $this->remove($items);
        return true;
    }

    private function delete(array $ids)
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

    private function assocRowToEntry(array $row)
    {
        return new Cache(
            $row['uri'],
            $row['method'],
            empty($row['content']) ? null : $row['content'],
            empty($row['headers']) ? [] : unserialize($row['headers']),
            $row['expiry']
        );
    }

    private function getCacheId(Cache $item)
    {
        return $this->cacheId($item->getUri(), $item->getMethod());
    }

    private function cacheId($uri, $method)
    {
        return sha1($method . ':' . $uri);
    }

    private function expireIds(array $ids)
    {
        if (empty($ids)) {
            return;
        }

        $tx = $this->db->startTransaction(self::TX);

        try {
            $this->db->update(static::TABLE_ENTRIES)
                ->fields([
                    'expire' => time()
                ])
                ->condition('id', $ids, 'IN')
                ->execute();
        } catch (\Exception $e) {
            $tx->rollback();
            // TODO add the fact that we rollbacked to the exception.
            throw $e;
        }

        unset($tx); // commit, btw this is marginaal AS FUCK.
    }
}
