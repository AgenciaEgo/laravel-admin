<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Form\Field;

class DateRange extends Field
{
    protected static $css = [
        '/vendor/laravel-admin/flatpickr/dist/flatpickr.min.css',
        '/vendor/laravel-admin/flatpickr/dist/plugins/monthSelect/style.css',
    ];

    protected static $js = [
        '/vendor/laravel-admin/flatpickr/dist/flatpickr.min.js',
        '/vendor/laravel-admin/flatpickr/dist/l10n/index.js',
        '/vendor/laravel-admin/flatpickr/dist/plugins/monthSelect/index.js',
    ];

    protected $format = 'YYYY-MM-DD';

    /**
     * Column name.
     *
     * @var array
     */
    protected $column = [];

    public function __construct($column, $arguments)
    {
        $this->column['start'] = $column;
        $this->column['end'] = $arguments[0];

        array_shift($arguments);
        $this->label = $this->formatLabel($arguments);
        $this->id = $this->formatId($this->column);

        $this->options(['format' => $this->format]);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($value)
    {
        if ($value === '') {
            $value = null;
        }

        return $value;
    }

    public function render()
    {
        $this->options['locale'] = array_key_exists('locale', $this->options) ? $this->options['locale'] : config('app.locale');

        $options = json_encode($this->options);

        $class = $this->getElementClassSelector();

        $this->script = <<<EOT
            $.admin.datetimeRange('{$class['start']}', '{$class['end']}', $options);
EOT;

        return parent::render();
    }
}
