<div class="form-group mb-3">
    <label class="form-label">{{ $label }}</label>
    <input style="width: 100%" {!! $attributes !!} />
    @include('admin::actions.form.help-block')
</div>