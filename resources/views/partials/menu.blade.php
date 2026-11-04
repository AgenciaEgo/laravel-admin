@if(Admin::user()->visible(\Illuminate\Support\Arr::get($item, 'roles', [])) && Admin::user()->can(\Illuminate\Support\Arr::get($item, 'permission')))
    @php($title = Lang::has($titleTranslation = 'admin.menu_titles.' . trim(str_replace(' ', '_', strtolower($item['title'])))) ? __($titleTranslation) : admin_trans($item['title']))
    @if(!isset($item['children']))
        <li class="nav-item">
            @if(url()->isValidUrl($item['uri']))
                <a href="{{ $item['uri'] }}" class="nav-link" target="_blank">
            @else
                <a href="{{ admin_url($item['uri']) }}" class="nav-link">
            @endif
                <i class="nav-icon fa {{ $item['icon'] }}"></i>
                <p>{{ $title }}</p>
            </a>
        </li>
    @else
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fa {{ $item['icon'] }}"></i>
                <p>
                    {{ $title }}
                    <i class="nav-arrow fa fa-angle-right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                @foreach($item['children'] as $item)
                    @include('admin::partials.menu', $item)
                @endforeach
            </ul>
        </li>
    @endif
@endif
