<div class="btn-group">
    @if($dropdown)
    {{-- the filter form is this button's dropdown menu; "active" while filters are applied --}}
    <button type="button" class="btn btn-sm btn-primary {{ $btn_class }} {{ $expand ? 'active' : '' }}" title="{{ trans('admin.filter') }}" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        <i class="fa fa-filter"></i><span class="d-none d-sm-inline">&nbsp;&nbsp;{{ trans('admin.filter') }}</span>
    </button>
    {!! $dropdown !!}
    @else
    <button type="button" class="btn btn-sm btn-primary {{ $btn_class }} {{ $expand ? 'active' : '' }}" title="{{ trans('admin.filter') }}">
        <i class="fa fa-filter"></i><span class="d-none d-sm-inline">&nbsp;&nbsp;{{ trans('admin.filter') }}</span>
    </button>
    @endif

    @if($scopes->isNotEmpty())
    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
        <span>{{ $label }}</span>
    </button>
    <ul class="dropdown-menu" role="menu">
        @foreach($scopes as $scope)
            {!! $scope->render() !!}
        @endforeach
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="{{ $cancel }}">{{ trans('admin.cancel') }}</a></li>
    </ul>
    @endif
</div>

@unless($dropdown)
<script>
var $btn = $('.{{ $btn_class }}');
var $filter = $('#{{ $filter_id }}');

$btn.off('click').on('click', function (e) {
    $filter.toggleClass('d-none');
    $btn.toggleClass('active', !$filter.hasClass('d-none'));
});
</script>
@endunless
