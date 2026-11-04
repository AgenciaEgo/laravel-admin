<div class="nav-tabs-custom">
    <ul class="nav nav-tabs mb-3" role="tablist">

        @foreach($tabObj->getTabs() as $tab)
            <li class="nav-item" role="presentation">
                <a href="#tab-{{ $tab['id'] }}" class="nav-link {{ $tab['active'] ? 'active' : '' }}" data-bs-toggle="tab" role="tab">
                    {{ $tab['title'] }} <i class="fa fa-exclamation-circle text-danger d-none"></i>
                </a>
            </li>
        @endforeach

    </ul>
    <div class="tab-content fields-group">

        @foreach($tabObj->getTabs() as $tab)
            <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="tab-{{ $tab['id'] }}" role="tabpanel">
                @foreach($tab['fields'] as $field)
                    {!! $field->render() !!}
                @endforeach
            </div>
        @endforeach

    </div>
</div>
