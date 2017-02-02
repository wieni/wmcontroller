<?php

namespace Drupal\wmcontroller\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure file system settings for this site.
 */
class WmControllerSettings extends ConfigFormBase
{

    /**  @var ModuleHandlerInterface */
    protected $moduleHandler;

    /** @var  ThemeHandlerInterface */
    protected $themeHandler;

    /**
     * Construct.
     *
     * {@inheritDoc}
     */
    public function __construct(
        ConfigFactoryInterface $config_factory,
        ModuleHandlerInterface $moduleHandler,
        ThemeHandlerInterface $themeHandler
    ) {
        parent::__construct($config_factory);
        $this->moduleHandler = $moduleHandler;
        $this->themeHandler = $themeHandler;
    }

    /**
     * Create.
     *
     * {@inheritDoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('config.factory'),
            $container->get('module_handler'),
            $container->get('theme_handler')
        );
    }

    /**
     * Formid.
     *
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'wmcontroller_settings_form';
    }

    /**
     * Config names.
     *
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['wmcontroller.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('wmcontroller.settings');

        $form['mapping'] = array(
            '#type' => 'details',
            '#title' => $this->t('Title'),
            '#collapsible' => false,
            '#open' => true,
        );

        $form['mapping']['path'] = array(
            '#type' => 'textfield',
            '#required' => true,
            '#title' => $this->t('Path'),
            '#default_value' => $config->get('path') ? $config->get('path') : '/templates',
            '#description' => $this->t('The path in the module/theme where your twig files are'),
        );

        $form['mapping']['module'] = array(
            '#type' => 'select',
            '#required' => true,
            '#title' => $this->t('Module'),
            '#options' => $this->getActiveModules(),
            '#default_value' => $config->get('module'),
            '#description' => $this->t('The module where bundle-specific controllers live'),
        );

        $form['mapping']['theme'] = array(
            '#type' => 'select',
            '#required' => false,
            '#title' => $this->t('Theme'),
            '#options' => $this->getActiveThemes(),
            '#default_value' => $config->get('theme'),
            '#description' => $this->t('The theme where your custom twig templates are. If this remains empty then WmController will fallback to the module.'),
            '#empty_value' => '',
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('wmcontroller.settings');

        $form_state->cleanValues();

        foreach ($form_state->getValues() as $key => $value) {
            $config->set($key, $value);
        }
        $config->save();

        parent::submitForm($form, $form_state);
    }

    /**
     * Get a list of all active modules
     * @return array
     */
    protected function getActiveModules()
    {
        $modules = [];
        foreach ($this->moduleHandler->getModuleList() as $name => $extension) {
            $modules[$name] = $extension->getName();
        }
        return $modules;
    }

    /**
     * Get a list of all active themes.
     * @return array
     */
    protected function getActiveThemes()
    {
        $themes = [];
        foreach ($this->themeHandler->listInfo() as $name => $extension) {
            $themes[$name] = $extension->getName();
        }
        return $themes;
    }
}

