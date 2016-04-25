@extends('admin.layout')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading">
        Отмена двух встречных лайков
    </div>
    <div class="panel-body">

        <div class="panel-body">
            <label>user1_id</label>
            <input type="text" class="form-control" name="user1_id" id="user1_id" value="{{ \Request::get('user1_id') }}" />
            <br />

            <label>user2_id</label>
            <input type="text" class="form-control" name="user2_id" id="user2_id" value="{{ \Request::get('user2_id') }}" />

            <br />
            <button type="button" class="btn btn-success" onclick="miscForm.unlikeMutual();">Отменить оба лайка</button>
        </div>

    </div>
</div>

<script>

var miscForm = {
    unlikeMutual: function() {
        Request.request('GET', '?', {
            user1_id: $('#user1_id').val(),
            user2_id: $('#user2_id').val(),
            action: 'unlike_mutual',
        }, function(data) {
            if (data.status == 1) {
                Messager.info('Удалено.');
            } else {
                Messager.info('Лайки не удалены: их не было.');
            }
        });
    }
};

</script>

@stop



