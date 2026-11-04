<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">Dependencies</h3>

        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                <i data-lte-icon="expand" class="fa fa-plus"></i><i data-lte-icon="collapse" class="fa fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-lte-toggle="card-remove"><i class="fa fa-times"></i></button>
        </div>
    </div>

    <div class="card-body p-0 dependencies">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                @foreach($dependencies as $dependency => $version)
                <tr>
                    <td width="240px">{{ $dependency }}</td>
                    <td><span class="badge text-bg-primary">{{ $version }}</span></td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>

<script>
    $('.dependencies').slimscroll({height:'510px',size:'3px'});
</script>
