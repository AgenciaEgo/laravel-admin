@if(Session::has('toastr'))
    @php
        $toastr     = Session::pull('toastr');
        // flashed as a plain array by admin_toastr(), older sessions may still hold a MessageBag
        $toastr     = $toastr instanceof \Illuminate\Support\MessageBag ? $toastr->toArray() : (array) $toastr;
        $type       = \Illuminate\Support\Arr::first(\Illuminate\Support\Arr::wrap(\Illuminate\Support\Arr::get($toastr, 'type'))) ?: 'success';
        $message    = \Illuminate\Support\Arr::first(\Illuminate\Support\Arr::wrap(\Illuminate\Support\Arr::get($toastr, 'message'))) ?: '';
        $options    = json_encode(\Illuminate\Support\Arr::get($toastr, 'options') ?: new \stdClass());
    @endphp
    <script>
        $(function () {
            toastr.{{$type}}('{!!  $message  !!}', null, {!! $options !!});
        });
    </script>
@endif
