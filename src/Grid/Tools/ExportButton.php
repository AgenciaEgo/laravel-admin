<?php

namespace Encore\Admin\Grid\Tools;

use Encore\Admin\Admin;
use Encore\Admin\Grid;

class ExportButton extends AbstractTool
{
    /**
     * @var Grid
     */
    protected $grid;

    /**
     * Create a new Export button instance.
     *
     * @param Grid $grid
     */
    public function __construct(Grid $grid)
    {
        $this->grid = $grid;
    }

    /**
     * Set up script for export button.
     */
    protected function setUpScripts()
    {
        $script = <<<SCRIPT

$('.{$this->grid->getExportSelectedName()}').click(function (e) {
    e.preventDefault();
    
    var rows = $.admin.grid.selected().join();

    if (!rows) {
        return false;
    }
    
    var href = $(this).attr('href').replace('__rows__', rows);
    location.href = href;
});

SCRIPT;

        Admin::script($script);
    }

    /**
     * Render Export button.
     *
     * @return string
     */
    public function render()
    {
        if (!$this->grid->showExportBtn()) {
            return '';
        }

        $this->setUpScripts();

        $trans = [
            'export'        => trans('admin.export'),
            'all'           => trans('admin.all'),
            'current_page'  => trans('admin.current_page'),
            'selected_rows' => trans('admin.selected_rows'),
        ];

        $page = request('page', 1);

        return <<<EOT

<div class="btn-group">
    <a href="{$this->grid->getExportUrl('all')}" target="_blank" class="btn btn-sm btn-info" title="{$trans['export']}"><i class="fa fa-download"></i><span class="d-none d-sm-inline"> {$trans['export']}</span></a>
    <button type="button" class="btn btn-sm btn-info dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
        <span class="visually-hidden">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu" role="menu">
        <li><a class="dropdown-item" href="{$this->grid->getExportUrl('all')}" target="_blank">{$trans['all']}</a></li>
        <li><a class="dropdown-item" href="{$this->grid->getExportUrl('page', $page)}" target="_blank">{$trans['current_page']}</a></li>
        <li><a href="{$this->grid->getExportUrl('selected', '__rows__')}" target="_blank" class='dropdown-item {$this->grid->getExportSelectedName()}'>{$trans['selected_rows']}</a></li>
    </ul>
</div>
EOT;
    }
}
