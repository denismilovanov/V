@extends('admin.layout')

@section('content')


<div class="panel panel-default">
    <div class="panel-heading">
        Добавить или обновить версию
    </div>
    <div class="panel-body">
        <form>
            <input type="hidden" name="action" value="upsert" />
            <div class="row">
                <div class="col-md-6">
                    <label>Версия (X.Y.Z)</label>
                    <input type="text" class="form-control" name="id" id="id" value="{{ $version->id }}" />
                    <br />
                    <select name="device_type" class="form-control">
                        <option value="1" @if ($version->device_type == 1) selected @endif>iOS</option>
                        <option value="2" @if ($version->device_type == 2) selected @endif>Android</option>
                    </select>
                    <br />
                    <button type="submit" class="btn btn-success">Добавить или обновить</button>
                </div>
                <div class="col-md-6">
                    <textarea name="description" style="width: 100%; height: 200px;">{{ $version->description }}</textarea>
                </div>
            </div>
        </form>
    </div>
</div>


<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                iOS
            </div>
            <table class="table">
                <tr>
                    <th>ID</th>
                    <th>Актуальность</th>
                    <th>Описание</th>
                    <th>Действия</th>
                </tr>
                @foreach ($versions[1] as $version)
                <tr>
                    <td><a href="?id={{ $version->id }}&device_type=1">{{ $version->id }}</a></td>
                    <td>
                        @if ($version->is_actual) Актуальна @endif
                    </td>
                    <td><?php echo nl2br(e($version->description)) ?></td>
                    <td>
                        @if (! $version->is_actual)
                            <a href="?id={{ $version->id }}&device_type=1&action=make_actual">Сделать актуальной</a>
                        @else
                            <a href="?id={{ $version->id }}&device_type=1&action=make_noactual">Сделать НЕ актуальной</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                Android
            </div>
            <table class="table">
                <tr>
                    <th>ID</th>
                    <th>Актуальность</th>
                    <th>Описание</th>
                    <th>Действия</th>
                </tr>
                @foreach ($versions[2] as $version)
                <tr>
                    <td><a href="?id={{ $version->id }}&device_type=1">{{ $version->id }}</a></td>
                    <td>
                        @if ($version->is_actual) Актуальна @endif
                    </td>
                    <td><?php echo nl2br(e($version->description)) ?></td>
                    <td>
                        @if (! $version->is_actual)
                            <a href="?id={{ $version->id }}&device_type=2&action=make_actual">Сделать актуальной</a>
                        @else
                            <a href="?id={{ $version->id }}&device_type=2&action=make_noactual">Сделать НЕ актуальной</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>

@stop



