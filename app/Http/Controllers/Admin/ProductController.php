<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Category;
use App\Model\Product;
use App\Model\Review;
use App\Model\Translation;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;
use Rap2hpoutre\FastExcel\FastExcel;

class ProductController extends Controller
{
    public function variant_combination(Request $request)
    {
        $options = [];
        $price = $request->price;
        $product_name = $request->name;

        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }

        $result = [[]];
        foreach ($options as $property => $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, [$property => $property_value]);
                }
            }
            $result = $tmp;
        }
        $combinations = $result;
        return response()->json([
            'view' => view('admin-views.product.partials._variant-combinations', compact('combinations', 'price', 'product_name'))->render(),
        ]);
    }

    // public function add_items(Request $request)
    // {
    //     $items_arr = json_decode(urldecode(html_entity_decode($request->getContent())), true);

    //     return response()->json([
    //         'view' => view('admin-views.product.partials._items-tables', compact('items_arr'))->render(),
    //     ]);
    // }

    // public function add_sides_or_drinks(Request $request)
    // {
    //     $arr = json_decode(urldecode(html_entity_decode($request->getContent())), true);

    //     return response()->json([
    //         'view' => view('admin-views.product.partials._side_or_drink-table', compact('arr'))->render(),
    //     ]);
    // }

    public function get_categories(Request $request)
    {
        $cat = Category::where(['parent_id' => $request->parent_id])->get();
        $res = '<option value="' . 0 . '" disabled selected>---Select---</option>';
        foreach ($cat as $row) {
            if ($row->id == $request->sub_category) {
                $res .= '<option value="' . $row->id . '" selected >' . $row->name . '</option>';
            } else {
                $res .= '<option value="' . $row->id . '">' . $row->name . '</option>';
            }
        }
        return response()->json([
            'options' => $res,
        ]);
    }

    public function index()
    {
        $categories = Category::where(['position' => 0])->get();
        return view('admin-views.product.index', compact('categories'));
    }

    public function list(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query = Product::where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('name', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        } else {
            $query = new Product();
        }
        $products = $query->orderBy('id', 'DESC')->paginate(Helpers::getPagination())->appends($query_param);
        return view('admin-views.product.list', compact('products', 'search'));
    }

    public function search(Request $request)
    {
        $key = explode(' ', $request['search']);
        $products = Product::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('name', 'like', "%{$value}%");
            }
        })->get();
        return response()->json([
            'view' => view('admin-views.product.partials._table', compact('products'))->render()
        ]);
    }

    public function view($id)
    {
        $product = Product::where(['id' => $id])->first();
        $reviews = Review::where(['product_id' => $id])->latest()->paginate(20);
        return view('admin-views.product.view', compact('product', 'reviews'));
    }

    public function store(Request $request)
    {
//        dd($request);
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:products',
            'category_id' => 'required',
            'image' => 'required',
            'price' => 'required|numeric',
            // 'product_type' => 'required|in:veg,non_veg',
            'meal_price' => Rule::requiredIf($request->has("has_meal_deal")),
        ], [
            'name.required' => translate('Product name is required!'),
            'meal_price.required' => translate('Cannot set a meal deal without a price'),
            'name.unique' => translate('Product name has been taken.'),
            'category_id.required' => translate('category  is required!'),
        ]);

        if ($request['discount_type'] == 'percent') {
            $dis = ($request['price'] / 100) * $request['discount'];
        } else {
            $dis = $request['discount'];
        }

        if ($request['price'] <= $dis) {
            $validator->getMessageBag()->add('unit_price', translate('Discount can not be more or equal to the price!'));
        }

        
        if ($request['price'] <= $dis || $validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        
        $img_names = [];
        if (!empty($request->file('images'))) {
            foreach ($request->images as $img) {
                $image_data = Helpers::upload('product/', 'png', $img);
                array_push($img_names, $image_data);
            }
            $image_data = json_encode($img_names);
        } else {
            $image_data = json_encode([]);
        }

        $product = new Product;
        $product->name = $request->name[array_search('en', $request->lang)];

        $category = [];
        if ($request->category_id != null) {
            array_push($category, [
                'id' => $request->category_id,
                'position' => 1,
            ]);
        }
        if ($request->sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_category_id,
                'position' => 2,
            ]);
        }
        if ($request->sub_sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_sub_category_id,
                'position' => 3,
            ]);
        }

        $product->category_ids = json_encode($category);
        $product->description = strip_tags($request->description[array_search('en', $request->lang)]);

        $choice_options = [];
        if ($request->has('choice')) {
            foreach ($request->choice_no as $key => $no) {
                $str = 'choice_options_' . $no;
                if ($request[$str][0] == null) {
                    $validator->getMessageBag()->add('name', translate('Attribute choice option values can not be null!'));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                $item['name'] = 'choice_' . $no;
                $item['title'] = $request->choice[$key];
                $item['options'] = explode(',', implode('|', preg_replace('/\s+/', ' ', $request[$str])));
                array_push($choice_options, $item);
            }
        }
        $product->choice_options = json_encode($choice_options);
        $variations = [];
        $options = [];
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('|', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }
        //Generates the combinations of customer choice options
        $combinations = Helpers::combinations($options);
        if (count($combinations[0]) > 0) {
            foreach ($combinations as $key => $combination) {
                $str = '';
                foreach ($combination as $k => $item) {
                    if ($k > 0) {
                        $str .= '-' . str_replace('', '', $item);
                    } else {
                        $str .= str_replace('', '', $item);
                    }
                }
                $item = [];
                $item['type'] = $str;
                Log::info($request);
                $price = $request['price_' . str_replace(' ', '_', $str)];
                $item['price'] = abs($price);
                $item['var_meal_price'] = $request->has('has_meal_deal') ? abs($request['meal_price_' . str_replace(' ', '_', $str)]):0;
                array_push($variations, $item);
            }
        }
        //combinations end
        $product->variations = json_encode($variations);
        $product->price = $request->price;
        $product->set_menu = $request->item_type;
        $product->product_type = $request->has('product_type') ? $request->product_type:null;
        $product->image = Helpers::upload('product/', 'png', $request->file('image'));
        $product->available_time_starts = $request->available_time_starts;
        $product->available_time_ends = $request->available_time_ends;

        $product->tax = $request->tax_type == 'amount' ? $request->tax : $request->tax;
        $product->tax_type = $request->tax_type;

        $product->discount = $request->discount_type == 'amount' ? $request->discount : $request->discount;
        $product->discount_type = $request->discount_type;

        $product->attributes = $request->has('attribute_id') ? json_encode($request->attribute_id) : json_encode([]);
        $product->add_ons = $request->has('addon_ids') ? json_encode($request->addon_ids) : json_encode([]);
        $product->status = $request->status == 'on' ? 1 : 0;
        //Added by Me Sopan
        $product->structure = $request->has('structure') ? $request->structure:null;
        $product->meal_price  = $request->has('meal_price') && $request->has('has_meal_deal') ? $request->meal_price:null;
        $product->sides  = $request->has('sides') ? $request->sides:null;
        $product->drinks  = $request->has('drinks') ? $request->drinks:null;
        $product->dips  = $request->has('dips') ? $request->dips:null;
        $product->item_ttl_free = $request->has('item_ttl_free') ? $request->item_ttl_free:0;
        
        $product->is_exclusive = $request->has('is_exclusive') ? $request->is_exclusive:0;
        if($product->is_exclusive == 1){
            $exclusives = Product::where('is_exclusive',1)->count();
            if($exclusives >= 12){
                $validator->getMessageBag()->add('name', translate('Cannot have more than 12 exclusive items!'));
                return response()->json(['errors' => Helpers::error_processor($validator)]);
            } 
        }
        //Added by Me Sopan end

        $product->save();

        $data = [];
        foreach ($request->lang as $index => $key) {
            if ($request->name[$index] && $key != 'en') {
                array_push($data, array(
                    'translationable_type' => 'App\Model\Product',
                    'translationable_id' => $product->id,
                    'locale' => $key,
                    'key' => 'name',
                    'value' => $request->name[$index],
                ));
            }
            if ($request->description[$index] && $key != 'en') {
                array_push($data, array(
                    'translationable_type' => 'App\Model\Product',
                    'translationable_id' => $product->id,
                    'locale' => $key,
                    'key' => 'description',
                    'value' => strip_tags($request->description[$index]),
                ));
            }
        }


        Translation::insert($data);

        return response()->json([], 200);
    }

    public function edit($id)
    {
        $product = Product::withoutGlobalScopes()->with('translations')->find($id);
        $product_category = json_decode($product->category_ids);
        $categories = Category::where(['parent_id' => 0])->get();
        return view('admin-views.product.edit', compact('product', 'product_category', 'categories'));
    }

    public function status(Request $request)
    {
        $product = Product::find($request->id);
        $product->status = $request->status;
        $product->save();
        Toastr::success(translate('Product status updated!'));
        return back();
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:products,name,' . $id,
            'category_id' => 'required',
            'price' => 'required|numeric',
        ], [
            'name.required' => translate('Product name is required!'),
            'category_id.required' => translate('category  is required!'),
        ]);

        if ($request['discount_type'] == 'percent') {
            $dis = ($request['price'] / 100) * $request['discount'];
        } else {
            $dis = $request['discount'];
        }

        if ($request['price'] <= $dis) {
            $validator->getMessageBag()->add('unit_price', translate('Discount can not be more or equal to the price!'));
        }

        if ($request['price'] <= $dis || $validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $product = Product::find($id);
        $product->name = $request->name[array_search('en', $request->lang)];

        $category = [];
        if ($request->category_id != null) {
            array_push($category, [
                'id' => $request->category_id,
                'position' => 1,
            ]);
        }
        if ($request->sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_category_id,
                'position' => 2,
            ]);
        }
        if ($request->sub_sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_sub_category_id,
                'position' => 3,
            ]);
        }

        $product->category_ids = json_encode($category);
        $product->description = strip_tags($request->description[array_search('en', $request->lang)]);

        $choice_options = [];
        if ($request->has('choice')) {
            foreach ($request->choice_no as $key => $no) {
                $str = 'choice_options_' . $no;
                if ($request[$str][0] == null) {
                    $validator->getMessageBag()->add('name', translate('Attribute choice option values can not be null!'));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                $item['name'] = 'choice_' . $no;
                $item['title'] = $request->choice[$key];
                $opts = explode(',', implode('|', preg_replace('/\s+/', ' ', $request[$str])));
                foreach ($opts as $key => $opt) {
                    $opts[$key] = preg_replace('/^[ \t]+/','',$opt);
                    // Log::info($opts[$key]);
                  } 
                $item['options'] = $opts;
                array_push($choice_options, $item);
            }
        }
        $product->choice_options = json_encode($choice_options);
        $variations = [];
        $options = [];
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('|', $request[$name]);
                // Added by Me
                $my_choices = explode(',', $my_str);
                foreach ($my_choices as $key => $ch) {
                    $my_choices[$key] = preg_replace('/^[ \t]+/','',$ch);
                    // Log::info($my_choices[$key]);
                  } 
                array_push($options, $my_choices);
            }
        }
        //Generates the combinations of customer choice options
        $combinations = Helpers::combinations($options);
        // Log::info($options);
        if (count($combinations[0]) > 0) {
            foreach ($combinations as $key => $combination) {
                $str = '';
                foreach ($combination as $k => $item) {
                    if ($k > 0) {
                        $str .= '-' . str_replace('', '', $item);
                    } else {
                        $str .= str_replace('', '', $item);
                    }
                }
                $item = [];
                $item['type'] = $str;
                Log::info($request);
                $price = $request['price_' . str_replace(' ', '_', $str)];
                $item['price'] = abs($price);
                $item['var_meal_price'] = $request->has('has_meal_deal') ? abs($request['meal_price_' . str_replace(' ', '_', $str)]):0;
                Log::info($item);
                array_push($variations, $item);
            }
        }
        //combinations end
        $product->variations = json_encode($variations);
        $product->price = $request->price;
        $product->set_menu = $request->item_type;
        // $product->product_type = $request->product_type;
        $product->product_type = $request->has('product_type') ? $request->product_type:null;
        $product->image = $request->has('image') ? Helpers::update('product/', $product->image, 'png', $request->file('image')) : $product->image;
        $product->available_time_starts = $request->available_time_starts;
        $product->available_time_ends = $request->available_time_ends;

        $product->tax = $request->tax_type == 'amount' ? $request->tax : $request->tax;
        $product->tax_type = $request->tax_type;

        $product->discount = $request->discount_type == 'amount' ? $request->discount : $request->discount;
        $product->discount_type = $request->discount_type;

        $product->attributes = $request->has('attribute_id') ? json_encode($request->attribute_id) : json_encode([]);
        $product->add_ons = $request->has('addon_ids') ? json_encode($request->addon_ids) : json_encode([]);
        $product->status = $request->status == 'on' ? 1 : 0;
        

         //Added by Me Sopan
         $product->structure = $request->has('structure') ? $request->structure:null;
         $product->meal_price  = $request->has('meal_price') && $request->has('has_meal_deal') ? $request->meal_price:null;
         $product->sides  = $request->has('sides') ? $request->sides:null;
         $product->drinks  = $request->has('drinks') ? $request->drinks:null;
         $product->dips  = $request->has('dips') ? $request->dips:null;
         $product->item_ttl_free = $request->has('item_ttl_free') ? $request->item_ttl_free:0;
         $product->is_exclusive = $request->has('is_exclusive') ? $request->is_exclusive:0;
         $product->is_exclusive = $request->has('is_exclusive') ? $request->is_exclusive:0;
         if($product->is_exclusive == 1){
             $exclusives = Product::where('is_exclusive',1)->count();
             if($exclusives >= 12){
                 $validator->getMessageBag()->add('name', translate('Cannot have more than 12 exclusive items!'));
                 return response()->json(['errors' => Helpers::error_processor($validator)]);
             } 
         }
         //Added by Me Sopan end
         
        $product->save();

        foreach ($request->lang as $index => $key) {
            if ($request->name[$index] && $key != 'en') {
                Translation::updateOrInsert(
                    ['translationable_type' => 'App\Model\Product',
                        'translationable_id' => $product->id,
                        'locale' => $key,
                        'key' => 'name'],
                    ['value' => $request->name[$index]]
                );
            }
            if ($request->description[$index] && $key != 'en') {
                Translation::updateOrInsert(
                    ['translationable_type' => 'App\Model\Product',
                        'translationable_id' => $product->id,
                        'locale' => $key,
                        'key' => 'description'],
                    ['value' => strip_tags($request->description[$index])]
                );
            }
        }

        return response()->json([], 200);
    }

    public function delete(Request $request)
    {
        $product = Product::find($request->id);
        Helpers::delete('product/' . $product['image']);
        $product->delete();
        Toastr::success(translate('Product removed!'));
        return back();
    }

    public function bulk_import_index()
    {
        return view('admin-views.product.bulk-import');
    }

    public function bulk_import_data(Request $request)
    {
        try {
            $collections = (new FastExcel)->import($request->file('products_file'));
        } catch (\Exception $exception) {
            Toastr::error(translate('You have uploaded a wrong format file, please upload the right file.'));
            return back();
        }

        //check
        $field_array = ['name', 'description', 'price', 'tax', 'category_id', 'sub_category_id', 'discount', 'discount_type', 'tax_type', 'set_menu', 'available_time_starts', 'available_time_ends', 'product_type'];
        if(count($collections) < 1) {
            Toastr::error(translate('At least one product have to import.'));
            return back();
        }
        foreach ($field_array as $field) {
            if(!array_key_exists($field, $collections->first())) {
                Toastr::error(translate($field) . translate(' must not be empty.'));
                return back();
            }
        }

        $data = [];
        foreach ($collections as $key => $collection) {
            if ($collection['name'] === "") {
                Toastr::error(translate('Please fill name field of row') . ' ' . ($key + 2));
                return back();
            }
            if ($collection['description'] === "") {
                Toastr::error(translate('Please fill description field of row') . ' ' . ($key + 2));
                return back();
            }
            if ($collection['price'] === "") {
                Toastr::error(translate('Please fill price field of row') . ' ' . ($key + 2));
                return back();
            }
            if ($collection['tax'] === "") {
                Toastr::error(translate('Please fill tax field of row') . ' ' . ($key + 2));
                return back();
            }
            if ($collection['category_id'] === "") {
                Toastr::error(translate('Please fill category_id field of row') . ' ' . ($key + 2));
                return back();
            }
            if ($collection['sub_category_id'] === "") {
                Toastr::error(translate('Please fill sub_category_id field of row') . ' ' . ($key + 2));
                return back();
            }
            if ($collection['discount'] === "") {
                Toastr::error(translate('Please fill discount field of row') . ' ' . ($key + 2));
                return back();
            }
            if ($collection['discount_type'] === "") {
                Toastr::error(translate('Please fill discount_type field of row') . ' ' . ($key + 2));
                return back();
            }
            if ($collection['tax_type'] === "") {
                Toastr::error(translate('Please fill tax_type field of row') . ' ' . ($key + 2));
                return back();
            }
            if ($collection['set_menu'] === "") {
                Toastr::error(translate('Please fill set_menu field of row') . ' ' . ($key + 2));
                return back();
            }

            if ($collection['product_type'] === "") {
                Toastr::error(translate('Please fill product_type field of row') . ' ' . ($key + 2));
                return back();
            }

            if (!is_numeric($collection['price'])) {
                Toastr::error(translate('Price of row') . ' ' . ($key + 2) .  ' ' . translate('must be number'));
                return back();
            }

            if (!is_numeric($collection['discount'])) {
                Toastr::error(translate('Discount of row') . ' ' . ($key + 2) . ' ' . translate('must be number'));
                return back();
            }

            if (!is_numeric($collection['tax'])) {
                Toastr::error(translate('Tax of row') . ' ' . ($key + 2) . ' ' . ' must be number');
                return back();
            }

            $product = [
                'discount_type' => $collection['discount_type'],
                'discount' => $collection['discount'],
            ];
            if ($collection['price'] <= Helpers::discount_calculate($product, $collection['price'])) {
                Toastr::error(translate('Discount can not be more or equal to the price in row') . ' ' . ($key + 2));
                return back();
            }
            if (!isset($collection['available_time_starts'])) {
                Toastr::error(translate('Please fill available_time_starts field'));
                return back();
            } elseif ($collection['available_time_starts'] === "") {
                Toastr::error(translate('Please fill available_time_starts field of row') . ' ' . ($key + 2));
                return back();
            }
            if (!isset($collection['available_time_ends'])) {
                Toastr::error(translate('Please fill available_time_ends field'));
                return back();
            } elseif ($collection['available_time_ends'] === "") {
                Toastr::error(translate('Please fill available_time_ends field of row ') . ' ' . ($key + 2));
                return back();
            }
        }

        foreach ($collections as $collection) {
            array_push($data, [
                'name' => $collection['name'],
                'description' => $collection['description'],
                'image' => 'def.png',
                'price' => $collection['price'],
                'variations' => json_encode([]),
                'add_ons' => json_encode([]),
                'tax' => $collection['tax'],
                'available_time_starts' => $collection['available_time_starts'],
                'available_time_ends' => $collection['available_time_ends'],
                'status' => 1,
                'attributes' => json_encode([]),
                'category_ids' => json_encode([['id' => (string)$collection['category_id'], 'position' => 1], ['id' => (string)$collection['sub_category_id'], 'position' => 2]]),
                'choice_options' => json_encode([]),
                'discount' => $collection['discount'],
                'discount_type' => $collection['discount_type'],
                'tax_type' => $collection['tax_type'],
                'set_menu' => $collection['set_menu'],
                'product_type' => $collection['product_type'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        DB::table('products')->insert($data);
        Toastr::success(count($data) . ' - ' . translate('Products imported successfully!'));
        return back();
    }

    public function bulk_export_data(Request $request)
    {
        if ($request->type == 'date_wise') {
            $request->validate([
                'start_date' => 'required',
                'end_date' => 'required'
            ]);
        }
        $start_date = Carbon::parse($request->start_date)->startOfDay();
        $end_date = Carbon::parse($request->end_date)->endOfDay();

        $products = Product::when($request['type'] == 'date_wise', function ($query) use ($start_date, $end_date) {
            $query->whereBetween('created_at', [$start_date, $end_date]);
        })
            ->get();
        $storage = [];


        if ($products->count() <1 ) {
            Toastr::info(translate('no_product_found'));
            return back();
        }

        foreach ($products as $item) {
            $category_id = 0;
            $sub_category_id = 0;
            foreach (json_decode($item->category_ids, true) as $category) {
                if ($category['position'] == 1) {
                    $category_id = $category['id'];
                } else if ($category['position'] == 2) {
                    $sub_category_id = $category['id'];
                }
            }

            if (!isset($item->name)) {
                $item->name = 'Demo Product';
            }

            if (!isset($item->description)) {
                $item->description = 'No description available';
            }

            array_push($storage,[
                'name' => $item->name,
                'description' => $item->description,
                'category_id' => $category_id,
                'sub_category_id' => $sub_category_id,
                'price' => $item->price,
                'tax' => $item->tax,
                'available_time_starts' => $item->available_time_starts,
                'available_time_ends' => $item->available_time_ends,
                'status' => $item->status,
                'discount' => $item->discount,
                'discount_type' => $item->discount_type,
                'tax_type' => $item->tax_type,
                'set_menu' => $item->set_menu,
                'product_type' => $item->product_type,
            ]);
        }
        return (new FastExcel($storage))->download('products.xlsx');
    }

    public function excel_import(Request $request)
    {
        $storage =[];
        $search = $request['search'];
        $products = Product::when($search, function ($query) use($search){
            $key = explode(' ', $search);
            foreach ($key as $value) {
                $query->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('name', 'like', "%{$value}%");
                };
        })
            ->get();

        foreach ($products as $item) {
            $category_id = 0;
            $sub_category_id = 0;
            foreach (json_decode($item->category_ids, true) as $category) {
                if ($category['position'] == 1) {
                    $category_id = $category['id'];
                } elseif ($category['position'] == 2) {
                    $sub_category_id = $category['id'];
                }
            }
            if (!isset($item->name)) {
                $item->name = 'Demo Product';
            }
            if (!isset($item->description)) {
                $item->description = 'No description available';
            }
            array_push($storage , array(
                'Name' => $item->name,
                'Description' => $item->description,
                'Category ID' => $category_id,
                'Sub Category ID' => $sub_category_id,
                'Price' => $item->price,
                'Tax' => $item->tax,
                'Available Time Starts' => $item->available_time_starts,
                'Available Time Ends' => $item->available_time_ends,
                'Status' => $item->status,
                'Discount' => $item->discount,
                'Discount Type' => $item->discount_type,
                'Tax Type' => $item->tax_type,
                'Set Menu' => $item->set_menu,
                'Product Type' => $item->product_type,
            ));
        }
        return (new FastExcel($storage))->download('products.xlsx');
    }

    public function bulk_export_index()
    {
        return view('admin-views.product.bulk-export');
    }
}
