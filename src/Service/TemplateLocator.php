<?php

namespace Drupal\wmcontroller\Service;

use Drupal\Core\Config\ConfigBase;
use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use RecursiveDirectoryIterator;

class TemplateLocator
{

    /** @var ConfigBase */
    protected $config;

    protected $twigExtension = '.html.twig';

    public function __construct(ConfigBase $config)
    {
        $this->config = $config;
    }

    /**
     * Get all custom themes
     *
     * @return array
     */
    public function getThemes()
    {
        if ($location = $this->getTheme()) {
            return $this->getThemeFiles('theme', $location);
        }
        if (!$location = $this->getModule()) {
            return $this->getThemeFiles('module', $location);
        }

        return [];
    }
    
    /**
     * Get the configured path
     * @see /admin/config/services/wmcontroller
     *
     * @return string
     */
    private function getPath()
    {
        return $this->config->get('path');
    }

    /**
     * Get the configured module
     * @see /admin/config/services/wmcontroller
     *
     * @return string
     */
    private function getModule()
    {
        return $this->config->get('module');
    }
    
    /**
     * Get the configured theme, if any
     * @see /admin/config/services/wmcontroller
     *
     * @return string
     */
    private function getTheme()
    {
        return $this->config->get('theme');
    }

    /**
     * Locate and create theme arrays in a module
     *
     * @param $type
     *   module or theme
     * @param $location
     *   directory in that module or theme
     * @return array
     */
    private function getThemeFiles($type, $location)
    {
        
        if (!$directory = $this->getPath()) {
            $directory = '/templates';
        }
        
        $themes = [];
        $dir = drupal_get_path($type, $location) . $directory;

        if (!file_exists($dir)) {
            return $themes;
        }

        $files = $this->findTwigFiles($dir);

        foreach ($files as $file) {
            $fileName = $this->stripOutTemplatePathAndExtension($dir, $file);
            // Transform the filename to a template name
            // node/article/index.html.twig => node.article.index
            $templateName = $this->createTemplateNameFromFilename($fileName);
            $themes[$templateName] = array(
                'variables' => array(
                    '_data' => array(),
                ),
                'path' => $dir,
                'template' => $fileName,
                'preprocess functions' => ['template_preprocess', 'wmcontroller_theme_set_variables']
            );
        }

        return $themes;
    }

    /**
     * Find all twig files recursively in a directory
     *
     * @param string $directory
     * @return string[]
     */
    private function findTwigFiles($directory)
    {
        $fileIterator = $this->createRecursiveFileIterator($directory);

        $twigFileRegex = '#.*' . preg_quote($this->twigExtension) . '$#';
        $matches = new RegexIterator(
            $fileIterator,
            $twigFileRegex,
            RecursiveRegexIterator::GET_MATCH
        );

        // Weed out non-matches
        $files = [];
        foreach ($matches as $match) {
            if (empty($match[0])) {
                continue;
            }
            $files[] = $match[0];
        }

        return $files;
    }

    /**
     * @param $dir
     * @return RecursiveIteratorIterator
     */
    private function createRecursiveFileIterator($dir)
    {
        $dirIterator = new RecursiveDirectoryIterator(
            $dir,
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
        );
        return new RecursiveIteratorIterator($dirIterator);
    }

    private function stripOutTemplatePathAndExtension($templatePath, $file)
    {
        // Strip out the module path
        $file = str_replace($templatePath . '/', '', $file);
        // Strip out extension
        return $file = preg_replace('#' . preg_quote($this->twigExtension) . '$#', '', $file);
    }

    /**
     * Transform a filename to a template name
     * node/article/index.html.twig => node.article.index
     *
     * @param $fileName
     * @return mixed
     */
    private function createTemplateNameFromFilename($fileName)
    {
        return preg_replace('/\/|\\\/', '.', $fileName);
    }

}