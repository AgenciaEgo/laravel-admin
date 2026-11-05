<div class="form-group mb-3">
    <label class="form-label">{{ $label }}</label>
    <div>
    @foreach($options as $option => $label)
        <div class="form-check form-check-inline">
            <label class="form-check-label">
                <input type="radio" name="{{$name}}" value="{{$option}}" class="form-check-input {{$class}}" {{ ($option == old($column, $value)) || ($value === null && in_array($label, $checked)) ?'checked':'' }} {!! $attributes !!} />{{$label}}
            </label>
        </div>
    @endforeach
    </div>
    @include('admin::actions.form.help-block')
</div>
