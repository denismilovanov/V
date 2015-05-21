@extends('admin.layout')

@section('content')

<div class="row">
    <div class="col-md-4">
        <form action="?" method="post">
            <label>user_id</label>
            <input type="text" class="form-control" name="user_id" value="{{ \Request::get('user_id') }}" />

            <br />

            <label>method</label>
            <select name="method" id="method" onchange="sendRequestForm.changeMethod();">
                @foreach(\App\Models\Api::getMethods() as $name => $method)
                    <option value="{{ $name }}"
                        @if ($name == \Request::get('method'))
                            selected="selected"
                        @endif
                    >{{ $name }}</option>
                @endforeach
            </select>

            <br /><br />

            @foreach(\App\Models\Api::getMethods() as $name => $method)
                <div class="panel panel-default params" style="display: none" id="params{{ $name }}">
                    <div class="panel-heading">
                        Параметры
                    </div>
                    <div class="panel-body">
                        @foreach($method['fields'] as $field)
                        <div >
                            <label>{{ $field }}</label>
                            <br />
                            <input  type="text" class="form-control"
                                    name="{{ $name }}[{{ $field }}]"
                                    value="{{ \Request::get($name)[$field] }}" />
                        </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <button type="submit" class="btn btn-success">Отправить</button>

        </form>
    </div>
    <div class="col-md-8" style="word-break: break-all;">
        <label>Результат</label>
        <pre style="height: 500px;">{{ $result['response'] }}</pre>
        <br />
        <a target="_blank" href="{{ $result['url'] }}">{{ $result['url'] }}</a>
    </div>
</div>

<script>

var sendRequestForm = {
    changeMethod: function() {
        var method = $('#method').val();
        $('.params').hide();
        $('#params' + method).show();
    }
}

sendRequestForm.changeMethod();

</script>

@stop



