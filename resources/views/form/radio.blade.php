<div class="{{$viewClass['form-group']}} {!! !$errors->has($errorKey) ? '' : 'has-error' !!}">

    <label for="{{$id}}" class="{{$viewClass['label']}}">{{$label}}</label>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')

        <div class="la-checks">
        @foreach($options as $option => $label)
            <div class="form-check {{ $inline ? 'form-check-inline' : '' }}">
                <label class="form-check-label">
                    <input type="radio" name="{{$name}}" value="{{$option}}" class="form-check-input {{$class}}" {{ ($option == old($column, $value)) || ($value === null && in_array($label, $checked)) ?'checked':'' }} {!! $attributes !!} />{{$label}}
                </label>
            </div>
        @endforeach
        </div>

        @include('admin::form.help-block')

    </div>
</div>
