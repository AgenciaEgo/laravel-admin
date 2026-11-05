<div class="dropdown column-selector">
    <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">
        <i class="fa fa-table"></i>
        &nbsp;
    </button>
    <ul class="dropdown-menu" role="menu">
        <li>
            <ul>
                @foreach($columns as $key => $label)
                @php
                if (empty($visible)) {
                    $checked = 'checked';
                } else {
                    $checked = in_array($key, $visible) ? 'checked' : '';
                }
                @endphp

                <li class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input column-select-item" value="{{ $key }}" {{ $checked }}/>{{ $label }}
                    </label>
                </li>
                @endforeach
            </ul>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li class="text-end">
            <button class="btn btn-sm btn-outline-secondary column-select-all">{{ __('admin.all') }}</button>&nbsp;&nbsp;
            <button class="btn btn-sm btn-primary column-select-submit">{{ __('admin.submit') }}</button>
        </li>
    </ul>
</div>

<style>
.column-selector .dropdown-menu {
    padding: 10px;
    height: auto;
    max-height: 500px;
    overflow-x: hidden;
}

.column-selector .dropdown-menu ul {
    padding: 0;
}

.column-selector .dropdown-menu ul li {
    margin: 0;
}

.column-selector .dropdown-menu label {
    width: 100%;
    padding: 3px;
}
</style>

<script>
$('.column-select-submit').on('click', function () {

    var defaults = @json($defaults);
    var selected = [];

    $('.column-select-item:checked').each(function () {
        selected.push($(this).val());
    });

    if (selected.length == 0) {
        return;
    }

    var url = new URL(location);

    if (selected.sort().toString() == defaults.sort().toString()) {
        url.searchParams.delete('_columns_');
    } else {
        url.searchParams.set('_columns_', selected.join());
    }

    $.pjax({container:'#pjax-container', url: url.toString()});
});

$('.column-select-all').on('click', function () {
    $('.column-select-item').prop('checked', true);
    return false;
});

</script>
