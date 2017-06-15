<?php

namespace Drupal\wmcontroller\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

abstract class AbstractPresenter implements PresenterInterface
{
    /** @var EntityInterface */
    protected $entity;

    protected function methodNames($methodName)
    {
        return [
            $methodName,
            'is' . ucfirst($methodName),
            'get' . ucfirst($methodName),
        ];
    }

    public function __isset($prop)
    {
        return isset($this->entity->{$prop});
    }

    public function __get($prop)
    {
        return $this->entity->{$prop};
    }

    public function __call($method, array $args)
    {
        foreach ($this->methodNames($method) as $method) {
            $call = [$this->entity, $method];
            if (is_callable($call)) {
                return call_user_func($call);
            }
        }

        throw new \BadMethodCallException();
    }

    public function setEntity(EntityInterface $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function uuid()
    {
        return $this->entity->uuid();
    }

    public function id()
    {
        return $this->entity->id();
    }

    public function language()
    {
        return $this->entity->language();
    }

    public function isNew()
    {
        return $this->entity->isNew();
    }

    public function enforceIsNew($value = true)
    {
        return $this->entity->enforceIsNew($value);
    }

    public function getEntityTypeId()
    {
        return $this->entity->getEntityTypeId();
    }

    public function bundle()
    {
        return $this->entity->bundle();
    }

    public function label()
    {
        return $this->entity->label();
    }

    public function urlInfo($rel = 'canonical', array $options = [])
    {
        return $this->entity->urlInfo($rel, $options);
    }

    public function toUrl($rel = 'canonical', array $options = [])
    {
        return $this->entity->toUrl($rel, $options);
    }

    public function url($rel = 'canonical', $options = [])
    {
        return $this->entity->url($rel, $options);
    }

    public function link($text = null, $rel = 'canonical', array $options = [])
    {
        return $this->entity->link($text, $rel, $options);
    }

    public function toLink($text = null, $rel = 'canonical', array $options = [])
    {
        return $this->entity->toLink($text, $rel, $options);
    }

    public function hasLinkTemplate($key)
    {
        return $this->entity->hasLinkTemplate($key);
    }

    public function uriRelationships()
    {
        return $this->entity->uriRelationships();
    }

    public static function load($id)
    {
        return $this->entity->load($id);
    }

    public static function loadMultiple(array $ids = null)
    {
        return $this->entity->loadMultiple($ids);
    }

    public static function create(array $values = [])
    {
        return $this->entity->create($values);
    }

    public function save()
    {
        return $this->entity->save();
    }

    public function delete()
    {
        return $this->entity->delete();
    }

    public function preSave(EntityStorageInterface $storage)
    {
        return $this->entity->preSave($storage);
    }

    public function postSave(EntityStorageInterface $storage, $update = true)
    {
        return $this->entity->postSave($storage, $update);
    }

    public static function preCreate(EntityStorageInterface $storage, array &$values)
    {
        return $this->entity->preCreate($storage, $values);
    }

    public function postCreate(EntityStorageInterface $storage)
    {
        return $this->entity->postCreate($storage);
    }

    public static function preDelete(EntityStorageInterface $storage, array $entities)
    {
        return $this->entity->preDelete($storage, $entities);
    }

    public static function postDelete(EntityStorageInterface $storage, array $entities)
    {
        return $this->entity->postDelete($storage, $entities);
    }

    public static function postLoad(EntityStorageInterface $storage, array &$entities)
    {
        return $this->entity->postLoad($storage, $entities);
    }

    public function createDuplicate()
    {
        return $this->entity->createDuplicate();
    }

    public function getEntityType()
    {
        return $this->entity->getEntityType();
    }

    public function referencedEntities()
    {
        return $this->entity->referencedEntities();
    }

    public function getOriginalId()
    {
        return $this->entity->getOriginalId();
    }

    public function getCacheTagsToInvalidate()
    {
        return $this->entity->getCacheTagsToInvalidate();
    }

    public function setOriginalId($id)
    {
        return $this->entity->setOriginalId($id);
    }

    public function toArray()
    {
        return $this->entity->toArray();
    }

    public function getTypedData()
    {
        return $this->entity->getTypedData();
    }

    public function getConfigDependencyKey()
    {
        return $this->entity->getConfigDependencyKey();
    }

    public function getConfigDependencyName()
    {
        return $this->entity->getConfigDependencyName();
    }

    public function getConfigTarget()
    {
        return $this->entity->getConfigTarget();
    }

    public function access($operation, AccountInterface $account = null, $return_as_object = false)
    {
        return $this->entity->access($operation, $account, $return_as_object);
    }

    public function getCacheContexts()
    {
        return $this->entity->getCacheContexts();
    }

    public function getCacheTags()
    {
        return $this->entity->getCacheTags();
    }

    public function getCacheMaxAge()
    {
        return $this->entity->getCacheMaxAge();
    }

    public function addCacheContexts(array $cache_contexts)
    {
        return $this->entity->addCacheContexts($cache_contexts);
    }

    public function addCacheTags(array $cache_tags)
    {
        return $this->entity->addCacheTags($cache_tags);
    }

    public function mergeCacheMaxAge($max_age)
    {
        return $this->entity->mergeCacheMaxAge($max_age);
    }

    public function addCacheableDependency($other_object)
    {
        return $this->entity->addCacheableDependency($other_object);
    }
}
