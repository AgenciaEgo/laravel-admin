<?php

namespace Encore\Admin\Form\Field;

class Date extends Text
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

    public function format($format)
    {
        $this->format = $format;

        return $this;
    }

    public function prepare($value)
    {
        if ($value === '') {
            $value = null;
        }

        return $value;
    }

    public function render()
    {
        $this->options['format'] = $this->format;
        $this->options['locale'] = array_key_exists('locale', $this->options) ? $this->options['locale'] : config('app.locale');
        // flatpickr through the eonasdan-compatible adapter in laravel-admin.js
        $this->script = "$.admin.datetimepicker('{$this->getElementClassSelector()}', ".json_encode($this->options).');';

        $this->prepend('<i class="fa fa-calendar fa-fw"></i>')
            ->defaultAttribute('style', 'width: 110px');

        return parent::render();
    }
}
