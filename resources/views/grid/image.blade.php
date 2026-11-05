<div class="card grid-box mb-3">

    {!! $grid->renderFilter() !!}

    <div class="card-body table-responsive p-0">
        <ul class="image clearfix">
            @foreach($grid->rows() as $row)
            <li>
                <label>
                    {!! $row->column($key) !!}
                    {!! $row->column('__modal_selector__') !!}
                </label>
            </li>
            @endforeach
        </ul>
    </div>

    <div class="card-footer d-flex flex-wrap align-items-center gap-2">
        {!! $grid->paginator() !!}
    </div>
</div>

<style>
ul.image {
    padding: 0px;
}

.image li {
    float: left;
    margin: 10px;
    list-style-type:none;
    position: relative;
}

.image label {
    cursor: pointer;
}

.image .img-thumbnail {
    padding-right: 15px;
}

.image .form-check-input {
    position: absolute;
    bottom: 5px;
    right: 1px;
    background-color: #ffffff;
}
</style>
