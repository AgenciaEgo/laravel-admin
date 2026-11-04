<div class="la-checks">
    @foreach($options as $option => $label)
        <div class="form-check {{ $inline ? 'form-check-inline' : '' }}">
            <label class="form-check-label">
                <input type="checkbox" class="form-check-input {{$id}}" name="{{$name}}[]" value="{{$option}}" {{ in_array((string)$option, request($name, is_null($value) ? [] : $value)) ? 'checked' : '' }} />{{$label}}
            </label>
        </div>
    @endforeach
</div>
