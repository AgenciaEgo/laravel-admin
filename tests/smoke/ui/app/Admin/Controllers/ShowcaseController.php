<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\ShowcaseBatchAction;
use App\Admin\Actions\ShowcaseRowAction;
use App\Models\Showcase;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Show;
use Encore\Admin\Widgets;

/**
 * Exercises every form field, grid displayer/filter/tool, show field and widget
 * the package ships, so the UI can be screenshotted and compared across the
 * Bootstrap 3 → 5 migration (see tests/smoke/ui/screenshots.mjs).
 */
class ShowcaseController extends AdminController
{
    protected $title = 'Showcase';

    public const OPTIONS = ['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma'];

    protected function grid()
    {
        $grid = new Grid(new Showcase());

        $grid->header(function () {
            return 'Grid header: <code>$grid->header()</code>';
        });
        $grid->footer(function () {
            return 'Grid footer: <code>$grid->footer()</code>';
        });

        $grid->column('id', 'ID')->sortable();
        $grid->column('title')->editable()->help('Inline text editable')->filter('like');
        $grid->column('select')->editable('select', self::OPTIONS);
        $grid->column('radio')->radio(self::OPTIONS);
        $grid->column('checkbox')->checkbox(self::OPTIONS);
        $grid->column('multiple_select', 'Labels')->label('info');
        $grid->column('tags', 'Badges')->badge('green');
        $grid->column('switch')->switch();
        $grid->column('date')->editable('date')->filter('range', 'date');
        $grid->column('image')->image('', 40, 40);
        $grid->column('progress')->progressBar();
        $grid->column('number')->totalRow();
        $grid->column('email')->copyable();
        $grid->column('url')->link()->qrcode();
        $grid->column('ip')->secret();
        $grid->column('currency')->prefix('$');
        $grid->column('decimal')->suffix('kg');
        $grid->column('textarea')->limit(15);
        $grid->column('icon')->display(function ($icon) {
            return "<i class=\"fa {$icon}\"></i> {$icon}";
        });
        $grid->column('mobile', 'Expand')->expand(function () {
            return new Widgets\Table(['Key', 'Value'], collect($this->key_value)->map(function ($v, $k) {
                return [$k, $v];
            })->values()->all());
        });
        $grid->column('color', 'Modal')->modal('Modal title', function () {
            return new Widgets\Table(['Key', 'Value'], (array) $this->table);
        });
        $grid->column('file')->downloadable();
        $grid->column('created_at')->hide();

        $grid->quickSearch('title');
        $grid->quickCreate(function (Grid\Tools\QuickCreate $create) {
            $create->text('title');
            $create->select('select')->options(self::OPTIONS);
            $create->date('date');
        });
        $grid->selector(function (Grid\Tools\Selector $selector) {
            $selector->select('select', 'Selector', self::OPTIONS);
        });

        $grid->expandFilter();
        $grid->filter(function (Grid\Filter $filter) {
            $filter->column(1 / 2, function (Grid\Filter $filter) {
                $filter->like('title');
                $filter->equal('select')->select(self::OPTIONS);
                $filter->in('radio')->checkbox(self::OPTIONS);
            });
            $filter->column(1 / 2, function (Grid\Filter $filter) {
                $filter->between('date')->date();
                $filter->between('datetime')->datetime();
                $filter->equal('switch')->radio(['' => 'All', 0 => 'Off', 1 => 'On']);
            });
            $filter->scope('switched', 'Switched on')->where('switch', 1);
        });

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->add(new ShowcaseRowAction());
        });
        $grid->batchActions(function (Grid\Tools\BatchActions $batch) {
            $batch->add(new ShowcaseBatchAction());
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Showcase::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('title');
        $show->field('email');
        $show->field('select')->using(self::OPTIONS);
        $show->field('multiple_select')->label();
        $show->field('tags')->badge();
        $show->field('switch')->using([0 => 'Off', 1 => 'On'])->dot([0 => 'danger', 1 => 'success']);
        $show->divider();
        $show->field('url')->link();
        $show->field('image')->image();
        $show->field('file')->file();
        $show->field('key_value')->json();
        $show->field('textarea');
        $show->field('created_at');

        $show->items('Has many items', function (Grid $grid) {
            $grid->column('name');
            $grid->column('qty');
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Showcase());

        $form->fieldset('Basic inputs', function (Form $form) {
            $form->display('id', 'ID');
            $form->text('title')->required()->help('Help text under a field');
            $form->email('email');
            $form->url('url');
            $form->ip('ip');
            $form->mobile('mobile');
            $form->password('password_virtual', 'Password');
            $form->color('color');
            $form->currency('currency')->symbol('$');
            $form->decimal('decimal');
            $form->number('number');
            $form->rate('rate');
            $form->slider('slider')->options(['min' => 0, 'max' => 100, 'step' => 1, 'postfix' => '%']);
            $form->textarea('textarea');
            $form->timezone('timezone');
            $form->divider('Divider');
            $form->html('<span class="label label-primary">custom html</span>', 'Html');
        });

        $form->fieldset('Choices', function (Form $form) {
            $form->select('select')->options(self::OPTIONS);
            $form->multipleSelect('multiple_select')->options(self::OPTIONS);
            $form->radio('radio')->options(self::OPTIONS);
            $form->radioButton('radio_button', 'Radio button')->options(self::OPTIONS);
            $form->radioCard('radio_card', 'Radio card')->options(self::OPTIONS);
            $form->checkbox('checkbox')->options(self::OPTIONS);
            $form->checkboxButton('checkbox_button', 'Checkbox button')->options(self::OPTIONS);
            $form->checkboxCard('checkbox_card', 'Checkbox card')->options(self::OPTIONS);
            $form->switch('switch');
            $form->listbox('listbox')->options(self::OPTIONS);
            $form->tags('tags');
            $form->icon('icon');
        });

        $form->fieldset('Dates', function (Form $form) {
            $form->date('date');
            $form->datetime('datetime');
            $form->time('time');
            $form->month('month');
            $form->year('year');
            $form->dateRange('date_start', 'date_end', 'Date range');
            $form->datetimeRange('datetime_start', 'datetime_end', 'Datetime range');
            $form->timeRange('time_start', 'time_end', 'Time range');
            $form->DateMultiple('date_multiple', 'Date multiple');
        });

        $form->fieldset('Uploads', function (Form $form) {
            $form->image('image');
            $form->file('file');
            $form->multipleImage('multiple_images', 'Multiple images');
            $form->multipleFile('multiple_files', 'Multiple files');
        });

        $form->fieldset('Complex', function (Form $form) {
            $form->keyValue('key_value', 'Key value');
            $form->list('list');
            $form->table('table', function ($table) {
                $table->text('key');
                $table->text('value');
            });
            $form->embeds('extra', 'Embeds', function ($form) {
                $form->text('a');
                $form->select('b')->options(self::OPTIONS);
            });
            $form->hasMany('items', 'Has many (default)', function (Form\NestedForm $form) {
                $form->text('name');
                $form->number('qty');
            });
            $form->hasMany('items', 'Has many (table)', function (Form\NestedForm $form) {
                $form->text('name');
                $form->number('qty');
            })->useTable();
        });

        return $form;
    }

    public function widgets(Content $content)
    {
        return $content
            ->title('Widgets')
            ->description('Every widget in Encore\Admin\Widgets')
            ->row(function (Row $row) {
                $row->column(3, new Widgets\InfoBox('Users', 'users', 'aqua', '#', '1024'));
                $row->column(3, new Widgets\InfoBox('Orders', 'shopping-cart', 'green', '#', '150'));
                $row->column(3, new Widgets\InfoBox('Visits', 'eye', 'yellow', '#', '2,048'));
                $row->column(3, new Widgets\InfoBox('Errors', 'warning', 'red', '#', '3'));
            })
            ->row(function (Row $row) {
                $row->column(4, function (Column $column) {
                    $column->append(new Widgets\Callout('Callout content', 'Callout info', 'info'));
                    $column->append(new Widgets\Callout('Callout content', 'Callout warning', 'warning'));
                    $column->append((new Widgets\Alert('Alert content', 'Alert success'))->style('success')->icon('check'));
                    $column->append((new Widgets\Alert('Alert content', 'Alert danger'))->style('danger')->icon('ban'));
                });
                $row->column(4, function (Column $column) {
                    $column->append((new Widgets\Box('Box collapsable + removable', 'Box content'))->collapsable()->removable()->style('primary')->solid());
                    $column->append((new Widgets\Box('Box with footer', 'Box content', 'Box footer'))->style('success'));
                    $collapse = new Widgets\Collapse();
                    $collapse->add('Collapse one', 'Content one');
                    $collapse->add('Collapse two', 'Content two');
                    $column->append($collapse);
                });
                $row->column(4, function (Column $column) {
                    $tab = new Widgets\Tab();
                    $tab->add('Tab one', 'Tab one content', true);
                    $tab->add('Tab two', 'Tab two content');
                    $tab->dropDown([['Dropdown link', '#']]);
                    $tab->title('Tab widget');
                    $column->append($tab);
                    $column->append(new Widgets\Box('Table widget', new Widgets\Table(['Name', 'Value'], [
                        ['PHP', PHP_VERSION],
                        ['Laravel', app()->version()],
                    ])));
                });
            })
            ->row(function (Row $row) {
                $form = new Widgets\Form();
                $form->action('#');
                $form->text('name')->required();
                $form->email('email');
                $form->select('select')->options(self::OPTIONS);
                $form->switch('switch');
                $form->datetime('datetime');
                $row->column(6, new Widgets\Box('Widgets\Form', $form));
                $row->column(6, new Widgets\Box('Buttons & labels', implode(' ', [
                    '<button class="btn btn-secondary">secondary</button>',
                    '<button class="btn btn-primary">primary</button>',
                    '<button class="btn btn-success btn-sm">success sm</button>',
                    '<button class="btn btn-outline-danger btn-sm">outline danger sm</button>',
                    '<div class="btn-group"><button class="btn btn-info">group</button><button class="btn btn-info dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button><ul class="dropdown-menu"><li><a class="dropdown-item" href="#">Item</a></li></ul></div>',
                    '<br><br>',
                    '<span class="badge text-bg-secondary">secondary</span>',
                    '<span class="badge text-bg-primary">primary</span>',
                    '<span class="badge text-bg-success">success</span>',
                    '<span class="badge rounded-pill text-bg-danger">pill</span>',
                    '<br><br><div class="progress"><div class="progress-bar progress-bar-striped" style="width:60%"></div></div>',
                ])));
            });
    }
}
