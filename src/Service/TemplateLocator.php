<?php

namespace Drupal\wmcontroller\Service;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

class TemplateLocator implements TemplateLocatorInterface
{
    public const TWIG_EXT = '.html.twig';

    /** @var array */
    protected $settings;

    public function __construct(array $settings)
    {
        if (empty($settings['module'])) {
            throw new \Exception(
                'wmcontroller requires a non-empty module entry in wmcontroller.settings'
            );
        }

        if (empty($settings['path'])) {
            $settings['path'] = 'templates';
        }

        $this->settings = $settings;
    }

    public function getThemes(): array
    {
        $type = 'module';
        if (!empty($this->settings['theme'])) {
            $type = 'theme';
        }

        return $this->getThemeFiles($type, $this->settings[$type]);
    }

    /**
     * Locate and create theme arrays in a module
     *
     * @param $type
     *   module or theme
     * @param $location
     *   directory in that module or theme
     */
    protected function getThemeFiles(string $type, string $location): array
    {
        $themes = [];
        $dir = drupal_get_path($type, $location) .
            DIRECTORY_SEPARATOR .
            $this->settings['path'];

        if (!file_exists($dir)) {
            return $themes;
        }

        $files = $this->findTwigFiles($dir);

        foreach ($files as $file) {
            $fileName = $this->stripOutTemplatePathAndExtension($dir, $file);
            // Transform the filename to a template name
            // node/article/index.html.twig => node.article.index
            $templateName = preg_replace('/\/|\\\/', '.', $fileName);
            $themes[$templateName] = [
                'variables' => [
                    '_data' => [],
                ],
                'path' => $dir,
                'template' => $fileName,
                'preprocess functions' => [
                    'template_preprocess',
                    'wmcontroller_theme_set_variables',
                ],
            ];
        }

        return $themes;
    }

    /**
     * Find all twig files recursively in a directory
     *
     * @return string[]
     */
    protected function findTwigFiles(string $directory): array
    {
        $fileIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
            )
        );

        $matches = new RegexIterator(
            $fileIterator,
            '#^.*' . preg_quote(static::TWIG_EXT, '#') . '$#',
            RecursiveRegexIterator::GET_MATCH
        );

        // Weed out non-matches
        $files = [];
        foreach ($matches as $match) {
            if (!empty($match[0])) {
                $files[] = $match[0];
            }
        }

        return $files;
    }

    protected function stripOutTemplatePathAndExtension(string $templatePath, string $file): string
    {
        // Strip out the module path
        $file = str_replace($templatePath . DIRECTORY_SEPARATOR, '', $file);
        // Strip out extension
        return preg_replace(
            '#' . preg_quote(static::TWIG_EXT, '#') . '$#',
            '',
            $file
        );
    }
}
