<?php

namespace Encore\Admin\Grid\Filter\Presenter;

use Encore\Admin\Facades\Admin;

class Checkbox extends Radio
{
    protected function prepare()
    {
        // native form-check inputs: nothing to initialise (iCheck in 1.x)
    }
}
