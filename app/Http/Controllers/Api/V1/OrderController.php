<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\CentralLogics\OrderLogic;
use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Model\CustomerAddress;
use App\Model\DMReview;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Model\Review;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use function App\CentralLogics\translate;

class OrderController extends Controller
{
    public function track_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        return response()->json(OrderLogic::track_order($request['order_id']), 200);
    }

    public function place_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_amount' => 'required',
            'delivery_address_id' => 'required',
            'order_type' => 'required',
            'branch_id' => 'required',
            'delivery_time' => 'required',
            'delivery_date' => 'required',
            'distance' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        //order scheduling
        if ($request['delivery_time'] == 'now') {
            $del_date = Carbon::now()->format('Y-m-d');
            $del_time = Carbon::now()->format('H:i:s');
        } else {
            $del_date = $request['delivery_date'];
            $del_time = $request['delivery_time'];
        }

        try {
            $order_id = 100000 + Order::all()->count() + 1;
            $or = [
                'id' => $order_id,
                'user_id' => $request->user()->id,
                'order_amount' => Helpers::set_price($request['order_amount']),
                'coupon_discount_amount' => Helpers::set_price($request->coupon_discount_amount),
                'coupon_discount_title' => $request->coupon_discount_title == 0 ? null : 'coupon_discount_title',
                'payment_status' => ($request->payment_method=='cash_on_delivery')?'unpaid':'paid',
                // 'order_status' => ($request->payment_method=='cash_on_delivery')?'pending':'confirmed',
                'order_status' => 'pending',
                'coupon_code' => $request['coupon_code'],
                'payment_method' => $request->payment_method,
                'transaction_reference' => $request->transaction_reference ?? null,

                'order_note' => $request['order_note'],

                'order_type' => $request['order_type'],
                'branch_id' => $request['branch_id'],
                'delivery_address_id' => $request->delivery_address_id,

                'delivery_date' => $del_date,
                'delivery_time' => $del_time,
                'delivery_address' => json_encode(CustomerAddress::find($request->delivery_address_id) ?? null),

                'delivery_charge' => Helpers::get_delivery_charge($request['distance']),
                'preparation_time' => Helpers::get_business_settings('default_preparation_time') ?? 0,

//                'table_id' => $request['table_id'],
//                'number_of_people' => $request['number_of_people'],

                'created_at' => now(),
                'updated_at' => now()
            ];
            $total_tax_amount = 0 ;

            foreach ($request['cart'] as $c) {
                //Added by Me:Change to match meal deal
                $product = Product::find($c['product_id']);
                // Log::info($c);
                if (array_key_exists('variation', $c) && count(json_decode($product['variations'], true)) > 0) {
                    $price = Helpers::variation_price($product, json_encode($c['variation']),$c['is_meal']);
                } else {
                    if($c['is_meal'] == 1){
                        $price = Helpers::set_price($product['meal_price']);
                    }else{
                        $price = Helpers::set_price($product['price']);
                    }
                }
                
                $or_d = [
                    'order_id' => $order_id,
                    'product_id' => $c['product_id'],
                    'product_details' => $product,
                    'quantity' => $c['quantity'],
                    'price' => $price,
                    'tax_amount' => Helpers::tax_calculate($product, $price),
                    'discount_on_product' => Helpers::discount_calculate($product, $price),
                    'discount_type' => 'discount_on_product',
                    'variant' => json_encode($c['variant']),
                    // 'variation' => array_key_exists('variation', $c) ? json_encode($c['variation']) : json_encode([]),
                    'variation' => (array_key_exists('variation', $c) && $c["variation"][0]['price'] != null) ? json_encode($c['variation']) : null,
                    'add_on_ids' => json_encode($c['add_on_ids']),
                    'add_on_qtys' => json_encode($c['add_on_qtys']),
                    //Added by Me
                    'is_meal' => array_key_exists("is_meal",$c) ? $c["is_meal"]:0,
                    'sides' => array_key_exists("sides",$c) ? json_encode($c["sides"]):null,
                    'drinks' => array_key_exists("drinks",$c) ? json_encode($c["drinks"]):null,
                    'dips' => array_key_exists("dips",$c) ? json_encode($c["dips"]):null,
                    // 'items' => array_key_exists("items",$c) ? json_encode($c["items"]):null,
                    'items' => (array_key_exists("items",$c) && $c["items"][0]['quantity'] != -1) ? json_encode($c["items"]):null,
                    'items_price' =>  Helpers::set_price($c['items_price']),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                // Log::info($or_d);
                $total_tax_amount += $or_d['tax_amount'] * $c['quantity'];
         
                DB::table('order_details')->insert($or_d);

                //update product popularity point
                Product::find($c['product_id'])->increment('popularity_count');
            }

            $or['total_tax_amount'] = $total_tax_amount;
            $o_id = DB::table('orders')->insertGetId($or);

            $fcm_token = $request->user()->cm_firebase_token;
            $value = Helpers::order_status_update_message(($request->payment_method=='cash_on_delivery')?'pending':'confirmed');
            try {
                //send push notification
                if ($value) {
                    $data = [
                        'title' => translate('Order'),
                        'description' => $value,
                        'order_id' => $order_id,
                        'image' => '',
                        'type'=>'order_status',
                    ];
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                }

                //send email
                $emailServices = Helpers::get_business_settings('mail_config');
                if (isset($emailServices['status']) && $emailServices['status'] == 1) {
                    Mail::to($request->user()->email)->send(new \App\Mail\OrderPlaced($order_id));
                }

            } catch (\Exception $e) {

            }

            if($or['order_status'] == 'confirmed') {
                $data = [
                    'title' => translate('You have a new order - (Order Confirmed).'),
                    'description' => $order_id,
                    'order_id' => $order_id,
                    'image' => '',
                ];

                try {
                    Helpers::send_push_notif_to_topic($data, "kitchen-{$or['branch_id']}",'general');

                } catch (\Exception $e) {
                    Toastr::warning(translate('Push notification failed!'));
                }
            }

            return response()->json([
                'message' => translate('order_success'),
                'order_id' => $order_id
            ], 200);

        } catch (\Exception $e) {
            Log::info($e);
            return response()->json([$e], 403);
        }
    }

    public function get_order_list(Request $request)
    {
        $orders = Order::with(['customer', 'delivery_man.rating'])
            ->withCount('details')
            ->where(['user_id' => $request->user()->id])->get();

        $orders->map(function ($data) {
            $data['deliveryman_review_count'] = DMReview::where(['delivery_man_id' => $data['delivery_man_id'], 'order_id' => $data['id']])->count();

            //is product available
            $order_id = $data->id;
            $order_details = OrderDetail::where('order_id', $order_id)->first();
            $product_id = null;
            $product = null;
            if(isset($order_details))
                $product_id = $order_details->product_id;

            if(isset($product_id))
                $product = Product::find($product_id);

            $data['is_product_available'] = isset($product) ? 1 : 0;


            return $data;
        });

        return response()->json($orders->map(function ($data) {
            $data->details_count = (integer)$data->details_count;
            return $data;
        }), 200);
    }

    public function get_order_details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $details = OrderDetail::with(['order'])->withCount(['reviews'])->where(['order_id' => $request['order_id']])->get();
        if ($details->count() < 1) {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('not found!')]
                ]
            ], 404);
        }

        $details = Helpers::order_details_formatter($details);
        return response()->json($details, 200);
    }

    public function cancel_order(Request $request)
    {
        if (Order::where(['user_id' => $request->user()->id, 'id' => $request['order_id']])->first()) {
            Order::where(['user_id' => $request->user()->id, 'id' => $request['order_id']])->update([
                'order_status' => 'canceled'
            ]);
            return response()->json(['message' => translate('order_canceled')], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('no_data_found')]
            ]
        ], 401);
    }

    public function update_payment_method(Request $request)
    {
        if (Order::where(['user_id' => $request->user()->id, 'id' => $request['order_id']])->first()) {
            Order::where(['user_id' => $request->user()->id, 'id' => $request['order_id']])->update([
                'payment_method' => $request['payment_method']
            ]);
            return response()->json(['message' => translate('payment_method_updated')], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('no_data_found')]
            ]
        ], 401);
    }
}
