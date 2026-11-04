<div class="{{$viewClass['form-group']}} {!! !$errors->has($errorKey) ? '' : 'has-error' !!}">

    <label for="{{$id}}" class="{{$viewClass['label']}}">{{$label}}</label>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')

        <div class="form-check form-switch la-switch">
            <input type="checkbox" role="switch" id="{{$id}}" class="form-check-input {{$class}} la_checkbox {{ $switchColors }}" {{ old($column, $value) == 'on' ? 'checked' : '' }} {!! $attributes !!} />
        </div>
        <input type="hidden" class="{{$class}}" name="{{$name}}" value="{{ old($column, $value) }}" />

        @include('admin::form.help-block')

    </div>
</div>
