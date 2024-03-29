<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Admin;
use App\Model\Branch;
use App\Model\Category;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Model\Review;
use App\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function fcm($id)
    {
        $fcm_token = Admin::find(auth('admin')->id())->fcm_token;
        $data = [
            'title' => 'New auto generate message arrived from admin dashboard',
            'description' => $id,
            'order_id' => '',
            'image' => '',
            'type'=>'order_status',
        ];
        Helpers::send_push_notif_to_device($fcm_token, $data);

        return "Notification sent to admin";
    }

    public function dashboard()
    {
        $top_sell = OrderDetail::with(['product'])
            ->whereHas('order', function ($query) {
                $query->where('order_status', 'delivered');
            })
            ->select('product_id', DB::raw('SUM(quantity) as count'))
            ->groupBy('product_id')
            ->orderBy("count", 'desc')
            ->take(6)
            ->get();

        $most_rated_products = Review::with(['product'])
            ->select(['product_id',
                DB::raw('AVG(rating) as ratings_average'),
                DB::raw('COUNT(rating) as total'),
            ])
            ->groupBy('product_id')
            ->orderBy("total", 'desc')
            ->take(7)
            ->get();

        $top_customer = Order::with(['customer'])
            ->select('user_id', DB::raw('COUNT(user_id) as count'))
            ->groupBy('user_id')
            ->orderBy("count", 'desc')
            ->take(6)
            ->get();

        $data = self::order_stats_data();

        $data['customer'] = User::count();
        $data['product'] = Product::count();
        $data['order'] = Order::count();
        $data['category'] = Category::where('parent_id', 0)->count();
        $data['branch'] = Branch::count();

        $data['top_sell'] = $top_sell;
        $data['most_rated_products'] = $most_rated_products;
        $data['top_customer'] = $top_customer;

        $from = \Carbon\Carbon::now()->startOfYear()->format('Y-m-d');
        $to = Carbon::now()->endOfYear()->format('Y-m-d');

        $earning = [];
        $earning_data = Order::where([
            // 'order_status' => 'delivered',
            'order_status' => 'accepted',
        ])->select(
            DB::raw('IFNULL(sum(order_amount),0) as sums'),
            DB::raw('YEAR(created_at) year, MONTH(created_at) month')
        )
            ->whereBetween('created_at', [Carbon::parse(now())->startOfYear(), Carbon::parse(now())->endOfYear()])
            ->groupby('year', 'month')->get()->toArray();
        for ($inc = 1; $inc <= 12; $inc++) {
            $earning[$inc] = 0;
            foreach ($earning_data as $match) {
                if ($match['month'] == $inc) {
                    $earning[$inc] = Helpers::set_price($match['sums']);
                }
            }
        }

        $order_statistics_chart = [];
        $order_statistics_chart_data = Order::where(['order_status' => 'delivered'])
            ->select(
                DB::raw('(count(id)) as total'),
                DB::raw('YEAR(created_at) year, MONTH(created_at) month')
            )
//            ->whereBetween('created_at', [$from, $to])
            ->whereBetween('created_at', [Carbon::parse(now())->startOfYear(), Carbon::parse(now())->endOfYear()])
            ->groupby('year', 'month')->get()->toArray();

        for ($inc = 1; $inc <= 12; $inc++) {
            $order_statistics_chart[$inc] = 0;
            foreach ($order_statistics_chart_data as $match) {
                if ($match['month'] == $inc) {
                    $order_statistics_chart[$inc] = $match['total'];
                }
            }
        }

        $donut = [];
        $donut_data = Order::all();
        $donut['pending'] = $donut_data->where('order_status', 'pending')->count();
        // $donut['ongoing'] = $donut_data->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery'])->count();
        $donut['accepted'] = $donut_data->where('order_status', 'accepted')->count();
        $donut['canceled'] = $donut_data->where('order_status', 'canceled')->count();
        $donut['declined'] = $donut_data->where('order_status', 'declined')->count();
        // $donut['failed'] = $donut_data->where('order_status', 'failed')->count();

//        $data['donut'] = $order_statistics_donut;
        

        $data['recent_orders'] = Order::latest()
            ->take(5)
            ->get();

        return view('admin-views.dashboard', compact('data', 'earning','order_statistics_chart', 'donut'));
    }

    public function order_stats(Request $request)
    {

        session()->put('statistics_type', $request['statistics_type']);
        $data = self::order_stats_data();

        return response()->json([
            'view' => view('admin-views.partials._dashboard-order-stats', compact('data'))->render()
        ], 200);
    }

    public function order_stats_data() {
        $today = session()->has('statistics_type') && session('statistics_type') == 'today' ? 1 : 0;
        $this_month = session()->has('statistics_type') && session('statistics_type') == 'this_month' ? 1 : 0;

        $pending = Order::where(['order_status' => 'pending'])->notSchedule()
            ->when($today, function ($query) {
                return $query->whereDate('created_at', \Carbon\Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        // $confirmed = Order::where(['order_status' => 'confirmed'])
        //     ->when($today, function ($query) {
        //         return $query->whereDate('created_at', Carbon::today());
        //     })
        //     ->when($this_month, function ($query) {
        //         return $query->whereMonth('created_at', Carbon::now());
        //     })
        //     ->count();
        //Added by Me
        $accepted = Order::where(['order_status' => 'accepted'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();

        // $processing = Order::where(['order_status' => 'processing'])
        //     ->when($today, function ($query) {
        //         return $query->whereDate('created_at', Carbon::today());
        //     })
        //     ->when($this_month, function ($query) {
        //         return $query->whereMonth('created_at', Carbon::now());
        //     })
        //     ->count();

        $declined = Order::where(['order_status' => 'declined'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();

        // $out_for_delivery = Order::where(['order_status' => 'out_for_delivery'])
        //     ->when($today, function ($query) {
        //         return $query->whereDate('created_at', Carbon::today());
        //     })
        //     ->when($this_month, function ($query) {
        //         return $query->whereMonth('created_at', Carbon::now());
        //     })
        //     ->count();
        $canceled = Order::where(['order_status' => 'canceled'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        // $delivered = Order::where(['order_status' => 'delivered'])
        //     ->when($today, function ($query) {
        //         return $query->whereDate('created_at', Carbon::today());
        //     })
        //     ->when($this_month, function ($query) {
        //         return $query->whereMonth('created_at', Carbon::now());
        //     })
        //     ->count();
        $all = Order::when($today, function ($query) {
            return $query->whereDate('created_at', Carbon::today());
        })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        // $returned = Order::where(['order_status' => 'returned'])
        //     ->when($today, function ($query) {
        //         return $query->whereDate('created_at', Carbon::today());
        //     })
        //     ->when($this_month, function ($query) {
        //         return $query->whereMonth('created_at', Carbon::now());
        //     })
        //     ->count();
        // $failed = Order::where(['order_status' => 'failed'])
        //     ->when($today, function ($query) {
        //         return $query->whereDate('created_at', Carbon::today());
        //     })
        //     ->when($this_month, function ($query) {
        //         return $query->whereMonth('created_at', Carbon::now());
        //     })
        //     ->count();

        // $data = [
        //     'pending' => $pending,
        //     'confirmed' => $confirmed,
        //     'processing' => $processing,
        //     'out_for_delivery' => $out_for_delivery,
        //     'canceled' => $canceled,
        //     'delivered' => $delivered,
        //     'all' => $all,
        //     'returned' => $returned,
        //     'failed' => $failed
        // ];
        $data = [
            'pending' => $pending,
            'accepted' => $accepted,
            'declined' => $declined,
            'canceled' => $canceled,
            'all' => $all,
        ];

        return $data;
    }

    public function order_statistics(Request $request){
        $dateType = $request->type;

        $order_data = array();
        if($dateType == 'yearOrder') {
            $number = 12;
            $from = Carbon::now()->startOfYear()->format('Y-m-d');
            $to = Carbon::now()->endOfYear()->format('Y-m-d');

            $orders = Order::where(['order_status' => 'delivered'])
                ->select(
                    DB::raw('(count(id)) as total'),
                    DB::raw('YEAR(created_at) year, MONTH(created_at) month')
                )
//                ->whereBetween('created_at', [$from, $to])
                ->whereBetween('created_at', [Carbon::parse(now())->startOfYear(), Carbon::parse(now())->endOfYear()])
                ->groupby('year', 'month')->get()->toArray();

            for ($inc = 1; $inc <= $number; $inc++) {
                $order_data[$inc] = 0;
                foreach ($orders as $match) {
                    if ($match['month'] == $inc) {
                        $order_data[$inc] = $match['total'];
                    }
                }
            }
            $key_range = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");

        }
        elseif($dateType == 'MonthOrder') {
            $from = date('Y-m-01');
            $to = date('Y-m-t');
            $number = date('d',strtotime($to));
            $key_range = range(1, $number);

            $orders = Order::where(['order_status' => 'delivered'])
                ->select(
                    DB::raw('(count(id)) as total'),
                    DB::raw('YEAR(created_at) year, MONTH(created_at) month, DAY(created_at) day')
                )
//                ->whereBetween('created_at', [$from, $to])
                ->whereBetween('created_at', [Carbon::parse(now())->startOfYear(), Carbon::parse(now())->endOfYear()])
                ->groupby('created_at')
                    ->get()
                    ->toArray();

            for ($inc = 1; $inc <= $number; $inc++) {
                $order_data[$inc] = 0;
                foreach ($orders as $match) {
                    if ($match['day'] == $inc) {
                        $order_data[$inc] += $match['total'];
                    }
                }
            }

        }
        elseif($dateType == 'WeekOrder') {
            Carbon::setWeekStartsAt(Carbon::SUNDAY);
            Carbon::setWeekEndsAt(Carbon::SATURDAY);

            $from = Carbon::now()->startOfWeek();
            $to = Carbon::now()->endOfWeek();
            $orders = Order::where(['order_status' => 'delivered'])
                ->whereBetween('created_at', [$from, $to])->get();

            $date_range = CarbonPeriod::create($from, $to)->toArray();
            $key_range = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
            $order_data = [];
            foreach ($date_range as $date) {

                $order_data[] = $orders->whereBetween('created_at', [$date, Carbon::parse($date)->endOfDay()])->count();
            }
        }

        $label = $key_range;
        $order_data_final = $order_data;

        $data = array(
            'orders_label' => $label,
            'orders' => array_values($order_data_final),
        );
        return response()->json($data);
    }


    public function earning_statistics(Request $request){
        $dateType = $request->type;

        $earning_data = array();
        if($dateType == 'yearEarn') {
            $earning = [];
            $earning_data = Order::where([
                'order_status' => 'delivered',
            ])->select(
                DB::raw('IFNULL(sum(order_amount),0) as sums'),
                DB::raw('YEAR(created_at) year, MONTH(created_at) month')
            )
                ->whereBetween('created_at', [Carbon::parse(now())->startOfYear(), Carbon::parse(now())->endOfYear()])
                ->groupby('year', 'month')->get()->toArray();
            for ($inc = 1; $inc <= 12; $inc++) {
                $earning[$inc] = 0;
                foreach ($earning_data as $match) {
                    if ($match['month'] == $inc) {
                        $earning[$inc] = Helpers::set_price($match['sums']);
                    }
                }
            }
            $key_range = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
            $order_data = $earning;


        }
        elseif($dateType == 'MonthEarn') {
            $from = date('Y-m-01');
            $to = date('Y-m-t');
            $number = date('d',strtotime($to));
            $key_range = range(1, $number);

            $earning = Order::where(['order_status' => 'delivered'])
                ->select(DB::raw('IFNULL(sum(order_amount),0) as sums'), DB::raw('YEAR(created_at) year, MONTH(created_at) month, DAY(created_at) day'))
                ->whereBetween('created_at', [Carbon::parse(now())->startOfMonth(), Carbon::parse(now())->endOfMonth()])
                ->groupby('created_at')
                ->get()
                ->toArray();

            for ($inc = 1; $inc <= $number; $inc++) {
                $earning_data[$inc] = 0;
                foreach ($earning as $match) {
                    if ($match['day'] == $inc) {
                        $earning_data[$inc] += $match['sums'];
                    }
                }
            }

            $order_data = $earning_data;
        }
        elseif($dateType == 'WeekEarn') {

            Carbon::setWeekStartsAt(Carbon::SUNDAY);
            Carbon::setWeekEndsAt(Carbon::SATURDAY);

            $from = Carbon::now()->startOfWeek();
            $to = Carbon::now()->endOfWeek();
            $orders = Order::where(['order_status' => 'delivered'])
                ->whereBetween('created_at', [$from, $to])->get();

            $date_range = CarbonPeriod::create($from, $to)->toArray();
            $key_range = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
            $order_data = [];
            foreach ($date_range as $date) {

                $order_data[] = $orders->whereBetween('created_at', [$date, Carbon::parse($date)->endOfDay()])->sum('order_amount');
            }
        }

        $label = $key_range;
        $earning_data_final = $order_data;

        $data = array(
            'earning_label' => $label,
            'earning' => array_values($earning_data_final),
        );
        return response()->json($data);
    }


}
