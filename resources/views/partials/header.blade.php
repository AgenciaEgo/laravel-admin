<!-- Main Header -->
<nav class="app-header navbar navbar-expand {{ $theme['header'] }}" data-bs-theme="{{ $theme['header_theme'] }}">
    <div class="container-fluid">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link sidebar-toggle" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="fa fa-bars"></i>
                    <span class="visually-hidden">Toggle navigation</span>
                </a>
            </li>
        </ul>
        <ul class="navbar-nav d-none d-lg-flex">
            {!! Admin::getNavbar()->render('left') !!}
        </ul>

        <ul class="navbar-nav ms-auto">

            {!! Admin::getNavbar()->render() !!}

            <!-- User Account Menu -->
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="{{ Admin::user()->avatar }}" class="user-image rounded-circle" alt="User Image">
                    <span class="d-none d-md-inline">{{ Admin::user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end" data-bs-theme="light">
                    <li class="user-header text-bg-primary">
                        <img src="{{ Admin::user()->avatar }}" class="rounded-circle" alt="User Image">
                        <p>
                            {{ Admin::user()->name }}
                            <small>Member since admin {{ Admin::user()->created_at }}</small>
                        </p>
                    </li>
                    <li class="user-footer">
                        <a href="{{ admin_url('auth/setting') }}" class="btn btn-outline-secondary">{{ trans('admin.setting') }}</a>
                        <a href="{{ admin_url('auth/logout') }}" class="btn btn-outline-secondary float-end">{{ trans('admin.logout') }}</a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
