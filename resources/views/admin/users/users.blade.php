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
    <table class="table">
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Пол</th>
            <th>Возраст</th>
            <th>VK ID</th>
            <th>Заблокирован нами?</th>
            <th>Заблокирован VK?</th>
            <th>Регистрация</th>
            <th>Число жалоб</th>
        </tr>
        @foreach ($users as $user)
            <tr>
                <td><a href="/users/{{ $user->id }}">{{ $user->id }}</a></td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->sex }}</td>
                <td>{{ $user->age }}</td>
                <td><a href="https://vk.com/id{{ $user->vk_id }}">{{ $user->vk_id }}</a></td>
                <td>@if ($user->is_blocked) Да @else Нет @endif</td>
                <td>@if ($user->is_blocked_by_vk) Да @else Нет @endif</td>
                <td>{{ $user->registered_at }}</td>
                <td>{{ $user->abuses_count }}</td>
            </tr>
        @endforeach
    </table>

    <nav align="center">
        <ul class="pagination">
        <li>
            <a href="?page={{ $page > 1 ? $page - 1 : 0 }}" aria-label="<">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
        <li>
            <a href="?page={{ $page + 1 }}" aria-label=">">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
        </ul>
    </nav>

</div>

</form>

<script>

</script>

@stop



