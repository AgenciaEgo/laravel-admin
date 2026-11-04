<div class="{{$viewClass['form-group']}} {!! !$errors->has($column) ?: 'has-error' !!}">

    <label for="{{$id}}" class="{{$viewClass['label']}}">{{$label}}</label>

    <div class="{{$viewClass['field']}}" id="{{$id}}">

        <div class="la-checks">

        @if($canCheckAll)
            <div class="form-check form-check-inline">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input {{ $checkAllClass }}"/>{{ __('admin.all') }}
                </label>
            </div>
            <hr class="mt-2 mb-0">
        @endif

        @include('admin::form.error')

        @if($groups)

        @foreach($groups as $group => $options)

            <p class="mb-1 pb-1 border-bottom d-inline-block {{ $canCheckAll ? 'mt-3' : 'mt-2' }}">{{ $group }}</p>

            @foreach($options as $option => $label)
            <div class="form-check">
                <label class="form-check-label">
                    <input type="checkbox" name="{{$name}}[]" value="{{$option}}" class="form-check-input {{$class}}" {{ false !== array_search($option, array_filter(old($column, $value ?? []))) || ($value === null && in_array($option, $checked)) ?'checked':'' }} {!! $attributes !!} />{{$label}}
                </label>
            </div>
            @endforeach

        @endforeach

        @else

        @foreach($options as $option => $label)
            <div class="form-check {{ $inline ? 'form-check-inline' : '' }}">
                <label class="form-check-label">
                    <input type="checkbox" name="{{$name}}[]" value="{{$option}}" class="form-check-input {{$class}}" {{ false !== array_search($option, array_filter(old($column, $value ?? []))) || ($value === null && in_array($option, $checked)) ?'checked':'' }} {!! $attributes !!} />{{$label}}
                </label>
            </div>
        @endforeach

        @endif

        </div>

        <input type="hidden" name="{{$name}}[]">

        @include('admin::form.help-block')

    </div>
</div>
