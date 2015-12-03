@if (Auth::check())

<ul class="nav navbar-nav">
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
            Пользователи
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu" role="menu">
            <li><a href="{{ $base }}/users/">Список и поиск</a></li>
        </ul>
    </li>
</ul>

<ul class="nav navbar-nav">
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
            Статистика
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu" role="menu">
            <li><a href="{{ $base }}/stats/">Регистрации и лайки</a></li>
            <li><a href="{{ $base }}/geo/">География</a></li>
        </ul>
    </li>
</ul>

<ul class="nav navbar-nav">
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
            Инструменты
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu" role="menu">
            <li><a href="{{ $base }}/tools/push">Push</a></li>
            <li><a href="{{ $base }}/tools/softVersions">Версии приложений</a></li>
        </ul>
    </li>
</ul>

<ul class="nav navbar-nav">
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
            Тестирование
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu" role="menu">
            <li><a href="{{ $base }}/tests/sendRequest">Отправить запрос на апи</a></li>
        </ul>
    </li>
</ul>

<ul class="nav navbar-nav">
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">{{ Auth::user()->name }}
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu" role="menu">
            <li>
                <a href="{{ $base }}/logout/">Выйти</a>
            </li>
        </ul>
    </li>
</ul>

@endif

