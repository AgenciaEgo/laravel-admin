<div class="btn-group">
    <a href="" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#{{ $modalID }}"><i class="fa fa-filter"></i>&nbsp;&nbsp;{{ trans('admin.filter') }}</a>
    <a href="{!! $action !!}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-undo"></i>&nbsp;&nbsp;{{ trans('admin.reset') }}</a>
</div>

<div class="modal fade" id="{{ $modalID }}" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">{{ trans('admin.filter') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{!! $action !!}" method="get" pjax-container>
                <div class="modal-body">
                    <div class="form">
                        @foreach($filters as $filter)
                            <div class="mb-3">
                                {!! $filter->render() !!}
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="reset" class="btn btn-warning me-auto">{{ trans('admin.reset') }}</button>
                    <button type="submit" class="btn btn-primary submit">{{ trans('admin.submit') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>