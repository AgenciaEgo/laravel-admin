<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Form\Field;
use Encore\Admin\Support\Color;
use Illuminate\Support\Arr;

class SwitchField extends Field
{
    protected $states = [
        'on'  => ['value' => 1, 'text' => 'ON', 'color' => 'primary'],
        'off' => ['value' => 0, 'text' => 'OFF', 'color' => 'default'],
    ];

    protected $size = 'small';

    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    public function states($states = [])
    {
        foreach (Arr::dot($states) as $key => $state) {
            Arr::set($this->states, $key, $state);
        }

        return $this;
    }

    public function prepare($value)
    {
        if (isset($this->states[$value])) {
            return $this->states[$value]['value'];
        }

        return $value;
    }

    /**
     * Classes with the Bootstrap 5 theme colors of the switch states (also used by the
     * grid switch displayers): the "on" color fills the checked switch, the "off" color
     * (if not the default gray) tints the unchecked one. The on/off texts of
     * bootstrap-switch have no native equivalent.
     *
     * @param array $states
     *
     * @return string
     */
    public static function colorClasses(array $states)
    {
        $themed = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
        $classes = [];

        $on = Color::bootstrap($states['on']['color'] ?? 'primary');
        if ($on !== 'primary' && in_array($on, $themed, true)) {
            $classes[] = "la-switch-{$on}";
        }

        $off = Color::bootstrap($states['off']['color'] ?? 'default');
        if ($off !== 'secondary' && in_array($off, $themed, true)) {
            $classes[] = "la-switch-off-{$off}";
        }

        return implode(' ', $classes);
    }

    public function render()
    {
        if (!$this->shouldRender()) {
            return '';
        }

        foreach ($this->states as $state => $option) {
            if ($this->value() == $option['value']) {
                $this->value = $state;
                break;
            }
        }

        // native Bootstrap 5 switch: the visible checkbox feeds the hidden input with
        // 'on' / 'off', which prepare() maps to the state values (same contract as 1.x)
        $this->addVariables(['switchColors' => static::colorClasses($this->states)]);

        $this->script = <<<EOT

$('{$this->getElementClassSelector()}.la_checkbox').on('change', function () {
    $(this).closest('.form-switch').next('input[type="hidden"]').val(this.checked ? 'on' : 'off').change();
});

EOT;

        return parent::render();
    }
}
