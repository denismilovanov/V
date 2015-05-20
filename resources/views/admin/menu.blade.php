@if (Auth::check())

<ul class="nav navbar-nav">
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
            Тестирование
            <span class="caret"></span>
        </a>
            <ul class="dropdown-menu" role="menu">
                <li><a href="/tests/sendRequest">Отправить запрос на апи</a></li>
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
                <a href="/logout/">Выйти</a>
            </li>
        </ul>
    </li>
</ul>

@endif
