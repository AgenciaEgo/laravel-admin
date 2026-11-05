{{--inline edit popover--}}

<span class="ie-wrap">
    <a
        href="javascript:void(0);"
        class="{{ $trigger }} la-ie-trigger"
        data-target="{{ $target }}"
        data-value="{{ $value }}"
        data-original="{{ $value }}"
        data-key="{{ $key }}"
        data-name="{{ $name }}"
    >
        <span class="ie-display">{{ $display }}</span>

        <i class="fa fa-edit" style="visibility: hidden;"></i>
    </a>
</span>

<template>
    <template id="{{ $target }}">
        <div class="ie-content ie-content-{{ $name }}" data-trigger="{{ $trigger }}">
            <div class="ie-container">
                @yield('field')
                <div class="error"></div>
            </div>
            <div class="ie-action">
                <button class="btn btn-primary btn-sm ie-submit">{{ __('admin.submit') }}</button>
                <button class="btn btn-outline-secondary btn-sm ie-cancel">{{ __('admin.cancel') }}</button>
            </div>
        </div>
    </template>
</template>

<style>
    .ie-wrap>a {
        padding: 3px;
        border-radius: 3px;
        color:#777;
    }

    .ie-wrap>a:hover {
        text-decoration: none;
        background-color: #ddd;
        color:#777;
    }

    .ie-wrap>a:hover i,
    .ie-wrap .ie-display:empty + i {
        visibility: visible !important;
    }

    .ie-action button {
        margin: 10px 0 10px 10px;
        float: right;
    }

    .ie-container  {
        width: 250px;
        position: relative;
    }

    .ie-container .error {
        color: #dd4b39;
        font-weight: 700;
    }
</style>

<script>
    $(document).off('click.la-ie', '.ie-action .ie-cancel').on('click.la-ie', '.ie-action .ie-cancel', function () {
        $.admin.inlineEdit.hide();
    });

    $('body').off('click.la-ie').on('click.la-ie', function (e) {
        if ($(e.target).closest('.la-ie-trigger, .popover.show, .flatpickr-calendar').length === 0) {
            $.admin.inlineEdit.hide();
        }
    });
</script>

@yield('assert')
