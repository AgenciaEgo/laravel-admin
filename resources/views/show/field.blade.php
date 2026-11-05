<div class="form-group row mb-3">
    <label class="col-sm-{{$width['label']}} col-form-label text-sm-end">{{ $label }}</label>
    <div class="col-sm-{{$width['field']}}">
        @if($wrapped)
        <div class="card m-0 box-show">
            <div class="card-body py-2">
                @if($escape)
                    {{ $content }}&nbsp;
                @else
                    {!! $content !!}&nbsp;
                @endif
            </div>
        </div>
        @else
            <div class="col-form-label">
            @if($escape)
                {{ $content }}
            @else
                {!! $content !!}
            @endif
            </div>
        @endif
    </div>
</div>
