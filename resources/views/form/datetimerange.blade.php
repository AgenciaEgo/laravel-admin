<div class="{{$viewClass['form-group']}} {!! ($errors->has($errorKey['start']) || $errors->has($errorKey['end'])) ? 'has-error' : ''  !!}">

    <label for="{{$id['start']}}" class="{{$viewClass['label']}}">{{$label}}</label>

    <div class="{{$viewClass['field']}}">
        <div class="row">
            <div class="col-lg-12">
                @if($errors->has($errorKey['start']))
                    @foreach($errors->get($errorKey['start']) as $message)
                        <div class="invalid-feedback d-block"><i class="fa fa-times-circle-o"></i> {{$message}}</div>
                    @endforeach
                @endif

                <div class="input-group" style="width: 200px">
                    <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                    <input type="text"
                           name="{{$name['start']}}"
                           value="{{ old($column['start'], $value['start'] ?? null) }}"
                           class="form-control {{$class['start']}}"
                           autocomplete="off"
                            {!! $attributes !!}
                    />
                </div>
            </div>
        </div>

        <div class="row" style="margin-top: 5px">
            <div class="col-lg-12">
                @if($errors->has($errorKey['end']))
                    @foreach($errors->get($errorKey['end']) as $message)
                        <div class="invalid-feedback d-block"><i class="fa fa-times-circle-o"></i> {{$message}}</div>
                    @endforeach
                @endif

                <div class="input-group" style="width: 200px">
                    <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                    <input type="text"
                           name="{{$name['end']}}"
                           value="{{ old($column['end'], $value['end'] ?? null) }}"
                           class="form-control {{$class['end']}}"
                           autocomplete="off"
                            {!! $attributes !!}
                    />
                </div>
            </div>
        </div>

        @include('admin::form.help-block')

    </div>
</div>
