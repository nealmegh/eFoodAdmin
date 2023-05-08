<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Model\Admin;
use App\Model\Order;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class SystemController extends Controller
{
    public function restaurant_data()
    {
        $order1 =DB::table('orders')->where(['checked' => 0]);
        $new_order = $order1->count();
        $ord = $order1->first();
        
        $order = $ord != null ?Order::where('id', $ord->id)->first():'';
        $addons=[];
        if($order!=''){
            foreach($order->details as $key => $item){
                $addids = json_decode($item['add_on_ids'],true);
                $adds = [];
                if(is_array($addids)){
                    foreach ($addids as $id) {
                        $addon = \App\Model\AddOn::find($id);
                        array_push($adds,$addon);
                    }
                }
                array_push($addons,$adds);
            }
        }
        return response()->json([
            'success' => 1,
            'data' => ['new_order' => $new_order,'order' => json_encode($order),'addons'=>json_encode($addons),'view' =>$order!= null || $order!='' ? view('layouts.admin.partials._recipt', compact('order'))->render():null]
        ]);
    }

    public function settings()
    {
        return view('branch-views.settings');
    }
}
