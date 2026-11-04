<style>
    .ext-icon {
        color: var(--bs-secondary-color);
        width: 2.5rem;
        text-align: center;
    }
    .installed {
        color: var(--bs-success);
    }
</style>
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Available extensions</h3>

        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                <i data-lte-icon="expand" class="fa fa-plus"></i><i data-lte-icon="collapse" class="fa fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-lte-toggle="card-remove"><i class="fa fa-times"></i></button>
        </div>
    </div>

    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            @foreach($extensions as $extension)
            <li class="list-group-item d-flex align-items-center">
                <i class="fa fa-{{$extension['icon']}} fa-2x ext-icon me-3"></i>
                <a href="{{ $extension['link'] }}" target="_blank" class="text-decoration-none">
                    {{ $extension['name'] }}
                </a>
                @if($extension['installed'])
                    <span class="ms-auto installed"><i class="fa fa-check"></i></span>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
    <div class="card-footer text-center">
        <a href="https://github.com/laravel-admin-extensions" target="_blank" class="text-uppercase text-decoration-none">View All Extensions</a>
    </div>
</div>