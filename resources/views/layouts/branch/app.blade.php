<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
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

    <!-- <style>
        .scroll-bar {
            max-height: calc(100vh - 100px);
            overflow-y: auto !important;
        }

        ::-webkit-scrollbar-track {
            box-shadow: inset 0 0 1px #cfcfcf;
            /*border-radius: 5px;*/
        }

        ::-webkit-scrollbar {
            width: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            /*border-radius: 5px;*/
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #FC6A57;
        }
    </style> -->

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
@include('layouts.branch.partials._front-settings')
<!-- End Builder -->

<!-- JS Preview mode only -->
@include('layouts.branch.partials._header')
@include('layouts.branch.partials._sidebar')
<!-- END ONLY DEV -->

<main id="content" role="main" class="main pointer-event">
    <!-- Content -->
@yield('content')
<!-- End Content -->

    <!-- Footer -->
@include('layouts.branch.partials._footer')
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


        // INITIALIZATION OF NAVBAR VERTICAL NAVIGATION
        // =======================================================
        var sidebar = $('.js-navbar-vertical-aside').hsSideNav();

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
</script>
<script>
    var modalshown = false;
    
    function printRecipt(divName,id) {
        var originalContents = document.body.innerHTML;
        $('h5').css('font-size',"16pt");
        $('table').css('font-size',"14pt");
        $('#printaddr').css('font-size',"13pt");

        var printContents = document.getElementById(divName).innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        //document.body.innerHTML = originalContents;
        location.href=`${window.location.origin}/branch/orders/status?id=${id}&order_status=accepted`;
    }
    setInterval(function () {
            $.get({
                url: '{{route('branch.get-restaurant-data')}}',
                dataType: 'json',
                success: function (response) {
                    let data = response.data;
                    let order = JSON.parse(data.order);
                    console.log(order);
                    let details = order.details;
                    let addons = JSON.parse(data.addons);
                    if (data.new_order > 0) {
                        playAudio();
                        $("#noti-print").html(data.view);
                        if(!modalshown){
                            modalshown = true;
                            $('#popup-modal').appendTo("body").modal('show');
                        }
                         $( '#popup-modal' ).on( 'hidden.bs.modal', function () {
                            modalshown = false;
                            $( '#popup-modal' ).off( 'hidden.bs.modal');
                        } );
                        $("#modal-check-order").off("click");
                        $("#modal-accept").off("click");
                        $("#modal-decline").off("click");


                        $('#modal-check-order').click(()=>{
                            window.open(
                            `${window.location.origin}/branch/orders/details/${order.id}`, "_blank");
                        });
                        $('#modal-accept').click(()=>{
                            //location.href=`${window.location.origin}/branch/orders/status?id=${order.id}&order_status=accepted`
                            printRecipt('printableArea',order.id);
                        });
                        $('#modal-decline').click(()=>{
                            location.href=`${window.location.origin}/branch/orders/status?id=${order.id}&order_status=declined`
                        });
                    }
                },
            });
        }, 10000);

    function check_order() {
        location.href = '{{route('branch.order.list',['status'=>'all'])}}';
    }

    function route_alert(route, message) {
        Swal.fire({
            title: '{{ translate('Are you sure?') }}',
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '#FC6A57',
            cancelButtonText: '{{ translate('No') }}',
            confirmButtonText: '{{ translate('Yes') }}',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                location.href = route;
            }
        })
    }

    function form_alert(id, message) {
        Swal.fire({
            title: '{{ translate('Are you sure?') }}',
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '#FC6A57',
            cancelButtonText: '{{ translate('No') }}',
            confirmButtonText: '{{ translate('Yes') }}',
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
        toastr.info('{{ translate('Update option is disabled for demo!') }}', {
            CloseButton: true,
            ProgressBar: true
        });
    }
</script>

<!-- IE Support -->
<script>
    if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write('<script src="{{asset('public/assets/admin')}}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
</script>
</body>
</html>
