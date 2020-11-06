<?php

namespace Drupal\wmcontroller\Entity;

abstract class AbstractPresenter implements PresenterInterface
{
    protected $entity;

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
                return call_user_func_array($call, $args);
            }
        }

        throw new \BadMethodCallException();
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    protected function methodNames($methodName)
    {
        $uc = ucfirst($methodName);
        return [$methodName, 'is' . $uc, 'get' . $uc, 'has' . $uc];
    }
}
