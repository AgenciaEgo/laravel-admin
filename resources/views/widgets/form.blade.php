<form {!! $attributes !!}>
    <div class="card-body fields-group">

        @foreach($fields as $field)
            {!! $field->render() !!}
        @endforeach

    </div>

    @if ($method != 'GET')
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
    @endif
    
    @if(count($buttons) > 0)
    <div class="card-footer">
        <div class="row">
            <div class="col-md-{{$width['label']}}"></div>

            <div class="col-md-{{$width['field']}} d-flex">
                @if(in_array('reset', $buttons))
                <div class="btn-group">
                    <button type="reset" class="btn btn-warning">{{ trans('admin.reset') }}</button>
                </div>
                @endif

                @if(in_array('submit', $buttons))
                <div class="btn-group ms-auto">
                    <button type="submit" class="btn btn-info">{{ trans('admin.submit') }}</button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</form>
