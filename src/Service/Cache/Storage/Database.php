<?php

namespace Drupal\wmcontroller\Service\Cache\Storage;

use Drupal\wmcontroller\Entity\Cache;
use Drupal\wmcontroller\Exception\NoSuchCacheEntryException;
use Drupal\Core\Database\Connection;
use Drupal\wmcontroller\Service\Cache\CacheSerializerInterface;

class Database implements StorageInterface
{
    const TX = 'wmcontroller_cache_storage';
    const TABLE_ENTRIES = 'wmcontroller_cache';
    const TABLE_TAGS = 'wmcontroller_cache_tags';

    /** @var \Drupal\Core\Database\Connection */
    protected $db;
    /** @var \Drupal\wmcontroller\Service\Cache\CacheSerializerInterface */
    protected $serializer;

    public function __construct(
        Connection $db,
        CacheSerializerInterface $serializer
    ) {
        $this->db = $db;
        $this->serializer = $serializer;
    }

    public function load($id, $includeBody = true)
    {
        $item = $this->loadMultiple([$id], $includeBody);
        $item = reset($item);
        if (!$item) {
            throw new NoSuchCacheEntryException($id);
        }

        return $item;
    }

    public function loadMultiple(array $ids, $includeBody = true)
    {
        $fields = ['id', 'uri', 'method', 'expiry'];
        if ($includeBody) {
            $fields[] = 'content';
            $fields[] = 'headers';
        }

        $stmt = $this->db->select(self::TABLE_ENTRIES, 'c')
            ->fields('c', $fields)
            ->condition('c.id', $ids, 'IN')
            ->condition('c.expiry', time(), '>=')
            ->execute();

        $items = array_fill_keys($ids, null);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $items[$row['id']] = $this->assocRowToEntry($row);
        }

        return array_filter($items);
    }

    public function set(Cache $item, array $tags)
    {
        $id = $item->getId();
        $tx = $this->db->startTransaction(self::TX);
        $tags = array_unique($tags);

        try {
            // Add cache entry
            $this->db->upsert(self::TABLE_ENTRIES)
                ->key($id)
                ->fields($this->serializer->normalize($item))
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

    public function getExpired($amount)
    {
        $q = $this->db->select(self::TABLE_ENTRIES, 'c')
            ->fields('c', ['id']);
        $q->condition('c.expiry', time(), '<');
        $q->range(0, (int) $amount);

        return $q->execute()->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getByTags(array $tags)
    {
        if (!$tags) {
            return [];
        }

        $q = $this->db->select(self::TABLE_ENTRIES, 'c')
            ->fields('c', ['id']);
        $q->condition('c.expiry', time(), '>=');
        $q->innerJoin(self::TABLE_TAGS, 't', 't.id = c.id');
        $q->condition('t.tag', $tags, 'IN');

        return $q->execute()->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function remove(array $ids)
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

    public function flush()
    {
        // Keep it transactional or risk a race with truncate?
        $ids = $this->db->select(self::TABLE_ENTRIES, 'c')
            ->fields('c', ['id'])
            ->execute()->fetchCol();

        while (!empty($ids)) {
            $this->remove(array_splice($ids, 0, 50));
        }
    }

    protected function assocRowToEntry(array $row)
    {
        return $this->serializer->denormalize($row);
    }
}
