<?php

namespace Encore\Admin\Widgets;

use Encore\Admin\Support\Color;
use Illuminate\Contracts\Support\Renderable;

class Box extends Widget implements Renderable
{
    /**
     * @var string
     */
    protected $view = 'admin::widgets.box';

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $content = 'here is the box content.';

    /**
     * @var string
     */
    protected $footer = '';

    /**
     * @var array
     */
    protected $tools = [];

    /**
     * @var string
     */
    protected $script;

    /**
     * Styles given to style() / solid(), resolved to card classes on render.
     *
     * @var array
     */
    protected $styles = [];

    /**
     * Box constructor.
     *
     * @param string $title
     * @param string $content
     */
    public function __construct($title = '', $content = '', $footer = '')
    {
        if ($title) {
            $this->title($title);
        }

        if ($content) {
            $this->content($content);
        }

        if ($footer) {
            $this->footer($footer);
        }

        $this->class('card mb-3');
    }

    /**
     * Set box content.
     *
     * @param string|Renderable $content
     *
     * @return $this
     */
    public function content($content)
    {
        if ($content instanceof Renderable) {
            $this->content = $content->render();
        } else {
            $this->content = (string) $content;
        }

        return $this;
    }

    /**
     * Set box footer.
     *
     * @param string|Renderable $footer
     *
     * @return $this
     */
    public function footer($footer)
    {
        if ($footer instanceof Renderable) {
            $this->footer = $footer->render();
        } else {
            $this->footer = (string) $footer;
        }

        return $this;
    }

    /**
     * Set box title.
     *
     * @param string $title
     *
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set box as collapsable.
     *
     * @return $this
     */
    public function collapsable()
    {
        $this->tools[] =
            '<button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">'
            .'<i data-lte-icon="expand" class="fa fa-plus"></i><i data-lte-icon="collapse" class="fa fa-minus"></i></button>';

        return $this;
    }

    /**
     *  Set box body scrollable.
     *
     * @param array $options
     *
     * @return $this
     */
    public function scrollable($options = [], $nodeSelector = '')
    {
        $this->id = uniqid('box-slim-scroll-');
        $scrollOptions = json_encode($options);
        $nodeSelector = $nodeSelector ?: '.card-body';

        $this->script = <<<SCRIPT
$("#{$this->id} {$nodeSelector}").slimScroll({$scrollOptions});
SCRIPT;

        return $this;
    }

    /**
     * Set box as removable.
     *
     * @return $this
     */
    public function removable()
    {
        $this->tools[] =
            '<button type="button" class="btn btn-tool" data-lte-toggle="card-remove"><i class="fa fa-times"></i></button>';

        return $this;
    }

    /**
     * Set box style.
     *
     * @param string $styles
     *
     * @return $this|Box
     */
    public function style($styles)
    {
        foreach ((array) $styles as $style) {
            $this->styles[] = $style;
        }

        return $this;
    }

    /**
     * Card classes for the styles: a color gives an outlined card ("card-primary card-outline"),
     * "solid" fills the header with it instead, "default" means no color.
     *
     * @return string
     */
    protected function styleClasses()
    {
        $solid = in_array('solid', $this->styles, true);
        $colors = array_values(array_diff($this->styles, ['solid', 'default']));

        if (!$color = Color::bootstrap(end($colors) ?: null)) {
            return '';
        }

        return $solid ? "card-$color" : "card-$color card-outline";
    }

    public function solid()
    {
        return $this->style('solid');
    }

    /**
     * Variables in view.
     *
     * @return array
     */
    protected function variables()
    {
        return [
            'title'      => $this->title,
            'content'    => $this->content,
            'footer'     => $this->footer,
            'tools'      => $this->tools,
            'attributes' => $this->formatAttributes(['class' => $this->styleClasses()]),
            'script'     => $this->script,
        ];
    }

    /**
     * Render box.
     *
     * @return string
     */
    public function render()
    {
        return view($this->view, $this->variables())->render();
    }
}
