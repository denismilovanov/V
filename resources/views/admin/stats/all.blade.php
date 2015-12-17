@extends('admin.layout')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading">
        Регистрации
    </div>
    <div class="panel-body">
        <div id="registrations_chart"></div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Активность
    </div>
    <div class="panel-body">
        <div id="activity_chart"></div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Активность лайков
    </div>
    <div class="panel-body">
        <div id="likes_chart"></div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Совпадения по дням
    </div>
    <div class="panel-body">
        <div id="matches_chart"></div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Совпадения по месяцам
    </div>
    <div class="panel-body">
        <div id="matches_months_chart"></div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Возраст
    </div>
    <div class="panel-body">
        <div id="ages_chart"></div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Мужчины и женщины
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <div id="males_females_chart"></div>
            </div>
            <div class="col-md-6">
                <div id="who_likes_who_chart"></div>
            </div>
        </div>
    </div>
</div>

<script>

Request.request('GET', '{{ $base }}/stats/', {
    action: 'gender_data'
}, function(data) {
    var highchartyw0 = new Highcharts.Chart({
        'exporting': {
            'enabled': true
        },
        'chart': {
            'plotBackgroundColor': null,
            'plotBorderWidth': null,
            'plotShadow': false,
            'renderTo': 'males_females_chart'
        },
        'title': {
            'text': 'Соотношение мужчины/женщины'
        },
        'tooltip': {
            'pointFormat': ''
        },
        'plotOptions': {
            'pie': {
                'allowPointSelect': true,
                'cursor': 'pointer',
                'dataLabels': {
                    'enabled': true,
                    'format': '<b>{point.name}<\/b>: {point.percentage:.1f} %, <br>{point.value}',
                    'style': {
                        'color': '(Highcharts.theme && Highcharts.theme.contrastTextColor) || \'black\''
                    }
                }
            }
        },
        'series': [{
            'type': 'pie',
            'name': '',
            'data': [{
                'name': 'Мужчины',
                'y': data.males_count * 1.0 / data.users_count,
                'value': data.males_count,
                'color': '#4897F1'
            }, {
                'name': 'Женщины',
                'y': data.females_count * 1.0 / data.users_count,
                'value': data.females_count,
                'color': '#f00'
            }]
        }]
    });
});

Request.request('GET', '{{ $base }}/stats/', {
    action: 'who_likes_who_data'
}, function(data) {
    var highchartyw1 = new Highcharts.Chart({
        'exporting': {
            'enabled': true
        },
        'chart': {
            'plotBackgroundColor': null,
            'plotBorderWidth': null,
            'plotShadow': false,
            'renderTo': 'who_likes_who_chart'
        },
        'title': {
            'text': 'Соотношение лайков'
        },
        'tooltip': {
            'pointFormat': ''
        },
        'plotOptions': {
            'pie': {
                'allowPointSelect': true,
                'cursor': 'pointer',
                'dataLabels': {
                    'enabled': true,
                    'format': '<b>{point.name}<\/b>: {point.percentage:.1f} %, <br>{point.value}',
                    'style': {
                        'color': '(Highcharts.theme && Highcharts.theme.contrastTextColor) || \'black\''
                    }
                }
            }
        },
        'series': [{
            'type': 'pie',
            'name': '',
            'data': [{
                'name': 'Мужчины -> Женщины',
                'y': data.male_likes_female_count * 1.0 / data.likes_count,
                'value': data.male_likes_female_count,
                'color': '#4897F1'
            }, {
                'name': 'Женщины -> Мужчины',
                'y': data.female_likes_male_count * 1.0 / data.likes_count,
                'value': data.female_likes_male_count,
                'color': '#f00'
            }, {
                'name': 'Женщины -> Женщины',
                'y': data.female_likes_female_count * 1.0 / data.likes_count,
                'value': data.female_likes_female_count,
                'color': '#FFB4B4'
            }, {
                'name': 'Мужчины -> Мужчины',
                'y': data.male_likes_male_count * 1.0 / data.likes_count,
                'value': data.male_likes_male_count,
                'color': '#45D6F0'
            }]
        }]
    });
});


Request.request('GET', '{{ $base }}/stats/', {
    action: 'get_ages_data'
}, function(data) {
    var highchartyw2 = new Highcharts.Chart({
        'exporting': {
            'enabled': true
        },
        'chart': {
            'type': 'column',
            'renderTo': 'ages_chart'
        },
        'title': {
            'text': 'Распределение по возрасту'
        },
        'subtitle': {
            'text': ''
        },
        'xAxis': {
            'type': 'category',
            'labels': {
                'rotation': -90,
                'style': {
                    'fontSize': '10px',
                    'fontFamily': 'Verdana, sans - serif'
                }
            }
        },
        'yAxis': {
            'min': 0,
            'title': {
                'text': 'Количество пользователей'
            }
        },
        'legend': {
            'enabled': false
        },
        'tooltip': {
            'pointFormat': 'Количество человек: <b>{point.y}<\/b>'
        },
        'series': [{
            'name': 'Возраст',
            'data': data,
            'dataLabels': {
                'enabled': true
            }
        }]
    });
});

Request.request('GET', '{{ $base }}/stats/', {
    action: 'get_registrations_data'
}, function(data) {
    var r_chart = new Highcharts.Chart({
        'exporting': {
            'enabled': true
        },
        'chart': {
            'type': 'line',
            'renderTo': 'registrations_chart'
        },
        'title': {
            'text': 'Динамика регистраций'
        },
        'subtitle': {
            'text': 'на основе данных о зарегистрированных устройствах'
        },
        'xAxis': {
            'type': 'category',
            'labels': {
                'rotation': -90,
                'style': {
                    'fontSize': '10px',
                    'fontFamily': 'Verdana, sans - serif'
                }
            }
        },
        'yAxis': {
            'min': 0,
            'title': {
                'text': 'Количество регистраций'
            }
        },
        'legend': {
            'enabled': false
        },
        'tooltip': {
            'pointFormat': 'Новых регистраций: <b>{point.y}<\/b>'
        },
        'series': [{
            'name': 'Дата',
            'data': data,
            'dataLabels': {
                'enabled': true
            }
        }]
    });
});

Request.request('GET', '{{ $base }}/stats/', {
    action: 'likes_activity_data'
}, function(data) {
    var highchartyw4 = new Highcharts.Chart({
        'exporting': {
            'enabled': true
        },
        'chart': {
            'type': 'line',
            'renderTo': 'likes_chart'
        },
        'title': {
            'text': 'Динамика лайков'
        },
        'xAxis': {
            'type': 'category',
            'labels': {
                'rotation': -90,
                'style': {
                    'fontSize': '10px',
                    'fontFamily': 'Verdana, sans - serif'
                }
            }
        },
        'yAxis': {
            'min': 0,
            'title': {
                'text': 'Количество лайков'
            }
        },
        'legend': {
            'layout': 'vertical',
            'align': 'right',
            'verticalAlign': 'middle',
            'borderWidth': 0
        },
        'tooltip': {
            'pointFormat': 'Лайков: <b>{point.y}<\/b>'
        },
        'series': [{
            'name': 'Мужчины',
            'data': data.males_likes,
            'dataLabels': {
                'enabled': true
            }
        }, {
            'name': 'Все',
            'data': data.all_likes,
            'dataLabels': {
                'enabled': true
            }
        }, {
            'name': 'Женщины',
            'color': '#f00',
            'data': data.females_likes,
            'dataLabels': {
                'enabled': true
            }
        }]
    });
});

Request.request('GET', '{{ $base }}/stats/', {
    action: 'matches_activity_data'
}, function(data) {
    var highchartyw4 = new Highcharts.Chart({
        'exporting': {
            'enabled': true
        },
        'chart': {
            'type': 'line',
            'renderTo': 'matches_chart'
        },
        'title': {
            'text': 'Динамика совпадений по дням'
        },
        'xAxis': {
            'type': 'category',
            'labels': {
                'rotation': -90,
                'style': {
                    'fontSize': '10px',
                    'fontFamily': 'Verdana, sans - serif'
                }
            }
        },
        'yAxis': {
            'min': 0,
            'title': {
                'text': 'Количество совпадений в день'
            }
        },
        'legend': {
            'layout': 'vertical',
            'align': 'right',
            'verticalAlign': 'middle',
            'borderWidth': 0
        },
        'tooltip': {
            'pointFormat': 'Совпадений: <b>{point.y}<\/b>'
        },
        'series': [{
            'name': 'Все',
            'data': data.matches_count,
            'dataLabels': {
                'enabled': true
            }
        }]
    });
});

Request.request('GET', '{{ $base }}/stats/', {
    action: 'matches_months_activity_data'
}, function(data) {
    var highchartyw4 = new Highcharts.Chart({
        'exporting': {
            'enabled': true
        },
        'chart': {
            'type': 'line',
            'renderTo': 'matches_months_chart'
        },
        'title': {
            'text': 'Динамика совпадений по месяцам'
        },
        'xAxis': {
            'type': 'category',
            'labels': {
                'rotation': -90,
                'style': {
                    'fontSize': '10px',
                    'fontFamily': 'Verdana, sans - serif'
                }
            }
        },
        'yAxis': {
            'min': 0,
            'title': {
                'text': 'Среднее количество совпадений в месяц'
            }
        },
        'legend': {
            'layout': 'vertical',
            'align': 'right',
            'verticalAlign': 'middle',
            'borderWidth': 0
        },
        'tooltip': {
            'pointFormat': 'Совпадений: <b>{point.y}<\/b>'
        },
        'series': [{
            'name': 'Все',
            'data': data.matches_count,
            'dataLabels': {
                'enabled': true
            }
        }]
    });
});

Request.request('GET', '{{ $base }}/stats/', {
    action: 'get_activity_data'
}, function(data) {
    var highchartyw5 = new Highcharts.Chart({
        'exporting': {
            'enabled': true
        },
        'chart': {
            'type': 'line',
            'renderTo': 'activity_chart'
        },
        'title': {
            'text': 'Количество активных пользователей по дням'
        },
        'xAxis': {
            'type': 'category',
            'labels': {
                'rotation': -90,
                'style': {
                    'fontSize': '10px',
                    'fontFamily': 'Verdana, sans - serif'
                }
            }
        },
        'yAxis': {
            'min': 0,
            'title': {
                'text': 'Количество активных пользователей по дням'
            }
        },
        'legend': {
            'layout': 'vertical',
            'align': 'right',
            'verticalAlign': 'middle',
            'borderWidth': 0
        },
        'tooltip': {
            'pointFormat': 'Пользователей: <b>{point.y}<\/b>'
        },
        'series': [{
            'name': 'Мужчины',
            'data': data.males,
            'dataLabels': {
                'enabled': true
            }
        }, {
            'name': 'Все',
            'data': data.all,
            'dataLabels': {
                'enabled': true
            }
        }, {
            'name': 'Женщины',
            'color': '#f00',
            'data': data.females,
            'dataLabels': {
                'enabled': true
            }
        }]
    });
});

</script>

@stop



