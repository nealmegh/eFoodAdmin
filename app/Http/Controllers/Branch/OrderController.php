<?php

namespace App\Http\Controllers\Branch;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Order;
use App\Model\TableOrder;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Rap2hpoutre\FastExcel\FastExcel;
use function App\CentralLogics\translate;

class OrderController extends Controller
{
    public function list($status, Request $request)
    {
        $from = $request['from'];
        $to = $request['to'];

        Order::where(['checked' => 0, 'branch_id' => auth('branch')->id()])->update(['checked' => 1]);
        if ($status == 'all') {
            $orders = Order::with(['customer'])->where(['branch_id' => auth('branch')->id()]);
        } elseif ($status == 'schedule') {
            $orders = Order::whereDate('delivery_date','>', \Carbon\Carbon::now()->format('Y-m-d'))
                ->where(['branch_id' => auth('branch')->id()]);
        } else {
            $orders = Order::with(['customer'])
                ->where(['order_status' => $status, 'branch_id' => auth('branch')->id()])
                ->whereDate('delivery_date','<=',\Carbon\Carbon::now()->format('Y-m-d'));
        }

        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $orders = Order::where(['branch_id' => auth('branch')->id()])
                ->whereDate('delivery_date', '<=', \Carbon\Carbon::now()->format('Y-m-d'))
                ->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%")
                            ->orWhere('order_status', 'like', "%{$value}%")
                            ->orWhere('transaction_reference', 'like', "%{$value}%");
                    }
                });
            $query_param = ['search' => $request['search']];
        }
        if ($from && $to) {
            $orders = Order::whereBetween('created_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);
            $query_param = ['from' => $from, 'to' => $to];
        }

        $order_count = [
            'pending' =>    Order::notPos()->notDineIn()->where(['order_status'=>'pending'])->notSchedule()
                ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                    $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                })->count(),
            'accepted' =>  Order::notPos()->notDineIn()->where(['order_status'=>'accepted'])->notSchedule()
                ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                    $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                })->count(),
            'declined' => Order::notPos()->notDineIn()->where(['order_status'=>'declined'])->notSchedule()
                ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                    $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                })->count(),
            'completed' => Order::notPos()->notDineIn()->where(['order_status'=>'completed'])->notSchedule()
            ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
            })->count(),
            // 'out_for_delivery' => Order::notPos()->notDineIn()->where(['order_status'=>'out_for_delivery'])->notSchedule()
            //     ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
            //         $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
            //     })->count(),
            // 'delivered' =>  Order::notPos()->notDineIn()->where(['order_status'=>'delivered'])
            //     ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
            //         $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
            //     })->count(),
            'canceled' =>   Order::notPos()->notDineIn()->where(['order_status'=>'canceled'])->notSchedule()
                ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                    $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                })->count(),
            // 'returned' =>   Order::notPos()->notDineIn()->where(['order_status'=>'returned'])->notSchedule()
            //     ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
            //         $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
            //     })->count(),
            // 'failed' =>     Order::notPos()->notDineIn()->where(['order_status'=>'failed'])->notSchedule()
            //     ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
            //         $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
            //     })->count(),
        ];

        $orders = $orders->notPos()->notDineIn()->latest()->paginate(Helpers::getPagination())->appends($query_param);
        session()->put('order_data_export', $orders);
        return view('branch-views.order.list', compact('orders', 'status', 'search', 'from', 'to', 'order_count'));
    }

    public function search(Request $request)
    {
        $key = explode(' ', $request['search']);
        $orders = Order::where(['branch_id' => auth('branch')->id()])->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('id', 'like', "%{$value}%")
                    ->orWhere('order_status', 'like', "%{$value}%")
                    ->orWhere('transaction_reference', 'like', "%{$value}%");
            }
        })->get();
        return response()->json([
            'view' => view('branch-views.order.partials._table', compact('orders'))->render()
        ]);
    }

    public function details($id)
    {
        $order = Order::with(['details', 'customer', 'delivery_address', 'branch', 'delivery_man'])
            ->where(['id' => $id])
            ->first();

//        dd ($order->branch);
        
        
        if(!isset($order)) {
            Toastr::info(translate('No more orders!'));
            return back();
        }
        

        // Added by Me
        if($order->checked == 0){
            $order->checked =1;
            $order->save();
        }

        //remaining delivery time
        $delivery_date_time =  $order['delivery_date']. ' ' .$order['delivery_time'];
        $ordered_time = Carbon::createFromFormat('Y-m-d H:i:s', date("Y-m-d H:i:s", strtotime($delivery_date_time)));
        $remaining_time = $ordered_time->add($order['preparation_time'], 'minute')->format('Y-m-d H:i:s');
        $order['remaining_time'] = $remaining_time;
        
        // Log::info($order);


        return view('branch-views.order.order-view', compact('order'));
    }

    // public function status(Request $request)
    // {
    //     $order = Order::where(['id' => $request->id, 'branch_id' => auth('branch')->id()])->first();
    //     if (($request->order_status == 'delivered' || $request->order_status == 'out_for_delivery') && $order['delivery_man_id'] == null && $order['order_type'] != 'take_away') {
    //         Toastr::warning(translate('Please assign delivery man first!'));
    //         return back();
    //     }
    //     $order->order_status = $request->order_status;
    //     if($request->order_status == 'delivered') {
    //         $order->payment_status = 'paid';
    //     }
    //     $order->save();

    //     $fcm_token=null;
    //     if($order->customer) {
    //         $fcm_token = $order->customer->cm_firebase_token;
    //     }
    //     $value = Helpers::order_status_update_message($request->order_status);
    //     try {
    //         if ($value) {
    //             $data = [
    //                 'title' => translate('Order'),
    //                 'description' => $value,
    //                 'order_id' => $order['id'],
    //                 'image' => '',
    //                 'type'=>'order_status',
    //             ];
    //             if(isset($fcm_token)) {
    //                 Helpers::send_push_notif_to_device($fcm_token, $data);
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         Toastr::warning(translate('Push notification failed for Customer!'));
    //     }

    //     //delivery man notification
    //     if ($request->ordeurrent_pager_status == 'processing' && $order->delivery_man != null) {
    //         $fcm_token = $order->delivery_man->fcm_token;
    //         $value = translate('One of your order is in processing');
    //         try {
    //             if ($value) {
    //                 $data = [
    //                     'title' => translate('Order'),
    //                     'description' => $value,
    //                     'order_id' => $order['id'],
    //                     'image' => '',
    //                     'type'=>'order_status',
    //                 ];
    //                 Helpers::send_push_notif_to_device($fcm_token, $data);
    //             }
    //         } catch (\Exception $e) {
    //             Toastr::warning(translate('Push notification failed for DeliveryMan!'));
    //         }
    //     }

    //     Toastr::success(translate('Order status updated!'));
    //     return back();
    // }

    public function status(Request $request)
    {
        $order = Order::find($request->id);
        // if (($request->order_status == 'delivered' || $request->order_status == 'out_for_delivery') && $order['delivery_man_id'] == null && $order['order_type'] != 'take_away') {
        //     Toastr::warning(translate('Please assign delivery man first!'));
        //     return back();
        // }
        if($request->order_status == 'completed' && $order->payment_status != 'paid') {
            Toastr::warning(translate('Please update payment status first!'));
            return back();
        }
        $order->order_status = $request->order_status;
        if($order->checked == 0){
            $order->checked =1;
            // $order->save();
        }
        $order->save();

        $fcm_token = null;
        if(isset($order->customer)) {
            $fcm_token = $order->customer->cm_firebase_token;
        }

        $value = Helpers::order_status_update_message($request->order_status);
        try {
            if ($value) {
                $data = [
                    'title' => translate('Order'),
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                    'type'=>'order_status',
                ];
                if(isset($fcm_token)) {
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                }

            }
        } catch (\Exception $e) {
            Toastr::warning(translate('Push notification send failed for Customer!'));
        }
        //Email to user
        $emailServices = Helpers::get_business_settings('mail_config');
        $user=DB::table("users")->where('id', $order->user_id)->first();
        if (isset($emailServices['status']) && $emailServices['status'] == 1) {
            Mail::to($user->email)->send(new \App\Mail\OrderStatus($order->id,$request->order_status));
        }
        //delivery man notification
        // if ($request->order_status == 'processing' && $order->delivery_man != null) {
        //     $fcm_token = $order->delivery_man->fcm_token;
        //     $value = translate('One of your order is in processing');
        //     try {
        //         if ($value) {
        //             $data = [
        //                 'title' => translate('Order'),
        //                 'description' => $value,
        //                 'order_id' => $order['id'],
        //                 'image' => '',
        //             ];
        //             Helpers::send_push_notif_to_device($fcm_token, $data);
        //         }
        //     } catch (\Exception $e) {
        //         Toastr::warning(translate('Push notification failed for DeliveryMan!'));
        //     }
        // }

        //kitchen order notification
        if($request->order_status == 'confirmed') {
            $data = [
                'title' => translate('You have a new order - (Order Confirmed).'),
                'description' => $order->id,
                'order_id' => $order->id,
                'image' => '',
            ];

            try {
                Helpers::send_push_notif_to_topic($data, "kitchen-{$order->branch_id}",'general');

            } catch (\Exception $e) {
                Toastr::warning(translate('Push notification failed!'));
            }
        }
        $table_order = TableOrder::where(['id' => $order->table_order_id])->first();

        if($request->order_status == 'completed' && $order->payment_status == 'paid') {

            if (isset($table_order->id)){
                //dd($table_order);
                $orders = Order::where(['table_order_id' => $table_order->id])->get();
                $status = 1;
                foreach ($orders as $order){
                    if($order->order_status != 'completed'){
                        $status=0;
                        break;
                    }
                }

                if ($status == 1){
                    $table_order->branch_table_token_is_expired = 1;
                    //dd($table_order);
                    $table_order->save();
                }
            }
        }

        if($request->order_status == 'canceled') {

            if (isset($table_order->id)){
                //dd($table_order);
                $orders = Order::where(['table_order_id' => $table_order->id])->get();
                $status = 1;
                foreach ($orders as $order){
                    if($order->order_status != 'canceled'){
                        $status=0;
                        break;
                    }
                }

                if ($status == 1){
                    $table_order->branch_table_token_is_expired = 1;
                    //dd($table_order);
                    $table_order->save();
                }
            }
        }

        Toastr::success(translate('Order status updated!'));
        // if($order->order_status == 'accepted'){
        //     return Redirect::to("admin/orders/generate-invoice/{$order->id}");
        // }
        return back();
    }

    public function preparation_time(Request $request, $id)
    {
        $order = Order::with(['customer'])->find($id);
        $delivery_date_time =  $order['delivery_date']. ' ' .$order['delivery_time'];

        $ordered_time = Carbon::createFromFormat('Y-m-d H:i:s', date("Y-m-d H:i:s", strtotime($delivery_date_time)));
        $remaining_time = $ordered_time->add($order['preparation_time'], 'minute')->format('Y-m-d H:i:s');

        //if delivery time is not over
        if (strtotime(date('Y-m-d H:i:s')) < strtotime($remaining_time)) {
            $delivery_time = new DateTime($remaining_time); //time when preparation will be over
            $current_time = new DateTime(); // time now
            $interval = $delivery_time->diff($current_time);
            $remainingMinutes = $interval->i;
            $remainingMinutes += $interval->days * 24 * 60;
            $remainingMinutes += $interval->h * 60;

            $order->preparation_time += ($request->extra_minute - $remainingMinutes);

        } else {
            //if delivery time is over
            $delivery_time = new DateTime($remaining_time);
            $current_time = new DateTime();
            $interval = $delivery_time->diff($current_time);
            $diffInMinutes = $interval->i;
            $diffInMinutes += $interval->days * 24 * 60;
            $diffInMinutes += $interval->h * 60;

            $order->preparation_time += $diffInMinutes + $request->extra_minute;
        }
        $order->save();

        //notification send
        $customer = $order->customer;
        $fcm_token = null;
        if (isset($customer)) {
            $fcm_token = $customer->cm_firebase_token;
        }
        $value = Helpers::order_status_update_message('customer_notify_message_for_time_change');

        try {
            if ($value) {
                $data = [
                    'title' => translate('Order'),
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                    'type'=>'order_status',
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
            } else {
                throw new \Exception(translate('failed'));
            }

        } catch (\Exception $e) {
            Toastr::warning(translate('Push notification send failed for Customer!'));
        }

        Toastr::success(translate('Order preparation time increased'));
        return back();
    }

    public function add_delivery_man($order_id, $delivery_man_id)
    {
        if ($delivery_man_id == 0) {
            return response()->json([], 401);
        }
        $order = Order::where(['id' => $order_id, 'branch_id' => auth('branch')->id()])->first();
        if($order->order_status == 'delivered' || $order->order_status == 'returned' || $order->order_status == 'failed' || $order->order_status == 'canceled' || $order->order_status == 'scheduled') {
            return response()->json(['status' => false], 200);
        }
        $order->delivery_man_id = $delivery_man_id;
        $order->save();

        $fcm_token = $order->delivery_man->fcm_token;
        $customer_fcm_token = null;
        if(isset($order->customer)) {
            $customer_fcm_token = $order->customer->cm_firebase_token;
        }
        $value = Helpers::order_status_update_message('del_assign');
        try {
            if ($value) {
                $data = [
                    'title' => translate('Order'),
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                    'type'=>'order_status',
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
            }
        } catch (\Exception $e) {
            Toastr::warning(translate('Push notification failed for DeliveryMan!'));
        }

        Toastr::success(translate('Order deliveryman added!'));
        return response()->json(['status' => true], 200);
    }

    public function payment_status(Request $request)
    {
        $order = Order::where(['id' => $request->id, 'branch_id' => auth('branch')->id()])->first();
        if ($request->payment_status == 'paid' && $order['transaction_reference'] == null && $order['payment_method'] != 'cash_on_delivery' && $order['order_type'] != 'dine_in') {
            Toastr::warning(translate('Add your payment reference code first!'));
            return back();
        }
        $order->payment_status = $request->payment_status;
        $order->save();
        Toastr::success(translate('Payment status updated!'));
        return back();
    }

    public function update_shipping(Request $request, $id)
    {
        $request->validate([
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required',
            'address' => 'required'
        ]);

        $address = [
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'floor' => $request->floor,
            'house' => $request->house,
            'road' => $request->road,
            'address' => $request->address,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'created_at' => now(),
            'updated_at' => now()
        ];

        DB::table('customer_addresses')->where('id', $id)->update($address);
        Toastr::success(translate('Address updated!'));
        return back();
    }

    public function generate_invoice($id)
    {
        $order = Order::where(['id' => $id, 'branch_id' => auth('branch')->id()])->first();
        return view('branch-views.order.invoice', compact('order'));
    }

    public function add_payment_ref_code(Request $request, $id)
    {
        Order::where(['id' => $id, 'branch_id' => auth('branch')->id()])->update([
            'transaction_reference' => $request['transaction_reference']
        ]);

        Toastr::success(translate('Payment reference code is added!'));
        return back();
    }

    public function export_excel()
    {
        $data = [];
        $orders = session('order_data_export');
        foreach ($orders as $key => $order) {
            $data[$key]['SL'] = ++$key;
            $data[$key]['Order ID'] = $order->id;
            $data[$key]['Order Date'] = date('d M Y h:m A',strtotime($order['created_at']));
            $data[$key]['Customer Info'] = $order['user_id'] == null? 'Walk in Customer' : ($order->customer == null? 'Customer Unavailable' : $order->customer['f_name']. ' '. $order->customer['l_name']);
            $data[$key]['Branch'] = $order->branch? $order->branch->name : 'Branch Deleted';
            $data[$key]['Total Amount'] = Helpers::set_symbol($order['order_amount']);
            $data[$key]['Payment Status'] = $order->payment_status=='paid'? 'Paid' : 'Unpaid';
            $data[$key]['Order Status'] = $order['order_status']=='pending'? 'Pending' : ($order['order_status']=='confirmed'? 'Confirmed' : ($order['order_status']=='processing' ? 'Processing' : ($order['order_status']=='delivered'? 'Delivered': ($order['order_status']=='picked_up'? 'Out For Delivery' : str_replace('_',' ',$order['order_status'])))));
        };
        return (new FastExcel($data))->download('orders.xlsx');
    }
    public function ajax_change_delivery_time_date(Request $request)
    {
        $order = Order::where('id', $request->order_id)->first();
        if(!$order) {
            return response()->json(['status' => false]);
        }
        $order->delivery_date = $request->input('delivery_date')?? $order->delivery_date;
        $order->delivery_time = $request->input('delivery_time')?? $order->delivery_time;
        $order->save();

        return response()->json(['status' => true]);

    }
}
