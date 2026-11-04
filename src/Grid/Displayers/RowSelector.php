<?php

namespace Encore\Admin\Grid\Displayers;

use Encore\Admin\Admin;

class RowSelector extends AbstractDisplayer
{
    public function display()
    {
        Admin::script($this->script());

        return <<<EOT
<input type="checkbox" class="form-check-input {$this->grid->getGridRowName()}-checkbox" data-id="{$this->getKey()}"  autocomplete="off"/>
EOT;
    }

    protected function script()
    {
        $all = $this->grid->getSelectAllName();
        $row = $this->grid->getGridRowName();

        $selected = trans('admin.grid_items_selected');

        return <<<EOT
$('.{$row}-checkbox').on('change', function () {

    var id = $(this).data('id');

    if (this.checked) {
        \$.admin.grid.select(id);
        $(this).closest('tr').css('background-color', '#ffffd5');
    } else {
        \$.admin.grid.unselect(id);
        $(this).closest('tr').css('background-color', '');
    }

    var selected = $.admin.grid.selected().length;

    if (selected > 0) {
        $('.{$all}-btn').show();
    } else {
        $('.{$all}-btn').hide();
    }

    $('.{$all}-btn .selected').html("{$selected}".replace('{n}', selected));
});

EOT;
    }
}
