<thead>
<tr class="quick-create">
    <td colspan="{{ $columnCount }}" style="height: 47px;padding-left: 57px;background-color: var(--bs-tertiary-bg); vertical-align: middle;">

        <span class="create text-body-secondary" style="cursor: pointer;display: block;">
             <i class="fa fa-plus"></i>&nbsp;{{ __('admin.quick_create') }}
        </span>

        <form class="create-form flex-wrap align-items-center gap-2" style="display: none;" method="post">
            @foreach($fields as $field)
                {!! $field->render() !!}
            @endforeach
            <button class="btn btn-primary btn-sm">{{ __('admin.submit') }}</button>
            <a href="javascript:void(0);" class="cancel">{{ __('admin.cancel') }}</a>
            {{ csrf_field() }}
        </form>
    </td>
</tr>
</thead>