<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{csrf_token()}}">
    <!-- Title -->
    <title>@yield('title')</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="">
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&amp;display=swap" rel="stylesheet">
    <!-- CSS Implementing Plugins -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <!-- CSS Front Template -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/theme.minc619.css?v=1.0">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/style.css?v=1.0">
    @stack('css_or_js')

    <script
        src="{{asset('public/assets/admin')}}/vendor/hs-navbar-vertical-aside/hs-navbar-vertical-aside-mini-cache.js"></script>
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/toastr.css">
</head>

<body class="footer-offset">

{{--loader--}}
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div id="loading" style="display: none;">
                <div style="position: fixed;z-index: 9999; left: 40%;top: 37% ;width: 100%">
                    <img width="200" src="{{asset('public/assets/admin/img/loader.gif')}}">
                </div>
            </div>
        </div>
    </div>
</div>
{{--loader--}}

<!-- Builder -->
@include('layouts.admin.partials._front-settings')
<!-- End Builder -->

<!-- JS Preview mode only -->
@include('layouts.admin.partials._header')
@include('layouts.admin.partials._sidebar')
<!-- END ONLY DEV -->

<main id="content" role="main" class="main pointer-event">
    <!-- Content -->
    @yield('content')
    <!-- End Content -->

    <!-- Footer -->
    @include('layouts.admin.partials._footer')
    <!-- End Footer -->

    <div class="modal fade" id="popup-modal">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            {{-- <center> --}}
                                <h2>
                                    <i class="tio-shopping-cart-outlined"></i> {{ translate('You have new order, Check Please.') }}
                                </h2>
                                <button id='modal-check-order' class="btn btn-primary">
                                    Click To View Details
                                </button>
                                {{-- <table class="table table-bordered mt-3" id="modaldetails-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 10%">{{ translate('QTY') }}</th>
                                            <th class="">{{ translate('DESC') }}</th>
                                            <th style="text-align:right; padding-right:4px">{{ translate('Price') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table> --}}
                                <div id='noti-print'></div>
                                <hr>
                                <div class="row">
                                    <button id='modal-accept' type="button" class="btn btn-outline-success col m-1">Accept</button>
                                    <button id='modal-decline' type="button" class="btn btn-outline-danger col m-1">Decline</button>
                                </div>
                                {{-- <button onclick="check_order()" class="btn btn-primary">{{ translate('Ok, let me check') }}</button> --}}
                            {{-- </center> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<!-- ========== END MAIN CONTENT ========== -->

<!-- ========== END SECONDARY CONTENTS ========== -->
<script src="{{asset('public/assets/admin')}}/js/custom.js"></script>
<!-- JS Implementing Plugins -->

@stack('script')

<!-- JS Front -->
<script src="{{asset('public/assets/admin')}}/js/vendor.min.js"></script>
<script src="{{asset('public/assets/admin')}}/js/theme.min.js"></script>
<script src="{{asset('public/assets/admin')}}/js/sweet_alert.js"></script>
<script src="{{asset('public/assets/admin')}}/js/toastr.js"></script>
{!! Toastr::message() !!}

@if ($errors->any())
    <script>
        @foreach($errors->all() as $error)
        toastr.error('{{$error}}', Error, {
            CloseButton: true,
            ProgressBar: true
        });
        @endforeach
    </script>
@endif
<!-- JS Plugins Init. -->

<script>
    // INITIALIZATION OF NAVBAR VERTICAL NAVIGATION
    // =======================================================
    var sidebar = $('.js-navbar-vertical-aside').hsSideNav();

    $(document).on('ready', function () {

        // BUILDER TOGGLE INVOKER
        // =======================================================
        $('.js-navbar-vertical-aside-toggle-invoker').click(function () {
            $('.js-navbar-vertical-aside-toggle-invoker i').tooltip('hide');
        });
        // INITIALIZATION OF UNFOLD
        // =======================================================
        $('.js-hs-unfold-invoker').each(function () {
            var unfold = new HSUnfold($(this)).init();
        });






        // INITIALIZATION OF TOOLTIP IN NAVBAR VERTICAL MENU
        // =======================================================
        $('.js-nav-tooltip-link').tooltip({boundary: 'window'})

        $(".js-nav-tooltip-link").on("show.bs.tooltip", function (e) {
            if (!$("body").hasClass("navbar-vertical-aside-mini-mode")) {
                return false;
            }
        });


    });
</script>

@stack('script_2')
<audio id="myAudio">
    <source src="{{asset('public/assets/admin/sound/notification.mp3')}}" type="audio/mpeg">
</audio>

<script>
    var audio = document.getElementById("myAudio");

    function playAudio() {
        audio.play();
    }

    function pauseAudio() {
        audio.pause();
    }

    //File Upload
    $(window).on('load', function() {
        $(".upload-file__input").on("change", function () {
        if (this.files && this.files[0]) {
            let reader = new FileReader();
            let img = $(this).siblings(".upload-file__img").find('img');

            reader.onload = function (e) {
            img.attr("src", e.target.result);
            console.log($(this).parent());
            };

            reader.readAsDataURL(this.files[0]);
        }
        });
    })
</script>
<script>
    function printRecipt(divName,id) {
        var originalContents = document.body.innerHTML;
        $('h5').css('font-size',"16pt");
        $('table').css('font-size',"14pt");

        var printContents = document.getElementById(divName).innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.href=`${window.location.origin}/admin/orders/status?id=${id}&order_status=accepted`;
    }
    @if(Helpers::module_permission_check('order_management'))
        setInterval(function () {
            $.get({
                url: '{{route('admin.get-restaurant-data')}}',
                dataType: 'json',
                success: function (response) {
                    let data = response.data;
                    let order = JSON.parse(data.order);
                    console.log(order);
                    let details = order.details;
                    let addons = JSON.parse(data.addons);
                    if (data.new_order > 0) {
                        playAudio();
                        $("#modaldetails-table tbody tr").remove();   
                        /*let sub_total = 0;
                        let total_tax= 0;
                        let total_dis_on_pro = 0;
                        let add_ons_cost = 0; 
                        let extra_items_cost = 0;
                        let total_meal_items_cost = 0;
                        details.forEach((detail,ind)=>{
                            let product_details = JSON.parse(detail.product_details);
                            let structure = JSON.parse(product_details.structure); 
                            let items = detail.items !=null ? JSON.parse(detail.items): new Array();
                            let total_free= product_details.item_ttl_free;
                            let is_meal = JSON.parse(detail.is_meal);
                            let sides = JSON.parse(detail.sides);
                            let drinks = JSON.parse(detail.drinks);
                            let dips = JSON.parse(detail.dips);
                            let meal_items_price = 0;
                            let items_price = detail.items_price;
                            let add_on_qtys = JSON.parse(detail.add_on_qtys);

                            let variation = JSON.parse(detail.variation);

                            let addon = addons[ind];

                            
                            let tr = `
                                <tr>
                                    <td class="">
                                        ${detail.quantity}
                                    </td>
                                    <td class="">
                                        ${is_meal == 1 ? `${product_details.name}(Meal Deal)`:product_details.name}<br>
                                        
                                        ${variation != null && variation.length > 0 ?
                                            `
                                            ${variation.map((el)=>{
                                                return `
                                                    <strong><u>{{ translate('Variation : ${el.type}') }}</u></strong>
                                                    <div class="font-size-sm text-body" style="color: black!important;">
                                                        <span>price : </span>
                                                        <span
                                                            class="font-weight-bold">${ is_meal == 1 ? el.var_meal_price : el.price }</span>
                                                    </div>
                                                `
                                            }).join("")}`:``
                                        }
                                        ${addon.length > 0 ?
                                            `<strong><u>{{ translate('Addons : ') }}</u></strong>
                                            ${addon.map((el,ind)=>{
                                                let add_on_qty = 0;
                                                console.log(addon);
                                                add_on_qtys != null ? add_on_qty = 1:add_on_qty=add_on_qtys[ind];
                                                add_ons_cost += el['price'] * add_on_qty;
                                                return `
                                                    <div class="font-size-sm text-body">
                                                        <span>${ el['name'] } : </span>
                                                        <span class="font-weight-bold">
                                                            ${ add_on_qty } x
                                                            ${ el['price']}
                                                        </span>
                                                    </div>
                                                ` 
                                                }).join("")
                                            }`:``
                                        }
                                        ${items.length > 0 ? 
                                        `
                                            ${total_free>0? `<strong><u>Items(Free: ${ total_free }) : </u></strong>`:`<strong><u>{{ translate('Items : ') }}</u></strong>`}
                                            ${items.map((item,itind)=>{
                                                return `
                                                    <div class="font-size-sm text-body">
                                                        <span>${ item['name'] }(Free:
                                                            ${ structure[itind]['item_freeAmount'] }) : </span>
                                                        <span class="font-weight-bold">
                                                            ${ item['quantity'] }
                                                        </span>
                                                    </div>
                                                `
                                                }).join("")
                                            }
                                            <p>Items Price: ${items_price}</p>
                                            ${(() => {
                                                extra_items_cost += (items_price * detail['quantity'])
                                                return ``;  
                                            })()}
                                        `:``}
                                        ${is_meal == 1? `
                                            <u><strong>Meal Deal</strong></u>
                                            ${sides != null ? `
                                                ${(() => {
                                                  meal_items_price += sides['Price']*1;
                                                    return ``;  
                                                })()}
                                                <div class="font-size-sm text-body">
                                                    <span>Side : </span>
                                                    <span class="font-weight-bold">
                                                        ${ sides['Name'] }
                                                    </span>
                                                </div>
                                            `:``}
                                            ${drinks != null ? `
                                                ${(() => {
                                                  meal_items_price += drinks['Price']*1;
                                                    return ``;  
                                                })()}

                                                <div class="font-size-sm text-body">
                                                    <span>Drink : </span>
                                                    <span class="font-weight-bold">
                                                        ${ drinks['Name'] }
                                                    </span>
                                                </div>
                                            `:``}
                                            ${dips != null ? `
                                                ${(() => {
                                                  meal_items_price += dips['Price']*1;
                                                    return ``;  
                                                })()}
                                                <div class="font-size-sm text-body">
                                                    <span>Dip : </span>
                                                    <span class="font-weight-bold">
                                                        ${ dips['Name'] }
                                                    </span>
                                                </div>
                                            `:``}
                                            
                                            ${(() => {
                                                total_meal_items_cost += meal_items_price * detail['quantity']*1
                                                return ``;  
                                            })()}
                                            <p>Meal Items Price: ${meal_items_price}</p>
                                        `:``}
                                        Discount : ${detail['discount_on_product']}
                                    </td>

                                    <td style="width: 28%;padding-right:4px; text-align:right">
                                        ${ (detail['price']*1 - (detail['discount_on_product']*1)) * (detail['quantity']*1) }
                                    </td>
                                </tr>
                            `  
                            sub_total += (detail['price']*1 - (detail['discount_on_product']*1)) * (detail['quantity']*1)
                            total_tax += (detail['tax_amount']*1) * (detail['quantity']*1)
                            $("#modaldetails-table tbody").append(tr);

                        })*/
                        $("#noti-print").html(data.view);
                        $('#popup-modal').appendTo("body").modal('show');
                        
                        $("#modal-check-order").off("click");
                        $("#modal-accept").off("click");
                        $("#modal-decline").off("click");


                        $('#modal-check-order').click(()=>{
                            window.open(
                            `${window.location.origin}/admin/orders/details/${order.id}`, "_blank");
                        });
                        $('#modal-accept').click(()=>{
                            //location.href=`${window.location.origin}/admin/orders/status?id=${order.id}&order_status=accepted`
                            printRecipt('printableArea',order.id);
                        });
                        $('#modal-decline').click(()=>{
                            location.href=`${window.location.origin}/admin/orders/status?id=${order.id}&order_status=declined`
                        });
                    }
                },
            });
        }, 10000);
    @endif

    function check_order() {
        location.href = '{{route('admin.orders.list',['status'=>'all'])}}';
    }

    function route_alert(route, message) {
        Swal.fire({
            title: '{{translate("Are you sure?")}}',
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '#FC6A57',
            cancelButtonText: '{{translate("No")}}',
            confirmButtonText:'{{translate("Yes")}}',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                location.href = route;
            }
        })
    }

    function form_alert(id, message) {
        Swal.fire({
            title: '{{translate("Are you sure?")}}',
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '#FC6A57',
            cancelButtonText: '{{translate("No")}}',
            confirmButtonText: '{{translate("Yes")}}',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                $('#'+id).submit()
            }
        })
    }
</script>

<script>
    function call_demo(){
        toastr.info('Update option is disabled for demo!', {
            CloseButton: true,
            ProgressBar: true
        });
    }
</script>

{{-- Internet Status Check --}}
<script>
    //Internet Status Check
    window.addEventListener('online', function() {
        toastr.success('{{translate('Became online')}}');
    });
    window.addEventListener('offline', function() {
        toastr.error('{{translate('Became offline')}}');
    });

    //Internet Status Check (after any event)
    document.body.addEventListener("click", function(event) {
        if(window.navigator.onLine === false) {
            toastr.error('{{translate('You are in offline')}}');
            event.preventDefault();
        }
    }, false);
</script>

<!-- IE Support -->
<script>
    if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write('<script src="{{asset('public/assets/admin')}}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
</script>
<script>
    function status_change(t) {
        let url = $(t).data('url');
        let checked = $(t).prop("checked");
        let status = checked === true ? 1 : 0;

        Swal.fire({
            title: 'Are you sure?',
            text: 'Want to change status',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#FC6A57',
            cancelButtonColor: 'default',
            cancelButtonText: '{{translate("No")}}',
            confirmButtonText: '{{translate("Yes")}}',
            reverseButtons: true
        }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                        }
                    });
                    $.ajax({
                        url: url,
                        data: {
                            status: status
                        },
                        success: function (data, status) {
                            toastr.success("{{translate('Status changed successfully')}}");
                        },
                        error: function (data) {
                            toastr.error("{{translate('Status changed failed')}}");
                        }
                    });
                }
                else if (result.dismiss) {
                    if (status == 1) {
                        $('#' + t.id).prop('checked', false)

                    } else if (status == 0) {
                        $('#'+ t.id).prop('checked', true)
                    }
                    toastr.info("{{translate("Status hasn't changed")}}");
                }
            }
        )
    }

</script>

<script>
    let initialImages = [];
    $(window).on('load', function() {
        $("form").find('img').each(function (index, value) {
            initialImages.push(value.src);
        })
    })

    $(document).ready(function() {
        $('form').on('reset', function(e) {
            $("form").find('img').each(function (index, value) {
                $(value).attr('src', initialImages[index]);
            })
            $('.js-select2-custom').val(null).trigger('change');

        });
    });
</script>

</body>
</html>
