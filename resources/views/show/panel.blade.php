<div class="card {{ $styleClasses }} mb-3">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>

        <div class="card-tools">
            {!! $tools !!}
        </div>
    </div>

    <div class="card-body">
        <div class="fields-group">
            @foreach($fields as $field)
                {!! $field->render() !!}
            @endforeach
        </div>
    </div>
</div>
