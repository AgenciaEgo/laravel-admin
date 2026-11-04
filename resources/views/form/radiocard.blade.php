<div class="{{$viewClass['form-group']}} {!! !$errors->has($errorKey) ? '' : 'has-error' !!}">

    <label for="{{$id}}" class="{{$viewClass['label']}}">{{$label}}</label>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')

        <div class="la-card-group d-flex flex-wrap gap-2 radio-group-toggle">
            @foreach($options as $option => $label)
                <label class="card {{ ($option == old($column, $value)) || ($value === null && in_array($label, $checked)) ?'active':'' }}">
                    <div class="card-body">
                    <input type="radio" name="{{$name}}" value="{{$option}}" class="d-none minimal {{$class}}" {{ ($option == old($column, $value)) || ($value === null && in_array($label, $checked)) ?'checked':'' }} {!! $attributes !!} />&nbsp;{{$label}}&nbsp;&nbsp;
                    </div>
                </label>
            @endforeach
        </div>

        @include('admin::form.help-block')

    </div>
</div>
