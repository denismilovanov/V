@extends('admin.layout')

@section('content')

<div class="panel panel-default">
    <div class="panel-heading">
        География
    </div>
    <div class="panel-body">
        <table class="table" id="geo">
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
                $('#geo tbody').remove();
                for (var k in data) {
                    var row = data[k];
                    $('#geo').append(
                        '<tr>' +
                            '<td>' + row['region'] + '</td>' +
                            '<td>' + row['city'] + '</td>' +
                            '<td>' + row['count'] + '</td>' +
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



