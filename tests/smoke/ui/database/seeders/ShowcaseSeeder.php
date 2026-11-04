<?php

namespace Database\Seeders;

use App\Models\Showcase;
use Encore\Admin\Auth\Database\Menu;
use Illuminate\Database\Seeder;

class ShowcaseSeeder extends Seeder
{
    public function run()
    {
        foreach (['First', 'Second', 'Third'] as $i => $title) {
            $showcase = Showcase::create([
                'title'           => "$title showcase row",
                'email'           => "row$i@example.com",
                'url'             => 'https://example.com/'.$i,
                'ip'              => "192.168.0.$i",
                'mobile'          => '15012345678',
                'color'           => '#3c8dbc',
                'currency'        => 1234.5 * ($i + 1),
                'decimal'         => 12.3 * ($i + 1),
                'number'          => 10 * ($i + 1),
                'rate'            => 12.5,
                'slider'          => 40,
                'select'          => ['a', 'b', 'c'][$i],
                'multiple_select' => ['a', 'b'],
                'radio'           => 'b',
                'checkbox'        => ['a', 'c'],
                'switch'          => $i % 2 === 0,
                'date'            => '2026-10-1'.$i,
                'datetime'        => '2026-10-1'.$i.' 10:30:00',
                'time'            => '10:30:00',
                'month'           => '10',
                'year'            => '2026',
                'date_start'      => '2026-10-01',
                'date_end'        => '2026-10-31',
                'datetime_start'  => '2026-10-01 08:00:00',
                'datetime_end'    => '2026-10-31 18:00:00',
                'time_start'      => '08:00:00',
                'time_end'        => '18:00:00',
                'date_multiple'   => '2026-10-01,2026-10-05',
                'icon'            => 'fa-star',
                'tags'            => ['red', 'green'],
                'listbox'         => ['a'],
                'textarea'        => 'A longer text that the grid will truncate with limit().',
                'image'           => 'images/sample.jpg',
                'file'            => 'files/sample.txt',
                'multiple_images' => ['images/sample.jpg'],
                'multiple_files'  => ['files/sample.txt'],
                'key_value'       => ['color' => 'blue', 'size' => 'XL'],
                'list'            => ['one', 'two'],
                'table'           => [['key' => 'k1', 'value' => 'v1'], ['key' => 'k2', 'value' => 'v2']],
                'extra'           => ['a' => 'embedded', 'b' => 'c'],
                'timezone'        => 'America/Argentina/Buenos_Aires',
                'progress'        => 30 * ($i + 1),
            ]);
            $showcase->items()->createMany([['name' => 'Item A', 'qty' => 1], ['name' => 'Item B', 'qty' => 2]]);
        }

        $parent = Menu::create(['parent_id' => 0, 'order' => 10, 'title' => 'UI showcase', 'icon' => 'fa-paint-brush', 'uri' => '']);
        Menu::create(['parent_id' => $parent->id, 'order' => 11, 'title' => 'Showcase CRUD', 'icon' => 'fa-table', 'uri' => 'showcases']);
        Menu::create(['parent_id' => $parent->id, 'order' => 12, 'title' => 'Widgets', 'icon' => 'fa-th', 'uri' => 'showcase-widgets']);
    }
}
