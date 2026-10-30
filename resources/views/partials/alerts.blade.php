@php
    // flashed as plain arrays by admin_success() & co, older sessions may still hold a MessageBag
    $flash = function ($value, $key) {
        $value = $value instanceof \Illuminate\Support\MessageBag ? $value->toArray() : (array) $value;

        return \Illuminate\Support\Arr::first(\Illuminate\Support\Arr::wrap(\Illuminate\Support\Arr::get($value, $key)));
    };
@endphp
@if($error = session()->get('error'))
    <div class="alert alert-danger alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <h4><i class="icon fa fa-ban"></i>{{ $flash($error, 'title') }}</h4>
        <p>{!!  $flash($error, 'message') !!}</p>
    </div>
@elseif ($errors = session()->get('errors'))
    @if ($errors->hasBag('error'))
      <div class="alert alert-danger alert-dismissable">

        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        @foreach($errors->getBag("error")->toArray() as $message)
            <p>{!!  \Illuminate\Support\Arr::get($message, 0) !!}</p>
        @endforeach
      </div>
    @endif
@endif

@if($success = session()->get('success'))
    <div class="alert alert-success alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <h4><i class="icon fa fa-check"></i>{{ $flash($success, 'title') }}</h4>
        <p>{!!  $flash($success, 'message') !!}</p>
    </div>
@endif

@if($info = session()->get('info'))
    <div class="alert alert-info alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <h4><i class="icon fa fa-info"></i>{{ $flash($info, 'title') }}</h4>
        <p>{!!  $flash($info, 'message') !!}</p>
    </div>
@endif

@if($warning = session()->get('warning'))
    <div class="alert alert-warning alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <h4><i class="icon fa fa-warning"></i>{{ $flash($warning, 'title') }}</h4>
        <p>{!!  $flash($warning, 'message') !!}</p>
    </div>
@endif