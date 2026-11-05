{{-- one handler per column: several inline-edit types can share a grid --}}
$(document).off('click', '.ie-content[data-ie-trigger="{{ $trigger }}"] .ie-submit').on('click', '.ie-content[data-ie-trigger="{{ $trigger }}"] .ie-submit', function () {

    var $popover = $(this).closest('.ie-content');
    // the element that opened this popover (Bootstrap links it with aria-describedby): do not
    // depend on the shown.bs.popover handler having run, a quick click can come before it
    var $trigger = $('[aria-describedby="' + $popover.closest('.popover').attr('id') + '"]');
    $popover.data('trigger', $trigger).data('display', $trigger.closest('.ie-wrap').find('.ie-display'));

    @isset($val)
        {{ $val }}
    @else
        var val = $popover.find('.ie-input').val();
    @endisset

    var original = $trigger.data('original');

    if (val == original) {
        $('[data-toggle="popover"]').popover('hide');
        return;
    }

    var data = {
        _token: LA.token,
        _method: 'PUT',
        _edit_inline: true,
    };
    data[$trigger.data('name')] = val;

    $.ajax({
        url: "{{ $resource }}/" + $trigger.data('key'),
        type: "POST",
        data: data,
        success: function (data) {
            toastr.success(data.message);

            {{ $slot }}

            $trigger.data('value', val)
                .data('original', val);

            $('[data-toggle="popover"]').popover('hide');
        },
        statusCode: {
            422: function(xhr) {
                $popover.find('.error').empty();
                var errors = xhr.responseJSON.errors;
                for (var key in errors) {
                    $popover.find('.error').append('<div><i class="fa fa-times-circle-o"></i> '+errors[key]+'</div>')
                }
            }
        }
    });
});
