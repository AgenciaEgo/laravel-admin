<?php

namespace Encore\Admin\Grid\Column;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Model;

class InputFilter extends Filter
{
    /**
     * @var string
     */
    protected $type;

    /**
     * InputFilter constructor.
     *
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
        $this->class = uniqid('column-filter-');
    }

    /**
     * Add a binding to the query.
     *
     * @param string     $value
     * @param Model|null $model
     */
    public function addBinding($value, Model $model)
    {
        if (empty($value)) {
            return;
        }

        if ($this->type == 'like') {
            $model->where($this->getColumnName(), 'like', "%{$value}%");

            return;
        }

        if (in_array($this->type, ['date', 'time'])) {
            $method = 'where'.ucfirst($this->type);
            $model->{$method}($this->getColumnName(), $value);

            return;
        }

        $model->where($this->getColumnName(), $value);
    }

    /**
     * Add script to page.
     *
     * @return void
     */
    protected function addScript()
    {
        $options = [
            'locale'           => config('app.locale'),
            'allowInputToggle' => true,
        ];

        if ($this->type == 'date') {
            $options['format'] = 'YYYY-MM-DD';
        } elseif ($this->type == 'time') {
            $options['format'] = 'HH:mm:ss';
        } elseif ($this->type == 'datetime') {
            $options['format'] = 'YYYY-MM-DD HH:mm:ss';
        } else {
            return;
        }

        $options = json_encode($options);

        Admin::script("$.admin.datetimepicker('.{$this->class}', $options);");
    }

    /**
     * Render this filter.
     *
     * @return string
     */
    public function render()
    {
        $script = <<<'SCRIPT'
$('.dropdown-menu input').click(function(e) {
    e.stopPropagation();
});
SCRIPT;
        Admin::script($script);

        $this->addScript();

        $value = $this->getFilterValue();

        $active = empty($value) ? '' : 'text-warning';

        return <<<EOT
<span class="dropdown">
    <form action="{$this->getFormAction()}" pjax-container style="display: inline-block;">
    <a href="javascript:void(0);" class="{$active}" data-bs-toggle="dropdown" data-bs-auto-close="outside">
        <i class="fa fa-filter"></i>
    </a>
    <ul class="dropdown-menu" role="menu" style="padding: 10px;">
        <li>
            <input type="text" name="{$this->getColumnName()}" value="{$this->getFilterValue()}" class="form-control form-control-sm {$this->class}" autocomplete="off"/>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li class="text-end">
            <button class="btn btn-sm btn-primary column-filter-submit float-start" data-loading-text="{$this->trans('search')}..."><i class="fa fa-search"></i>&nbsp;&nbsp;{$this->trans('search')}</button>
            <span><a href="{$this->getFormAction()}" class="btn btn-sm btn-outline-secondary column-filter-all"><i class="fa fa-undo"></i></a></span>
        </li>
    </ul>
    </form>
</span>
EOT;
    }
}
