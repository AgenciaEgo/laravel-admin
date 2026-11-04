<aside class="app-sidebar {{ $theme['sidebar'] }} shadow" data-bs-theme="{{ $theme['sidebar_theme'] }}">

    <div class="sidebar-brand">
        <a href="{{ admin_url('/') }}" class="brand-link">
            <!-- shown when the sidebar is collapsed to icons -->
            <span class="brand-image brand-mini">{!! config('admin.logo-mini', config('admin.name')) !!}</span>
            <span class="brand-text">{!! config('admin.logo', config('admin.name')) !!}</span>
        </a>
    </div>

    <div class="sidebar-wrapper">

        @if(config('admin.enable_menu_search'))
        <div class="sidebar-search px-3 pt-3">
            <input type="search" class="form-control form-control-sm" autocomplete="off"
                   data-lte-toggle="sidebar-search" placeholder="{{ __('Search...') }}" aria-label="{{ __('Search...') }}">
            <p class="small text-secondary mt-2 mb-0" data-lte-search-empty hidden>{{ __('No results') }}</p>
        </div>
        @endif

        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation" data-accordion="false">
                <li class="nav-header">{{ trans('admin.menu') }}</li>

                @each('admin::partials.menu', Admin::menu(), 'item')

            </ul>
        </nav>
    </div>
</aside>
