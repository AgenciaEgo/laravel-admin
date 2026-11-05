<form action="{!! $action !!}" pjax-container class="d-inline-block">
    <div class="input-group input-group-sm" style="width: 250px;">
        <input type="text" name="{{ $key }}" class="form-control grid-quick-search" value="{{ $value }}" placeholder="{{ $placeholder }}">
        <button type="submit" class="btn btn-outline-secondary"><i class="fa fa-search"></i></button>
    </div>
</form>
