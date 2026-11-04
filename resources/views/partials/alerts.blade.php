@php
    // flashed as plain arrays by admin_success() & co, older sessions may still hold a MessageBag
    $flash = function ($value, $key) {
        $value = $value instanceof \Illuminate\Support\MessageBag ? $value->toArray() : (array) $value;

        return \Illuminate\Support\Arr::first(\Illuminate\Support\Arr::wrap(\Illuminate\Support\Arr::get($value, $key)));
    };
@endphp
@if($error = session()->get('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <h5><i class="icon fa fa-ban"></i>{{ $flash($error, 'title') }}</h5>
        <p>{!!  $flash($error, 'message') !!}</p>
    </div>
@elseif ($errors = session()->get('errors'))
    @if ($errors->hasBag('error'))
      <div class="alert alert-danger alert-dismissible fade show">

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        @foreach($errors->getBag("error")->toArray() as $message)
            <p>{!!  \Illuminate\Support\Arr::get($message, 0) !!}</p>
        @endforeach
      </div>
    @endif
@endif

@if($success = session()->get('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <h5><i class="icon fa fa-check"></i>{{ $flash($success, 'title') }}</h5>
        <p>{!!  $flash($success, 'message') !!}</p>
    </div>
@endif

@if($info = session()->get('info'))
    <div class="alert alert-info alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <h5><i class="icon fa fa-info"></i>{{ $flash($info, 'title') }}</h5>
        <p>{!!  $flash($info, 'message') !!}</p>
    </div>
@endif

@if($warning = session()->get('warning'))
    <div class="alert alert-warning alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <h5><i class="icon fa fa-warning"></i>{{ $flash($warning, 'title') }}</h5>
        <p>{!!  $flash($warning, 'message') !!}</p>
    </div>
@endif