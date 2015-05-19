@extends('admin.layout')

@section('content')

<div class="row">
    <div class="col-md-6">
        <form action="?" method="post">
            <label>user_id</label>
            <input type="text" class="form-control" name="user_id" />

            <br />

            <label>method</label>
            <select name="method">
                @foreach(\App\Models\Api::getMethods() as $method)
                    <option value="{{ $method['name'] }}">{{ $method['name'] }}</option>
                @endforeach
            </select>

            <br /><br />

            @foreach(\App\Models\Api::getMethods() as $method)
                <div class="panel panel-default" style="display: block">
                    <div class="panel-heading">
                        Параметры
                    </div>
                    <div class="panel-body">
                        @foreach($method['fields'] as $field)
                        <div >
                            <label>{{ $field }}</label>
                            <br />
                            <input type="text" class="form-control" name="{{ $method['name']}}.{{ $field }}" />
                        </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <button type="submit" class="btn btn-success">Отправить</button>

        </form>
    </div>
    <div class="col-md-6">
        <label>Результат</label>
        <pre style="height: 400px;"></pre>
    </div>
</div>

@stop


