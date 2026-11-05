$('.{{ $trigger }}').each(function () {
    $.admin.inlineEdit.popover(this, function ($trigger, $template) {
        {{ $content }}
    });
}).on('shown.bs.popover', function (e) {

    var $popover = $(bootstrap.Popover.getInstance(this).tip).find('.ie-content');
    var $display = $(this).parents('.ie-wrap').find('.ie-display');
    var $trigger = $(this);

    $popover.data('display', $display);
    $popover.data('trigger', $trigger);

    {{ $shown ?? '' }}

}).on('click', function () {
    $.admin.inlineEdit.toggle(this);
});
