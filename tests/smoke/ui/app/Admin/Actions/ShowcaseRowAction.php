<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ShowcaseRowAction extends RowAction
{
    public $name = 'Row action (form)';

    public function handle(Model $model, Request $request)
    {
        return $this->response()->success('Row action: '.$request->input('note'))->refresh();
    }

    public function form()
    {
        $this->text('note', 'Note')->rules('required');
        $this->select('kind', 'Kind')->options(['a' => 'Alpha', 'b' => 'Beta']);
        $this->date('when', 'When');
        $this->textarea('comment', 'Comment');
    }
}
