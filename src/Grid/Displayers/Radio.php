<?php

namespace Encore\Admin\Grid\Displayers;

use Encore\Admin\Admin;
use Illuminate\Support\Arr;

class Radio extends AbstractDisplayer
{
    public function display($options = [])
    {
        return Admin::component('admin::grid.inline-edit.radio', [
            'key'      => $this->getKey(),
            'name'     => $this->getPayloadName(),
            'value'    => $this->getValue(),
            'resource' => $this->getResource(),
            'trigger'  => "ie-trigger-{$this->getClassName()}",
            'target'   => "ie-template-{$this->getClassName()}",
            // Arr::get() with a null key returns the whole array
            'display'  => is_null($this->getValue()) ? '' : Arr::get($options, $this->getValue(), ''),
            'options'  => $options,
        ]);
    }
}
