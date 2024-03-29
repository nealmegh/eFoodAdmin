@if(count($combinations[0]) > 0)
    <table class="table table-bordered" id="variant-table">
        <thead>
        <tr>
            <td class="text-center">
                <label for="" class="control-label">{{translate('Variant')}}</label>
            </td>
            <td class="text-center">
                <label for="" class="control-label">{{translate('Variant Price')}}</label>
            </td>
        </tr>
        
        </thead>
        <tbody>

        @foreach ($combinations as $key => $combination)
            @php
                $str = '';
                foreach ($combination as $key => $item){
                    if($key > 0 ){
                        $str .= '-'.str_replace('', '', $item);
                    }
                    else{
                        $str .= str_replace('', '', $item);
                    }
                }
            @endphp
            @if(strlen($str) > 0)
                <tr>
                    <td>
                        <label for="" class="control-label">{{ $str }}</label>
                    </td>
                    <td>
                        <input type="number" name="price_{{ str_replace(' ', '_', $str)}}" value="{{ $price }}" min="0" step="0.01"
                               class="form-control" required>
                    </td>
                    <td style="display:none" class="variant_meal_price">
                        <input type="number" name="meal_price_{{ str_replace(' ', '_', $str) }}" min="0" step="0.01"
                            class="form-control">
                    </td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>
@endif
