@extends('admin.layout')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading">
        Пользователь #{{ $user->id }}
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <td>Имя</td>
                        <td>{{ $user->name }}, {{ $user->gender }}</td>
                    </tr>
                    <tr>
                        <td>VK ID</td>
                        <td><a href="https://vk.com/id{{ $user->vk_id }}">{{ $user->vk_id }}</a></td>
                    </tr>
                    <tr>
                        <td>Регистрация</td>
                        <td>{{ $user->registered_at }}</a></td>
                    </tr>
                    <tr>
                        <td>Активность</td>
                        <td>{{ $user->last_activity_at }}</a></td>
                    </tr>
                    <tr>
                        <td>Заблокирован нами?</td>
                        <td>@if ($user->is_blocked) Да ({{ $user->block_reason }}) @else Нет @endif</a></td>
                    </tr>
                    <tr>
                        <td>Заблокирован VK?</td>
                        <td>@if ($user->is_blocked_by_vk) Да @else Нет @endif</a></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <td>Число собственных лайков</td>
                        <td>{{ $user->likes_count }}</td>
                    </tr>
                    <tr>
                        <td>Число собственных дизлайков</td>
                        <td>{{ $user->dislikes_count }}</td>
                    </tr>
                    <tr>
                        <td>Сколько раз его/ее лайкнули</td>
                        <td>{{ $user->liked_count }}</td>
                    </tr>
                    <tr>
                        <td>Сколько раз его/ее дизлайкнули</td>
                        <td>{{ $user->disliked_count }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <form action="?" method="post">
                    @if ($user->is_blocked)
                        <button class="btn btn-success" type="submit" name="action" value="unblock">Разблокировать</button>
                    @else
                        <button class="btn btn-danger" type="submit" name="action" value="block">Заблокировать</button>
                        <input type="text" name="reason" value="" placeholder="Причина блокировки" id="reason" style="width:100%;" />
                        <br /><br />
                    @endif
                    @if ($user->is_deleted)
                        <button class="btn btn-success" type="submit" name="action" value="unremove">Восстановить</button>
                    @else
                        <button class="btn btn-danger" type="submit" name="action" value="remove">Удалить</button>
                    @endif
                </form>
            </div>
            <div class="col-md-6">

            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Фотографии
    </div>
    <div class="panel-body">
        @foreach ($user->photos as $photo)
            <img src="{{ $photo['url'] }}" style="height: 100px;" />
        @endforeach
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Жалобы
        <button class="btn btn-xs btn-danger navbar-right" type="button" onclick="userForm.removeAllAbuses({{ $user->id }});">Удалить все</button>
    </div>
    <table class="table" id="abuses">
        <tr>
            <th>ID</th>
            <th>От кого</th>
            <th>Текст</th>
            <th></th>
        </tr>
        @foreach ($user->abuses as $abuse)
        <tr id="abuse{{ $abuse->id }}">
            <td>{{ $abuse->id }}</td>
            <td><a href="{{ $base }}/users/{{ $abuse->from_id }}">{{ $abuse->from_name }} ({{ $abuse->from_id }})</a></td>
            <td>{{{ $abuse->message }}}</td>
            <td><button class="btn btn-xs btn-danger" type="button" onclick="userForm.removeAbuse({{ $abuse->id }});">Удалить</button></td>
        </tr>
        @endforeach
    </table>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Блокировки
    </div>
    <table class="table" id="abuses">
        <tr>
            <th>Кого заблокировал?</th>
            <th></th>
            <th></th>
        </tr>
        @foreach ($user->blocks as $block)
        <tr id="block{{ $block->user_id }}">
            <td><a href="{{ $base }}/users/{{ $block->user_id }}">{{ $block->user_id }}</a></td>
            <td>{{ $block->name }}</td>
            <td><button class="btn btn-xs btn-info" type="button" onclick="userForm.unblock({{ $block->user_id }});">Разблокировать</button></td>
        </tr>
        @endforeach
    </table>
</div>

<script>

var user_id = {{ $user->id }};

var userForm = {
    removeAbuse: function(abuse_id) {
        Request.request('POST', '?', {
            abuse_id: abuse_id,
            action: 'remove_abuse'
        }, function() {
            $('#abuse' + abuse_id).remove();
        });
    },

    removeAllAbuses: function(user_id) {
        Request.request('POST', '?', {
            user_id: user_id,
            action: 'remove_all_abuses'
        }, function() {
            $('#abuses').remove();
        });
    },

    unblock: function(blocked_user_id) {
        Request.request('POST', '?', {
            user_id: user_id,
            blocked_user_id: blocked_user_id,
            action: 'unblock_like'
        }, function() {
            $('#block' + blocked_user_id).remove();
        });
    }
}

</script>

@stop



