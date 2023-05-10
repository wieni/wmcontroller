<?php

namespace Drupal\wmcontroller\Twig\Extension;

use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontroller\Entity\HasPresenterInterface;
use Drupal\wmcontroller\Service\Cache\Dispatcher;
use Drupal\wmcontroller\Service\PresenterFactoryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


class PresenterExtension extends AbstractExtension
{
    /** @var PresenterFactoryInterface */
    protected $presenterFactory;
    /** @var Dispatcher */
    protected $dispatcher;

    public function __construct(
        PresenterFactoryInterface $presenterFactory,
        Dispatcher $dispatcher
    ) {
        $this->presenterFactory = $presenterFactory;
        $this->dispatcher = $dispatcher;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('presenter', [$this, 'getPresenter']),
            new TwigFilter('p', [$this, 'getPresenter']),
        ];
    }

    public function getPresenter($entities)
    {
        if (!is_array($entities)) {
            return $this->fetchPresenter($entities);
        }

        $presenters = [];
        foreach ($entities as $key => $entity) {
            $presenters[$key] = $this->fetchPresenter($entity);
        }

        return $presenters;
    }

    protected function fetchPresenter($entity)
    {
        if ($entity instanceof EntityInterface) {
            $this->dispatcher->dispatchPresented($entity);
        }

        if ($entity instanceof HasPresenterInterface) {
            return $this->presenterFactory->getPresenterForEntity($entity);
        }

        return $entity;
    }
}
