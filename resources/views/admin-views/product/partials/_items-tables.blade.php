@if(count($items_arr) > 0)
    <table class="table table-bordered">
        <thead>
        <tr>
            <td class="text-center">
                <label for="" class="control-label">{{translate('Item')}}</label>
            </td>
            <td class="text-center">
                <label for="" class="control-label">{{translate('Default Amount')}}</label>
            </td>
            <td class="text-center">
                <label for="" class="control-label">{{translate('Price')}}</label>
            </td>
            <td class="text-center">
                <label for="" class="control-label">{{translate('Free Upto')}}</label>
            </td>
            <td class="text-center">
                <label for="" class="control-label">{{translate('Max Amount')}}</label>
            </td>
        </tr>
        </thead>
        <tbody>

        @foreach ($items_arr as $key => $item)
                <tr>
                    <td>{{$item["item"]}}</td>
                    <td>{{$item["item_defAmount"]}}</td>
                    <td>{{$item["item_Price"]}}</td>
                    <td>{{$item["item_freeAmount"]}}</td>
                    <td>{{$item["item_maxAmount"]}}</td>
                </tr>
        @endforeach
        </tbody>
    </table>
@endif
