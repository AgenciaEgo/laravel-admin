<div class="form-check form-switch la-grid-switch m-0">
    <input type="checkbox" role="switch" class="form-check-input {{ $class }} {{ $colors }}" {{ $checked }} data-key="{{ $key }}" />
</div>

<script>
    // native Bootstrap 5 switch; on a failed save the switch goes back to its previous state
    $('.{{ $class }}').off('change.la-switch').on('change.la-switch', function () {
        var $switch = $(this);
        var checked = this.checked;
        var revert = function () { $switch.prop('checked', !checked); };

        $.ajax({
            url: "{{ $resource }}/" + $switch.data('key'),
            type: "POST",
            data: {
                "{{ $name }}": checked ? @json($states['on']['value']) : @json($states['off']['value']),
                _token: LA.token,
                _method: 'PUT',
                _edit_inline: true
            },
            success: function (data) {
                if (data.status) {
                    toastr.success(data.message);
                } else {
                    revert();
                    toastr.warning(data.message);
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                revert();
                var data = xhr.responseJSON || {};
                if (data['errors'] || data['message']) {
                    toastr.error(data['message'] || Object.values(data['errors']).join("\n"));
                } else {
                    toastr.error('Error: ' + errorThrown);
                }
            }
        });
    });
</script>
