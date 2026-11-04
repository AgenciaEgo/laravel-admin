<div class="card-footer">

    {{ csrf_field() }}

    <div class="row">
        <div class="col-md-{{$width['label']}}">
        </div>

        <div class="col-md-{{$width['field']}} d-flex align-items-center">

            @if(in_array('reset', $buttons))
            <div class="btn-group">
                <button type="reset" class="btn btn-warning">{{ trans('admin.reset') }}</button>
            </div>
            @endif

            @if(in_array('submit', $buttons))
            <div class="ms-auto d-flex flex-row-reverse align-items-center gap-3">
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">{{ trans('admin.submit') }}</button>
                </div>

                @foreach($submit_redirects as $value => $redirect)
                    @if(in_array($redirect, $checkboxes))
                    <div class="form-check mb-0">
                        <input type="checkbox" class="form-check-input after-submit" id="after-save-{{ $value }}" name="after-save" value="{{ $value }}" {{ ($default_check == $redirect) ? 'checked' : '' }}>
                        <label class="form-check-label" for="after-save-{{ $value }}">{{ trans("admin.{$redirect}") }}</label>
                    </div>
                    @endif
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
