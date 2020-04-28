<?php

namespace Drupal\wmcontroller\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\wmcontroller\ViewBuilder\ViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class ControllerBase implements ContainerInjectionInterface
{
    use StringTranslationTrait;

    /** @var ViewBuilder */
    protected $viewBuilder;
    /** @var string */
    protected $templateDir = '';

    public static function create(ContainerInterface $container)
    {
        $instance = new static;
        $instance->viewBuilder = $container->get('wmcontroller.viewbuilder');

        return $instance;
    }

    /**
     * Return a new view from the application.
     *
     * @param  string      $template
     * @param  array       $data
     * @return ViewBuilder
     */
    protected function view($template = '', $data = [])
    {
        return $this->viewBuilder
            ->setTemplateDir($this->templateDir)
            ->setData($data)
            ->setTemplate($template);
    }

    /**
     * Returns a redirect response object for the specified route.
     *
     * @param string $routeName
     *   The name of the route to which to redirect.
     * @param array $routeParameters
     *   (optional) Parameters for the route.
     * @param array $options
     *   (optional) An associative array of additional options.
     * @param int $status
     *   (optional) The HTTP redirect status code for the redirect. The default is
     *   302 Found.
     *
     * @return RedirectResponse
     *   A redirect response object that may be returned by the controller.
     */
    protected function redirect($routeName, array $routeParameters = [], array $options = [], $status = 302)
    {
        $url = Url::fromRoute($routeName, $routeParameters, $options)
            ->setAbsolute(true)
            ->toString();

        return new RedirectResponse($url, $status);
    }
}
