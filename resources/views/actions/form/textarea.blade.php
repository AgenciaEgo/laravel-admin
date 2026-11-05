<div class="form-group mb-3">
    <label class="form-label">{{ $label }}</label>
    <textarea name="{{$name}}" class="form-control {{$class}}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" {!! $attributes !!} >{{ old($column, $value) }}</textarea>
    @include('admin::actions.form.help-block')
</div>