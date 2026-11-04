<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class ShowcaseBatchAction extends BatchAction
{
    public $name = 'Batch action (confirm)';

    public function handle(Collection $collection)
    {
        return $this->response()->success('Batch action on '.$collection->count().' rows')->refresh();
    }

    public function dialog()
    {
        $this->confirm('Apply to the selected rows?');
    }
}
