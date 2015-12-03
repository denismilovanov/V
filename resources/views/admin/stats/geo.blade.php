@extends('admin.layout')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading">
        География
    </div>
    <div class="panel-body">
        <table class="table" id="geo">
            <tr>
                <th width="25%">Регион [region_id]</th>
                <th width="25%">Город [city_id]</th>
                <th width="16%">Всего</th>
                <th width="16%">Мужчин</th>
                <th width="16%">Женщин</th>
            <tr>
        </table>
    </div>
</div>

<script>

    GeoForm = {
        loadGeo: function(page) {
            Request.request('GET', '{{ $base }}/geo', {
                action: 'get_geo_data',
                ajax: true
            }, function(data) {
                for (var k in data) {
                    var row = data[k];
                    $('#geo').append(
                        '<tr>' +
                            '<td>' + row['region'] + '[' + row['region_id'] + ']</td>' +
                            '<td>' + row['city'] + '[' + row['city_id'] + ']</td>' +
                            '<td>' + row['count'] + '</td>' +
                            '<td>' + row['count_males'] + '</td>' +
                            '<td>' + row['count_females'] + '</td>' +
                        '</tr>'
                    );
                }
            });
        }
    };

    $(window).ready(function() {
        GeoForm.loadGeo();
    });

</script>

@stop



