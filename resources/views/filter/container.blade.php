@if($offcanvas ?? false)
{{-- drawer opened by the filter button (filter/button.blade.php): one column, labels above the fields --}}
<div class="offcanvas offcanvas-end filter-box filter-offcanvas" tabindex="-1" id="{{ $filterID }}" aria-labelledby="{{ $filterID }}-title">
    <form action="{!! $action !!}" class="d-flex flex-column h-100" pjax-container method="get">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="{{ $filterID }}-title">{{ trans('admin.filters') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ trans('admin.close') }}"></button>
        </div>

        <div class="offcanvas-body">
            @foreach($layout->columns() as $column)
                @foreach($column->filters() as $filter)
                    {!! $filter->render() !!}
                @endforeach
            @endforeach
        </div>

        <div class="border-top p-3 d-flex gap-2">
            <button class="btn btn-success submit">{{ trans('admin.apply') }}</button>
            <a href="{!! $action !!}" class="btn btn-outline-secondary"><i class="fa fa-undo"></i>&nbsp;&nbsp;{{ trans('admin.reset') }}</a>
        </div>
    </form>
</div>
@else
{{-- panel above the table, for grid views that do not render the header tools --}}
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
@endif
