<div class="filter-box border-bottom {{ $expand ? '' : 'd-none' }}" id="{{ $filterID }}">
    <form action="{!! $action !!}" class="p-3" pjax-container method="get">

        <div class="row">
            @foreach($layout->columns() as $column)
            <div class="col-md-{{ $column->width() }}">
                <div class="fields-group">
                    @foreach($column->filters() as $filter)
                        {!! $filter->render() !!}
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-md-{{ $layout->columns()->first()->width() }}">
                <div class="row">
                    <div class="col-sm-2"></div>
                    <div class="col-sm-8 d-flex gap-2">
                        <button class="btn btn-info submit btn-sm"><i class="fa fa-search"></i>&nbsp;&nbsp;{{ trans('admin.search') }}</button>
                        <a href="{!! $action !!}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-undo"></i>&nbsp;&nbsp;{{ trans('admin.reset') }}</a>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>
