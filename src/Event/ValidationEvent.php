<?php

namespace Drupal\wmcontroller\Event;

use Drupal\Core\Access\AccessResult;
use Drupal\wmcontroller\Service\Cache\Validation\ValidationResult;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidationEvent extends Event
{
    /** @var \Symfony\Component\HttpFoundation\Request */
    protected $request;
    /** @var \Symfony\Component\HttpFoundation\Response */
    protected $response;
    protected $resultClass;

    protected $result;
    protected $results = [];

    public function __construct(Request $request, ?Response $response = null, $resultClass = null)
    {
        $this->resultClass = $resultClass ?: ValidationResult::class;
        $this->request = $request;
        $this->response = $response;
    }

    /** @return \Symfony\Component\HttpFoundation\Request */
    public function getRequest()
    {
        return $this->request;
    }

    /** @return \Symfony\Component\HttpFoundation\Response */
    public function getResponse()
    {
        return $this->response;
    }

    public function add(AccessResult $result)
    {
        $this->result = null;
        $this->results[] = $result;
    }

    /**
     * Check whether or not this request or response should be cached.
     *
     * @return \Drupal\wmcontroller\Service\Cache\Validation\ValidationResult
     */
    public function result()
    {
        if (isset($this->result)) {
            return $this->result;
        }

        return $this->result = new $this->resultClass(
            $this->processAccessResults($this->results)
        );
    }

    protected function processAccessResults(array $access)
    {
        // No results means no opinion.
        if (empty($access)) {
            return AccessResult::neutral();
        }

        /** @var \Drupal\Core\Access\AccessResultInterface $result */
        $result = array_shift($access);
        foreach ($access as $other) {
            $result = $result->orIf($other);
        }
        return $result;
    }
}
