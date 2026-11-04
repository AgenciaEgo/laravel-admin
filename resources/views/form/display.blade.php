<div class="{{$viewClass['form-group']}}">
    <label class="{{$viewClass['label']}}">{{$label}}</label>
    <div class="{{$viewClass['field']}}">
        <div class="card m-0">
                        <div class="card-body py-2">
                {!! $value !!}&nbsp;
            </div>
        </div>

        @include('admin::form.help-block')

    </div>
</div>