<?php namespace OFFLINE\Boxes\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Backend\Widgets\Form;
use OFFLINE\Boxes\Classes\YamlConfig;
use OFFLINE\Boxes\Models\Instance;

/**
 * PartialFormFields widget renders a
 * form based on the yaml config for a given partial.
 */
class PartialFormFields extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'offline_boxes_partial_form_fields';
    /**
     * @var Form
     */
    protected $widget;

    public function init()
    {
        $yaml = YamlConfig::instance();

        // Get the partial from the Post data, from the model, or use the first available from the theme.
        $partial = $this->getPartial($yaml);

        if (!$partial) {
            return;
        }

        $config = $this->buildConfig($yaml, $partial);

        $this->widget = $this->buildWidget($config);
    }

    /**
     * Build the config object for the Form widget.
     */
    protected function buildConfig(YamlConfig $yaml, string $partial)
    {
        $partialConfig = $yaml->configForPartial($partial);

        $config = (object)$partialConfig->form;
        $config->arrayName = $this->formField->getName();
        $config->model = property_exists($partialConfig, 'modelClass')
            ? new $partialConfig->modelClass
            : new Instance();

        return $config;
    }

    /**
     * Build the form widget containing the fields defined in the YAML config.
     */
    protected function buildWidget(object $config)
    {
        $widget = new Form($this->controller, $config);

        $clone = $this->model->buildClone();

        // Set the nested "data" property of the model as well as all relations as the widget's data.
        $widget->data = $clone;
        $widget->model = $clone;
        $widget->bindToController();

        return $widget;
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        if (!$this->model->partial) {
            return '';
        }

        $this->prepareVars();

        return $this->makePartial('partialformfields');
    }

    /**
     * prepareVars for view data
     */
    public function prepareVars()
    {
        $this->vars['widget'] = $this->widget;
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
    }

    /**
     * Get the active partial from the Post data, from the model or use
     * the first available YAML from the theme as fallback.
     */
    protected function getPartial(YamlConfig $yaml)
    {
        $partial = post('Instance.partial', $this->model->partial);

        if (!$partial) {
            $partial = array_first(array_keys($yaml->listPartials()));
        }

        return $partial;
    }

}
