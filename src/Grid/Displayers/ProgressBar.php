<?php

namespace Encore\Admin\Grid\Displayers;

class ProgressBar extends AbstractDisplayer
{
    public function display($style = 'primary', $size = 'sm', $max = 100)
    {
        $style = collect((array) $style)->map(function ($style) {
            return 'bg-'.\Encore\Admin\Support\Color::bootstrap($style);
        })->implode(' ');

        $this->value = (int) $this->value;

        return <<<EOT
<div class="d-flex align-items-center gap-2" style="min-width: 100px;">
    <span class="text-body-secondary small">{$this->value}%</span>
    <div class="progress progress-$size flex-grow-1" style="width: 100px;">
        <div class="progress-bar $style" role="progressbar" aria-valuenow="{$this->value}" aria-valuemin="0" aria-valuemax="$max" style="width: {$this->value}%">
        </div>
    </div>
</div>
EOT;
    }
}
