<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Admin;

class CheckboxCard extends CheckboxButton
{
    protected function addStyle()
    {
        $style = <<<'STYLE'
.la-card-group label.card {
    cursor: pointer;
    margin-bottom: 0;
    font-weight: 400;
}

.la-card-group .card-body {
    padding: .5rem 1rem;
}

.la-card-group label.card.active {
    border: 2px solid var(--bs-primary);
}
STYLE;

        Admin::style($style);
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $this->addStyle();

        return parent::render();
    }
}
