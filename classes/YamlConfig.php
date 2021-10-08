<?php

namespace OFFLINE\Boxes\Classes;

use October\Rain\Parse\Yaml;
use October\Rain\Support\Traits\Singleton;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use System\Helpers\System;

/**
 * Parses the YAML configuration files that define partial data schemas.
 */
class YamlConfig
{
    use Singleton;

    /**
     * Path to the partials directory of the currently active theme.
     * @var string
     */
    protected $partialsPath;

    /**
     * @var System
     */
    protected $systemHelper;

    /**
     * @var Yaml
     */
    protected $yaml;

    public function __construct()
    {
        $this->partialsPath = sprintf("%s/%s/", \Cms\Classes\Theme::getActiveTheme()->getPath(), 'partials');
        $this->systemHelper = new System();
        $this->yaml = new Yaml();
    }

    /**
     * Return the parsed YAML config for a given view.
     * @param $partial
     * @return object
     */
    public function configForPartial($partial)
    {
        $yamlPath = $this->partialsPath . str_replace_last('.htm', '.yaml', $partial);

        if (!$partial || !file_exists($yamlPath)) {
            return new \stdClass();
        }

        $base = $this->yaml->parseFileCached($yamlPath);

        return $this->parseIncludes($base);
    }

    /**
     * Replace all `_include` keys with their linked content.
     */
    protected function parseIncludes(array $config)
    {
        $newConfig = [];
        $iterate = function ($config) use (&$iterate, $newConfig) {
            foreach ($config as $key => $value) {
                if ($key === '_include' || starts_with($key, '_include_')) {
                    foreach ($this->getInclude($value) as $newKey => $newValue) {
                        $newConfig[$newKey] = $newValue;
                    }
                } elseif (is_array($value)) {
                    $newConfig[$key] = $iterate($value);
                } else {
                    $newConfig[$key] = $value;
                }
            }
            return $newConfig;
        };

        $newConfig = $iterate($config);

        return (object)$newConfig;
    }

    /**
     * Load and parse an include.
     */
    protected function getInclude($path)
    {
        // A path that starts with $ is relative to the project's base.
        if (starts_with($path, '$')) {
            $path = base_path(substr($path, 1));
        } else {
            $path = $this->partialsPath . $path;
        }

        if (!file_exists($path)) {
            throw new \RuntimeException(sprintf('[OFFLINE.Boxes] Could not find referenced file to include in YAML config: %s does not exist',
                $path));
        }

        if (!$this->systemHelper->checkBaseDir($path)) {
            throw new \RuntimeException(sprintf('[OFFLINE.Boxes] Cannot include files from outside the project\'s base directory: %s cannot be used',
                $path));
        }

        return $this->yaml->parseFileCached($path);
    }

    /**
     * List all partials from the current theme that have a YAML config.
     */
    public function listPartials()
    {
        $files = Finder::create()->files()->name(['*.yml', '*.yaml'])->in($this->partialsPath);
        if (!$files->hasResults()) {
            return [];
        }

        return collect($files)
            ->mapWithKeys(function (SplFileInfo $file) {
                $yaml = (new Yaml())->parseFileCached($file->getRealPath());
                $name = $yaml['name'] ?? $file->getFilename();

                // path/relative/to/partials.yaml => name
                return [
                    str_replace($this->partialsPath, '', $file->getRealPath()) => $name,
                ];
            })
            ->toArray();
    }
}

