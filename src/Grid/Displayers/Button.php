<?php

namespace Encore\Admin\Grid\Displayers;

class Button extends AbstractDisplayer
{
    public function display($style = null)
    {
        $style = collect((array) $style)->map(function ($style) {
            // 1.x names: default = white button, xs = smallest size, flat = no effect in Bootstrap 5
            $map = ['default' => 'outline-secondary', 'xs' => 'sm', 'flat' => null];
            $style = array_key_exists($style, $map) ? $map[$style] : \Encore\Admin\Support\Color::bootstrap($style);

            return $style ? 'btn-'.$style : '';
        })->implode(' ');

        return "<span class='btn $style'>{$this->value}</span>";
    }
}
