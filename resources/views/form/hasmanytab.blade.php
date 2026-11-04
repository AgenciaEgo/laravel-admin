<style>
    .nav-tabs > li:hover > i{
        display: inline;
    }
    .close-tab {
        position: absolute;
        font-size: 10px;
        top: 2px;
        right: 5px;
        color: #94A6B0;
        cursor: pointer;
        display: none;
    }
</style>
<div id="has-many-{{$column}}" class="nav-tabs-custom has-many-{{$column}}">
    <div class="row header">
        <div class="{{$viewClass['label']}}"><h4 class="float-end">{{ $label }}</h4></div>
        <div class="{{$viewClass['field']}}">
            <button type="button" class="btn btn-outline-secondary btn-sm add"><i class="fa fa-plus-circle" style="font-size: large;"></i></button>
        </div>
    </div>

    <hr style="margin-top: 0px;">

    <ul class="nav nav-tabs mb-3">
        @foreach($forms as $pk => $form)
            <li class="nav-item position-relative">
                <a href="#{{ $relationName . '_' . $pk }}" class="nav-link @if ($form == reset($forms)) active @endif" data-bs-toggle="tab">
                    {{ $pk }} <i class="fa fa-exclamation-circle text-danger d-none"></i>
                </a>
                <i class="close-tab fa fa-times" ></i>
            </li>
        @endforeach

    </ul>
    
    <div class="tab-content has-many-{{$column}}-forms">

        @foreach($forms as $pk => $form)
            <div class="tab-pane fields-group has-many-{{$column}}-form @if ($form == reset($forms)) active show @endif" id="{{ $relationName . '_' . $pk }}">
                @foreach($form->fields() as $field)
                    {!! $field->render() !!}
                @endforeach
            </div>
        @endforeach
    </div>

    <template class="nav-tab-tpl">
        <li class="nav-item position-relative new">
            <a href="#{{ $relationName . '_new_' . \Encore\Admin\Form\NestedForm::DEFAULT_KEY_NAME }}" class="nav-link" data-bs-toggle="tab">
                &nbsp;New {{ \Encore\Admin\Form\NestedForm::DEFAULT_KEY_NAME }} <i class="fa fa-exclamation-circle text-danger d-none"></i>
            </a>
            <i class="close-tab fa fa-times" ></i>
        </li>
    </template>
    <template class="pane-tpl">
        <div class="tab-pane fields-group new" id="{{ $relationName . '_new_' . \Encore\Admin\Form\NestedForm::DEFAULT_KEY_NAME }}">
            {!! $template !!}
        </div>
    </template>

</div>
