<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Showcase extends Model
{
    protected $guarded = [];

    protected $casts = [
        'multiple_select' => 'array',
        'checkbox'        => 'array',
        'tags'            => 'array',
        'listbox'         => 'array',
        'multiple_images' => 'array',
        'multiple_files'  => 'array',
        'key_value'       => 'json',
        'list'            => 'json',
        'table'           => 'json',
        'extra'           => 'json',
        'switch'          => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(ShowcaseItem::class);
    }
}
