<?php

namespace Encore\Admin\Support;

/**
 * Translates the AdminLTE 2 color names accepted by the 1.x API (InfoBox, Box::style(),
 * grid labels/badges...) to Bootstrap 5 / AdminLTE 4 color names.
 */
class Color
{
    /**
     * AdminLTE 2 name => Bootstrap 5 theme color or AdminLTE 4 extended color
     * (the extended ones come from adminlte-colors.css).
     *
     * @var array
     */
    protected static $map = [
        'default'    => 'secondary',
        'aqua'       => 'info',
        'light-blue' => 'primary',
        'blue'       => 'primary',
        'green'      => 'success',
        'lime'       => 'success',
        'yellow'     => 'warning',
        'red'        => 'danger',
        'maroon'     => 'danger',
        'gray'       => 'secondary',
        'grey'       => 'secondary',
        'black'      => 'dark',
        'purple'     => 'violet',
        'navy'       => 'navy',
        'teal'       => 'teal',
        'olive'      => 'olive',
        'orange'     => 'orange',
        'fuchsia'    => 'fuchsia',
    ];

    /**
     * @param string|null $color
     *
     * @return string|null
     */
    public static function bootstrap($color)
    {
        if ($color === null || $color === '') {
            return $color;
        }

        $color = strtolower(trim($color));

        return static::$map[$color] ?? $color;
    }
}
