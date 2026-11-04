<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Admin;

class BelongsToMany extends MultipleSelect
{
    use BelongsToRelation;

    protected function addScript()
    {
        $script = <<<SCRIPT
;(function () {

    var grid = $('.belongstomany-{$this->column()}');
    var modal = $('#{$this->modalID}');
    var table = grid.find('.grid-table');
    var selected = $("{$this->getElementClassSelector()}").val() || [];
    var rows = {};

    table.find('tbody').children().each(function (index, tr) {
        if ($(tr).find('.grid-row-remove').length > 0) {
            rows[$(tr).find('.grid-row-remove').data('key')] = $(tr);
        }
    });

    // open modal
    grid.find('.select-relation').click(function (e) {
        $('#{$this->modalID}').modal('show');
        e.preventDefault();
    });

    // remove row
    grid.on('click', '.grid-row-remove', function () {
        val = $(this).data('key').toString();

        var index = selected.indexOf(val);
        if (index !== -1) {
           selected.splice(index, 1);
           delete rows[val];
        }

        $(this).parents('tr').remove();
        $("{$this->getElementClassSelector()}").val(selected);

        if (selected.length == 0) {
            var empty = $('.belongstomany-{$this->column()}').find('template.empty').html();
            table.find('tbody').append(empty);
        }
    });

    var load = function (url) {
        $.get(url, function (data) {
            modal.find('.modal-body').html(data);
            modal.find('.box-header:first').hide();

            modal.find('input.select').each(function (index, el) {
                if ($.inArray($(el).val().toString(), selected) >=0 ) {
                    $(el).laToggle();
                }
            });
        });
    };

    var update = function (callback) {

        $("{$this->getElementClassSelector()}")
            .select2({data: selected})
            .val(selected)
            .trigger('change')
            .next()
            .addClass('d-none');

        table.find('tbody').empty();

        Object.values(rows).forEach(function (row) {
            row.find('td:last a').removeClass('d-none');
            row.find('td.column-__modal_selector__').remove();
            table.find('tbody').append(row);
        });

        if (selected.length == 0) {
            var empty = $('.belongstomany-{$this->column()}').find('template.empty').html();
            table.find('tbody').append(empty);
        } else {
            table.find('.empty-grid').parent().remove();
        }

        callback();
    };

    modal.on('show.bs.modal', function (e) {
        load("{$this->getLoadUrl(1)}");
    }).on('click', '.page-item a, .filter-box a', function (e) {
        load($(this).attr('href'));
        e.preventDefault();
    }).on('click', 'tr', function (e) {
        if ($(e.target).is('input.select')) return; // the checkbox toggles itself
        $(this).find('input.select').laToggle();
        e.preventDefault();
    }).on('submit', '.box-header form', function (e) {
        load($(this).attr('action')+'&'+$(this).serialize());
        e.preventDefault();
        return false;
    }).on('change', 'input.select', function (e) {
        if (!this.checked) return;
        if (selected.indexOf($(this).val()) < 0) {
            selected.push($(this).val());
            rows[$(e.target).val()] = $(e.target).parents('tr');
        }
    }).on('change', 'input.select', function (e) {
        if (this.checked) return;
           var val = $(this).val();
           var index = selected.indexOf(val);
           if (index !== -1) {
               selected.splice(index, 1);
               delete rows[$(e.target).val()];
           }
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

        if ($this->value()) {
            $options = array_combine($this->value(), $this->value());
        }

        return $options;
    }
}
