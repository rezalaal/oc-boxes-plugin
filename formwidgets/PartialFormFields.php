<?php namespace OFFLINE\Boxes\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Backend\Widgets\Form;
use OFFLINE\Boxes\Classes\YamlConfig;
use OFFLINE\Boxes\Models\Instance;
use RainLab\Translate\Classes\Translator;

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

    /**
     * @var object
     */
    protected object $partialConfig;

    public function init()
    {
        $yaml = YamlConfig::instance();

        $partial = $this->getPartial($yaml);
        if (!$partial) {
            return;
        }

        $this->partialConfig = $yaml->configForPartial($partial);

        $this->model->partial = $partial;

        $this->widget = $this->buildWidget(
            $this->buildConfig($partial)
        );
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
     * Build the config object for the Form widget.
     */
    protected function buildConfig(string $partial)
    {

        $config = (object)$this->partialConfig->form;
        $config->arrayName = $this->formField->getName();

        $model = property_exists($this->partialConfig, 'modelClass')
            ? new $this->partialConfig->modelClass
            : new Instance();

        $model->partial = $partial;
        $config->model = $model->buildClone([], $this->partialConfig);

        return $config;
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
     * prepare view vars
     */
    public function prepareVars()
    {
        $this->vars['widget'] = $this->widget;
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
    }

    public function getSaveValue($value)
    {
        // Set the translated attributes values on the model, if RainLab.Translate is installed.
        if ($this->model->methodExists('setAttributeTranslated')) {
            $data = post('RLTranslate', []);

            $defaultLocale = Translator::instance()->getDefaultLocale();

            foreach ($data as $locale => $fields) {
                // Only set attribute values for non-default locales.
                if ($locale === $defaultLocale) {
                    continue;
                }
                foreach ($fields as $field => $fieldValue) {
                    $this->model->setAttributeTranslated($field, $fieldValue, $locale);
                }
            }
        }

        return $this->widget->getSaveData();
    }

    /**
     * Get the active partial from the Post data or from the model.
     */
    protected function getPartial(YamlConfig $yaml)
    {
        return post('Instance.partial', $this->model->partial);
    }

}
