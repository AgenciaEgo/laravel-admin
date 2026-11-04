<div {!! $attributes !!}>
    @foreach($items as $key => $item)
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button {{ $key == 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse"
                    data-bs-target="#{{ $id }}-{{ $key }}" aria-expanded="{{ $key == 0 ? 'true' : 'false' }}" aria-controls="{{ $id }}-{{ $key }}">
                {{ $item['title'] }}
            </button>
        </h2>
        <div id="{{ $id }}-{{ $key }}" class="accordion-collapse collapse {{ $key == 0 ? 'show' : '' }}" data-bs-parent="#{{ $id }}">
            <div class="accordion-body">
                {!! $item['content'] !!}
            </div>
        </div>
    </div>
    @endforeach
</div>
