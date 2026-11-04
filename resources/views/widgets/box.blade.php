<div {!! $attributes !!}>
    @if($title || $tools)
        <div class="card-header">
            <h3 class="card-title">{{ $title }}</h3>
            <div class="card-tools">
                @foreach($tools as $tool)
                    {!! $tool !!}
                @endforeach
            </div>
        </div>
    @endif
    <div class="card-body">
        {!! $content !!}
    </div>
    @if($footer)
        <div class="card-footer">
            {!! $footer !!}
        </div>
    @endif
</div>
{{-- the box may be rendered inside an expand row after page load, so its script has to live next to the html --}}
<script>
    {!! $script !!}
</script>
