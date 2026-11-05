<div class="card grid-box mb-3">
    @if(isset($title))
    <div class="card-header">
        <h3 class="card-title"> {{ $title }}</h3>
    </div>
    @endif

    @if ( $grid->showTools() || $grid->showExportBtn() || $grid->showCreateBtn() )
    <div class="card-header d-flex flex-wrap align-items-center gap-2 grid-tools">
        @if ( $grid->showTools() )
        <div class="d-flex flex-wrap align-items-center gap-1">
            {!! $grid->renderHeaderTools() !!}
        </div>
        @endif
        {{-- flex-row-reverse keeps the order of the 1.x right floats --}}
        <div class="ms-auto d-flex flex-row-reverse flex-wrap align-items-center gap-1">
            {!! $grid->renderColumnSelector() !!}
            {!! $grid->renderExportButton() !!}
            {!! $grid->renderCreateButton() !!}
        </div>
    </div>
    @endif

    {!! $grid->renderFilter() !!}

    {!! $grid->renderHeader() !!}

    <div class="card-body table-responsive p-0">
        <table class="table table-hover grid-table" id="{{ $grid->tableID }}">
            <thead>
                <tr>
                    @foreach($grid->visibleColumns() as $column)
                    <th {!! $column->formatHtmlAttributes() !!}>{!! $column->getLabel() !!}{!! $column->renderHeader() !!}</th>
                    @endforeach
                </tr>
            </thead>

            @if ($grid->hasQuickCreate())
                {!! $grid->renderQuickCreate() !!}
            @endif

            <tbody>

                @if($grid->rows()->isEmpty() && $grid->showDefineEmptyPage())
                    @include('admin::grid.empty-grid')
                @endif

                @foreach($grid->rows() as $row)
                <tr {!! $row->getRowAttributes() !!}>
                    @foreach($grid->visibleColumnNames() as $name)
                    <td {!! $row->getColumnAttributes($name) !!}>
                        {!! $row->column($name) !!}
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>

            {!! $grid->renderTotalRow() !!}

        </table>

    </div>

    {!! $grid->renderFooter() !!}

    <div class="card-footer d-flex flex-wrap align-items-center gap-2">
        {!! $grid->paginator() !!}
    </div>
</div>
