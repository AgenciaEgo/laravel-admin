<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" data-bs-theme="{{ config('admin.color_mode', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ Admin::title() }} @if($header) | {{ $header }}@endif</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @if(!is_null($favicon = Admin::favicon()))
    <link rel="shortcut icon" href="{{$favicon}}">
    @endif

    {!! Admin::css() !!}

    <script src="{{ Admin::jQuery() }}"></script>
    @if($jQueryMigrate = Admin::jQueryMigrate())
    <script src="{{ $jQueryMigrate }}"></script>
    @endif
    {!! Admin::headerJs() !!}
</head>

@php($theme = Admin::theme())
<body class="{{ $theme['body'] }}">

@if($alert = config('admin.top_alert'))
    <div class="text-center small py-1 text-danger" style="background-color: #ffffd5;">
        {!! $alert !!}
    </div>
@endif

<div class="app-wrapper">

    @include('admin::partials.header')

    @include('admin::partials.sidebar')

    <main class="app-main" id="pjax-container">
        {!! Admin::style() !!}
        <div id="app">
        @yield('content')
        </div>
        {!! Admin::script() !!}
        {!! Admin::html() !!}
    </main>

    @include('admin::partials.footer')

</div>

<button id="totop" title="Go to top" style="display: none;"><i class="fa fa-chevron-up"></i></button>

<script>
    function LA() {}
    LA.token = "{{ csrf_token() }}";
    LA.user = @json($_user_);
</script>

<!-- REQUIRED JS SCRIPTS -->
{!! Admin::js() !!}

</body>
</html>
