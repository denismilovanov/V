@extends('admin.layout')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading">
        Поиск
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4">
                <form action="?" method="get">
                    <label>ID</label>
                    <input type="text" class="form-control" name="user_id" value="{{ \Request::get('user_id') }}" />
                    <button class="btn btn-xs btn-info" type="submit" name="action" value="search">Искать</button>
                </form>
            </div>
            <div class="col-md-4">
                <form action="?" method="get">
                    <label>VK ID</label>
                    <input type="text" class="form-control" name="vk_id" value="{{ \Request::get('vk_id') }}" />
                    <button class="btn btn-xs btn-info" type="submit" name="action" value="search">Искать</button>
                </form>
            </div>
            <div class="col-md-4">

            </div>
        </div>
    </div>
</div>

</form>

<form action="?" method="get">

<div class="panel panel-default">
    <div class="panel-heading">
        Пользователи
        <button class="btn btn-success btn-xs pull-right" type="submit">Показать всех</button>
        <button class="btn btn-warning btn-xs pull-right" type="submit" name="action" value="search_with_abuses">Показать незаблокированных с жалобами</button>
    </div>
    <table class="table" id="users">
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Пол</th>
            <th>Возраст</th>
            <th>VK ID</th>
            <th>App</th>
            <th>Заблокирован нами?</th>
            <th>Заблокирован VK?</th>
            <th>Регистрация</th>
            <th>Число жалоб</th>
        </tr>
    </table>
    @if ($action != 'search_with_abuses')
        <center>
            <button class="btn btn-success" type="button" onclick="UsersForm.loadUsers(UsersForm.page);">Показать еще</button>
        </center>
    @endif
    <br />
</div>

</form>

<script>

    UsersForm = {
        page: 0,

        loadUsers: function(page) {
            Request.request('GET', '{{ $base }}/users/', {
                action: '{{ $action }}',
                ajax: true,
                page: page
            }, function(data) {
                if ('{{ $action }}' != 'search_with_abuses') {
                    $('#users').append(
                        '<tr><td colspan="10" style="height: 10px;">' + UsersForm.page + ' д. назад</td></tr>'
                    );
                }

                for (var k in data) {
                    var user = data[k];
                    $('#users').append(
                        '<tr>' +
                            '<td><a href="{{ $base }}/users/' + user['id'] + '">' + user['id'] + '</a></td>' +
                            '<td>' + user['name'] + '</td>' +
                            '<td>' + user['sex'] + '</td>' +
                            '<td>' + user['age'] + '</td>' +
                            '<td><a href="https://vk.com/id' + user['vk_id'] + '">' + user['vk_id'] + '</a></td>' +
                            '<td>' + user['app'] + '</td>' +
                            '<td>' + (user['is_blocked'] ? 'Да' : 'Нет') + '</td>' +
                            '<td>' + (user['is_blocked_by_vk'] ? 'Да' : 'Нет') + '</td>' +
                            '<td>' + user['registered_at'] + '</td>' +
                            '<td>' + user['abuses_count'] + '</td>' +
                        '</tr>' +
                        '<tr>' +
                            '<td colspan=10 style="border-top: none; padding-top: 0;">' +
                                '<span style="color: silver; font-family: Courier;">' + user['geography'] + '</span> ' +
                                user['city'] + ' ' +
                                user['region'] + ' ' +
                            '</td>' +
                        '</tr>'
                    );
                }
                UsersForm.page += 1;
            });
        }
    };

    UsersForm.loadUsers(UsersForm.page);


</script>

@stop



