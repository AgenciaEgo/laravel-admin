<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Admin;

class BelongsTo extends Select
{
    use BelongsToRelation;

    protected function addScript()
    {
        $script = <<<SCRIPT
;(function () {

    var grid = $('.belongsto-{$this->column()}');
    var modal = $('#{$this->modalID}');
    var table = grid.find('.grid-table');
    var selected = $("{$this->getElementClassSelector()}").val();
    var row = null;

    // open modal
    grid.find('.select-relation').click(function (e) {
        $('#{$this->modalID}').modal('show');
        e.preventDefault();
    });

    // remove row
    grid.on('click', '.grid-row-remove', function () {
        selected = null;
        $(this).parents('tr').remove();
        $("{$this->getElementClassSelector()}").val(null);

        var empty = $('.belongsto-{$this->column()}').find('template.empty').html();

        table.find('tbody').append(empty);
    });

    var load = function (url) {
        $.get(url, function (data) {
            modal.find('.modal-body').html(data);
            modal.find('.card-header:first').hide();

            modal.find('input.select').each(function (index, el) {
                if ($(el).val() == selected) {
                    $(el).laToggle();
                }
            });
        });
    };

    var update = function (callback) {

        $("{$this->getElementClassSelector()}")
            .select2({data: [selected]})
            .val(selected)
            .trigger('change')
            .next()
            .addClass('d-none');

        if (row) {
            row.find('td:last a').removeClass('d-none');
            row.find('td:first').remove();
            table.find('tbody').empty().append(row);
        }

        callback();
    };

    modal.on('show.bs.modal', function (e) {
        load("{$this->getLoadUrl()}");
    }).on('click', '.page-item a, .filter-box a', function (e) {
        load($(this).attr('href'));
        e.preventDefault();
    }).on('click', 'tr', function (e) {
        if ($(e.target).is('input.select')) return; // the checkbox toggles itself
        $(this).find('input.select').laToggle();
        e.preventDefault();
    }).on('submit', '.grid-tools form, .filter-box form', function (e) {
        load($(this).attr('action')+'&'+$(this).serialize());
        return false;
    }).on('change', 'input.select', function (e) {
        if (!this.checked) return;
        row = $(e.target).parents('tr');
        selected = $(this).val();
    }).find('.modal-footer .submit').click(function () {
        update(function () {
            modal.modal('toggle');
        });
    });
})();
SCRIPT;

        Admin::script($script);

        return $this;
    }

    protected function getOptions()
    {
        $options = [];

        if ($value = $this->value()) {
            $options = [$value => $value];
        }

        return $options;
    }
}
