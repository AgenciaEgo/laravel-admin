<?php

namespace Encore\Admin\Grid\Selectable;

use Encore\Admin\Grid\Displayers\AbstractDisplayer;

class Checkbox extends AbstractDisplayer
{
    public function display($key = '')
    {
        $value = $this->getAttribute($key);

        return <<<EOT
<input type="checkbox" name="item" class="form-check-input select" value="{$value}"/>
EOT;
    }
}
