<span data-bs-toggle="modal" data-bs-target="#grid-modal-{{ $name }}" data-key="{{ $key }}">
   <a href="javascript:void(0)"><i class="fa fa-clone"></i>&nbsp;&nbsp;{{ $value }}</a>
</span>

<div class="modal grid-modal fade" id="grid-modal-{{ $name }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 5px;">
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {!! $html !!}
            </div>
        </div>
    </div>
</div>

@if($grid)
<style>
    .card.grid-box {
        box-shadow: none;
        border-top: none;
    }

    .grid-box .card-header:first-child {
        display: none;
    }
</style>
@endif

@if($async)
<script>
    var modal = $('#grid-modal-{{ $name }}');
    var modalBody = modal.find('.modal-body');

    var load = function (url) {

        modalBody.html("<div class='loading text-center' style='height:200px;'>\
                <i class='fa fa-spinner fa-pulse fa-3x fa-fw' style='margin-top: 80px;'></i>\
            </div>");

        $.get(url, function (data) {
            modalBody.html(data);
        });
    };

    modal.on('show.bs.modal', function (e) {
        var key = $(e.relatedTarget).data('key');
        load('{{ $url }}'+'&key='+key);
    }).on('click', '.page-item a, .filter-box a', function (e) {
        load($(this).attr('href'));
        e.preventDefault();
    }).on('submit', '.grid-tools form, .filter-box form', function (e) {
        load($(this).attr('action')+'&'+$(this).serialize());
        return false;
    });
</script>
@endif
