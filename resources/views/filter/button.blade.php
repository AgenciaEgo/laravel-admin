<div class="btn-group">
    {{-- with $offcanvas the button opens the filter drawer; "active" while filters are applied --}}
    <button type="button" class="btn btn-sm btn-primary {{ $btn_class }} {{ $expand ? 'active' : '' }}" title="{{ trans('admin.filter') }}"
        @if($offcanvas) data-bs-toggle="offcanvas" data-bs-target="#{{ $filter_id }}" aria-controls="{{ $filter_id }}" @endif>
        <i class="fa fa-filter"></i><span class="d-none d-sm-inline">&nbsp;&nbsp;{{ trans('admin.filter') }}</span>
    </button>

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

@if($offcanvas)
{!! $offcanvas !!}
@else
<script>
var $btn = $('.{{ $btn_class }}');
var $filter = $('#{{ $filter_id }}');

$btn.off('click').on('click', function (e) {
    $filter.toggleClass('d-none');
    $btn.toggleClass('active', !$filter.hasClass('d-none'));
});
</script>
@endif
