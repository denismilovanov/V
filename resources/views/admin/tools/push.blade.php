@extends('admin.layout')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading">
        Отправка сообщения пользователям
    </div>
    <div class="panel-body">

        <div class="row">
            <div class="col-md-12">
                <textarea style="height: 200px; width: 100%;" id="push_message"></textarea>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">

                <div class="panel panel-default">
                    <div class="panel-heading">
                        Одному пользователю
                    </div>
                    <div class="panel-body">
                        <label>user_id</label>
                        <input type="text" class="form-control" name="user_id" id="user_id" value="{{ \Request::get('user_id') }}" />
                        <br />
                        <button type="button" class="btn btn-success" onclick="pushForm.personalPush();">Отправить одному</button>
                    </div>
                </div>

            </div>

            <div class="col-md-6">

                <div class="panel panel-default">
                    <div class="panel-heading">
                        Всем пользователям
                    </div>
                    <div class="panel-body">
                        <button type="button" class="btn btn-success" onclick="pushForm.massPush();">Отправить всем</button>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script>

var pushForm = {
    personalPush: function() {
        if ($('#push_message').val() == '') {
            return ;
        }

        Request.request('GET', '?', {
            user_id: $('#user_id').val(),
            action: 'personal_push',
            message: $('#push_message').val(),
        }, function(data) {
            if (data.status == 1) {
                Messager.info('Отправлено.');
            } else {
                Messager.info('НЕ отправлено.');
            }
        });
    },

    massPush: function() {
        if ($('#push_message').val() == '') {
            return ;
        }

        Request.request('GET', '?', {
            action: 'mass_push',
            message: $('#push_message').val(),
        }, function(data) {
            if (data.status == 1) {
                Messager.info('Поставлено в очередь.');
            } else {
                Messager.info('НЕ поставлено в очередь.');
            }
        });
    }
};

</script>

@stop



