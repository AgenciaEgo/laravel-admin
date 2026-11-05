<?php

namespace Encore\Admin\Grid\Displayers;

/**
 * Grid ->editable() (2.0).
 *
 * x-editable (Bootstrap 3 only) is gone: each editable type is rendered by the matching
 * inline-edit displayer, which saves through the same PUT endpoint (`_edit_inline`).
 * The public signature of 1.x is kept:
 *
 *   ->editable()                                   Input
 *   ->editable('textarea')                         Textarea
 *   ->editable('select', $options)                 Select ($options may be a closure, called with the row)
 *   ->editable('date' | 'datetime' | 'year' | 'month' | 'day' | 'time')
 *   ->editable('combodate', $format = 'YYYY-MM-DD') Datetime with that (moment) format
 */
class Editable extends AbstractDisplayer
{
    /**
     * Moment formats of the date types (the same ones x-editable used).
     *
     * @var array
     */
    protected $formats = [
        'date'      => 'YYYY-MM-DD',
        'datetime'  => 'YYYY-MM-DD HH:mm:ss',
        'year'      => 'YYYY',
        'month'     => 'MM',
        'day'       => 'DD',
        'time'      => 'HH:mm:ss',
        'combodate' => 'YYYY-MM-DD',
    ];

    /**
     * @param string $type
     * @param mixed  ...$arguments
     *
     * @return string
     */
    public function display($type = 'text', ...$arguments)
    {
        if ($type === 'textarea') {
            return $this->delegate(Textarea::class);
        }

        if ($type === 'select') {
            $options = $arguments[0] ?? [];

            if ($options instanceof \Closure) {
                $options = $options->call($this, $this->row);
            }

            return $this->delegate(Select::class, [$options]);
        }

        if (isset($this->formats[$type])) {
            $format = ($type === 'combodate' && !empty($arguments[0])) ? $arguments[0] : $this->formats[$type];

            return $this->delegate(Datetime::class, [$format]);
        }

        return $this->delegate(Input::class);
    }

    /**
     * Render the cell with another displayer on the same value, row and column.
     *
     * @param string $displayer
     * @param array  $arguments
     *
     * @return string
     */
    protected function delegate($displayer, array $arguments = [])
    {
        return (new $displayer($this->value, $this->grid, $this->column, $this->row))->display(...$arguments);
    }
}
