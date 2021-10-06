<?php

namespace OFFLINE\Boxes\Classes;

use October\Rain\Parse\Yaml;
use October\Rain\Support\Traits\Singleton;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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

    public function __construct()
    {
        $this->partialsPath = sprintf("%s/%s/", \Cms\Classes\Theme::getActiveTheme()->getPath(), 'partials');
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

        return (object)(new Yaml)->parseFileCached($yamlPath);
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
