@if(count($arr) > 0)
    <table class="table table-bordered">
        <thead>
        <tr>
            <td class="text-center">
                <label for="" class="control-label">{{translate('Name')}}</label>
            </td>
            <td class="text-center">
                <label for="" class="control-label">{{translate('Price')}}</label>
            </td>
        </tr>
        </thead>
        <tbody>

        @foreach ($arr as $key => $el)
                <tr>
                    <td>{{$el["Name"]}}</td>
                    <td>{{$el["Price"]}}</td>
                </tr>
        @endforeach
        </tbody>
    </table>
@endif
