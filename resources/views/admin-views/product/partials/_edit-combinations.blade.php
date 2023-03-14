@if(count($combinations) > 0)
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
            @php(Illuminate\Support\Facades\Log::info($combination))                
            <tr>
                <td>
                    <label for="" class="control-label">{{ $combination['type'] }}</label>
                </td>
                <td>
                    <input type="number" name="price_{{ $combination['type'] }}"
                           value="{{$combination['price']}}" min="0"
                           step="0.01"
                           class="form-control" required>
                </td>
                <td style="display:none" class="variant_meal_price">
                    <input type="number" name="meal_price_{{ $combination['type'] }}" min="0" step="0.01"
                        value="{{$combination['var_meal_price']}}"
                        class="form-control">
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
