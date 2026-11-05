<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" data-bs-theme="{{ config('admin.color_mode', 'light') }}">
<head>
  <meta charset="utf-8">
  <title>{{config('admin.title')}} | {{ trans('admin.login') }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  @if(!is_null($favicon = Admin::favicon()))
  <link rel="shortcut icon" href="{{$favicon}}">
  @endif

  <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/AdminLTE4/css/adminlte.min.css") }}">
  <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/fontawesome6/css/all.min.css") }}">
  <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/fontawesome6/css/v4-shims.min.css") }}">
  <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/google-fonts/fonts.css") }}">
  @include('admin::partials.accent')
  <style>html { font-size: 15px; } .login-logo a { color: var(--la-accent, var(--bs-link-color)); }</style>
</head>
<body class="login-page bg-body-secondary" @if(config('admin.login_background_image'))style="background: url({{config('admin.login_background_image')}}) no-repeat;background-size: cover;"@endif>
<div class="login-box">
  <div class="login-logo">
    <a href="{{ admin_url('/') }}" class="text-decoration-none"><b>{{config('admin.name')}}</b></a>
  </div>

  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">{{ trans('admin.login') }}</p>

      <form action="{{ admin_url('auth/login') }}" method="post">
        @foreach(['username' => ['text', 'fa-user'], 'password' => ['password', 'fa-lock']] as $field => [$type, $icon])
          <div class="mb-3">
            @foreach($errors->get($field) as $message)
              <div class="text-danger small mb-1"><i class="fa fa-times-circle-o"></i> {{ $message }}</div>
            @endforeach
            <div class="input-group">
              <input type="{{ $type }}" class="form-control {{ $errors->has($field) ? 'is-invalid' : '' }}" placeholder="{{ trans("admin.$field") }}" name="{{ $field }}" @if($field === 'username') value="{{ old('username') }}" @endif>
              <div class="input-group-text"><i class="fa {{ $icon }}"></i></div>
            </div>
          </div>
        @endforeach

        <div class="row align-items-center">
          <div class="col-8">
            @if(config('admin.auth.remember'))
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember" {{ (!old('username') || old('remember')) ? 'checked' : '' }}>
              <label class="form-check-label" for="remember">{{ trans('admin.remember_me') }}</label>
            </div>
            @endif
          </div>
          <div class="col-4">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="d-grid">
              <button type="submit" class="btn btn-primary">{{ trans('admin.login') }}</button>
            </div>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>

<script src="{{ admin_asset("vendor/laravel-admin/bootstrap5/js/bootstrap.bundle.min.js")}}"></script>
<script src="{{ admin_asset("vendor/laravel-admin/AdminLTE4/js/adminlte.min.js")}}"></script>
</body>
</html>
