<?php

namespace Encore\Admin\Grid\Column;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Model;

class CheckFilter extends Filter
{
    /**
     * @var array
     */
    protected $options;

    /**
     * CheckFilter constructor.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;

        $this->class = [
            'all'  => uniqid('column-filter-all-'),
            'item' => uniqid('column-filter-item-'),
        ];
    }

    /**
     * Add a binding to the query.
     *
     * @param array $value
     * @param Model $model
     */
    public function addBinding($value, Model $model)
    {
        if (empty($value)) {
            return;
        }

        $model->whereIn($this->getColumnName(), $value);
    }

    /**
     * Add script to page.
     *
     * @return void
     */
    protected function addScript()
    {
        $script = <<<SCRIPT
$('.{$this->class['all']}').on('change', function () {
    $('.{$this->class['item']}').laCheck(this.checked);
});
SCRIPT;

        Admin::script($script);
    }

    /**
     * Render this filter.
     *
     * @return string
     */
    public function render()
    {
        $value = $this->getFilterValue([]);

        $lists = collect($this->options)->map(function ($label, $key) use ($value) {
            $checked = in_array($key, $value) ? 'checked' : '';

            return <<<HTML
<li class="form-check" style="margin: 0;">
    <label class="form-check-label" style="width: 100%;padding: 3px;">
        <input type="checkbox" class="form-check-input {$this->class['item']}" name="{$this->getColumnName()}[]" value="{$key}" {$checked}/>{$label}
    </label>
</li>
HTML;
        })->implode("\r\n");

        $this->addScript();

        $allCheck = (count($value) == count($this->options)) ? 'checked' : '';
        $active = empty($value) ? '' : 'text-warning';

        return <<<EOT
<span class="dropdown">
<form action="{$this->getFormAction()}" pjax-container style="display: inline-block;">
    <a href="javascript:void(0);" class="{$active}" data-bs-toggle="dropdown" data-bs-auto-close="outside">
        <i class="fa fa-filter"></i>
    </a>
    <ul class="dropdown-menu" role="menu" style="padding: 10px;">

        <li>
            <ul style='padding: 0;'>
            <li class="form-check" style="margin: 0;">
                <label class="form-check-label" style="width: 100%;padding: 3px;">
                    <input type="checkbox" class="form-check-input {$this->class['all']}" {$allCheck}/>{$this->trans('all')}
                </label>
            </li>
                <li><hr class="dropdown-divider"></li>
                {$lists}
            </ul>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li class="text-end">
            <button class="btn btn-sm btn-primary float-start" data-loading-text="{$this->trans('search')}..."><i class="fa fa-search"></i>&nbsp;&nbsp;{$this->trans('search')}</button>
            <span><a href="{$this->getFormAction()}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-undo"></i></a></span>
        </li>
    </ul>
</form>
</span>
EOT;
    }
}
