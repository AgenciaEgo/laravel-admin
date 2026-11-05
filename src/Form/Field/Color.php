<?php

namespace Encore\Admin\Form\Field;

class Color extends Text
{
    protected static $css = [
        '/vendor/laravel-admin/coloris/coloris.min.css',
    ];

    protected static $js = [
        '/vendor/laravel-admin/coloris/coloris.min.js',
    ];

    /**
     * Use `hex` format.
     *
     * @return $this
     */
    public function hex()
    {
        return $this->options(['format' => 'hex']);
    }

    /**
     * Use `rgb` format.
     *
     * @return $this
     */
    public function rgb()
    {
        return $this->options(['format' => 'rgb']);
    }

    /**
     * Use `rgba` format.
     *
     * @return $this
     */
    public function rgba()
    {
        return $this->options(['format' => 'rgba']);
    }

    /**
     * Render this filed.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function render()
    {
        $options = json_encode($this->options);

        // Coloris through the adapter in laravel-admin.js (bootstrap-colorpicker in 1.x)
        $this->script = "$.admin.colorpicker('{$this->getElementClassSelector()}', $options);";

        $this->prepend('<i class="la-color-swatch"></i>')
            ->defaultAttribute('style', 'width: 140px');

        return parent::render();
    }
}
