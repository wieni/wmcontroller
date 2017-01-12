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
        if (!$module = $this->getModule()) {
            return [];
        }

        return $this->getThemesFromModule($module);
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
     * Locate and create theme arrays in a module
     *
     * @param $module
     * @param string $directory
     * @return array
     */
    private function getThemesFromModule($module, $directory = '/templates')
    {
        $themes = [];
        $dir = drupal_get_path('module', $module) . $directory;

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