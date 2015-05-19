@extends('admin.layout')

@section('content')

<form action="?" method="post">
    <div class="panel panel-default" style="width: 40%; margin: 0 auto;">
        <div class="panel-heading">
            Вход
        </div>
        <div class="panel-body">
            <input class="form-control" name="email" type="text" placeholder="Емейл" value="{{{ $email }}}">
            <br>
            <input class="form-control" name="password" type="password" placeholder="Пароль">
            <br>
            <button class="btn btn-default" type="submit">
            Войти
            </button>
        </div>
    </div>
</form>

@stop


