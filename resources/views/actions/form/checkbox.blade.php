<div class="form-group">
    <label>{{ $label }}</label>
    <div>
    @foreach($options as $option => $label)
        <div class="form-check form-check-inline">
            <label class="form-check-label">
                <input type="checkbox" name="{{$name}}[]" value="{{$option}}" class="form-check-input {{$class}}" {{ in_array($option, (array)old($column, $value)) || ($value === null && in_array($label, $checked)) ?'checked':'' }} {!! $attributes !!} />{{$label}}
            </label>
        </div>
    @endforeach
    </div>
    <input type="hidden" name="{{$name}}[]">
    @include('admin::actions.form.help-block')
</div>
