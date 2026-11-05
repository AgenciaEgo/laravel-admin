@if(!$holdAll)
<div class="btn-group {{ $all }}-btn" style="display:none;">
    <a class="btn btn-sm btn-outline-secondary d-none d-sm-inline-block"><span class="selected"></span></a>
    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
        <span class="visually-hidden">Toggle Dropdown</span>
    </button>
    @if(!$actions->isEmpty())
    <ul class="dropdown-menu" role="menu">
        @foreach($actions as $action)
            @if($action instanceof \Encore\Admin\Actions\BatchAction)
                <li>{!! $action->render() !!}</li>
            @else
                <li><a href="#" class="dropdown-item {{ $action->getElementClass(false) }}">{!! $action->render() !!} </a></li>
            @endif
        @endforeach
    </ul>
    @endif
</div>
@endif

<script>
// the row checkboxes keep the selection and the button in sync on their own 'change'
$('.{{ $all }}').on('change', function () {
    $('.{{ $row }}-checkbox').laCheck(this.checked);
});
</script>
