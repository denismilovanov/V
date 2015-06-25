<!DOCTYPE html>
<html>
    <head>
        <title>
            Вместе
        </title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
        <link rel="stylesheet" href="/css/admin/common.css">
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
        <script src="/js/admin/common.js"></script>
        <script src="/js/admin/highcharts.src.js"></script>
    </head>
    <body>

        <div id="message-wrapper">
            <div id="message" class="alert alert-info" role="alert">
                &nbsp;
            </div>
        </div>

        <nav class="navbar navbar-default" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <a class="navbar-brand" href="{{ $base }}">
                    <b>
                        Вместе
                    </b>
                    </a>
                </div>

                @include('admin.menu')

            </div>
        </nav>

        <div class="panel panel-default" style="padding: 10px; margin-top: -10px; margin-bottom:10px;">
            @yield('content')
        </div>

    </body>
</html>
