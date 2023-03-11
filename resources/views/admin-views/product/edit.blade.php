@extends('layouts.admin.app')

@section('title', translate('Update product'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{asset('public/assets/admin/css/tags-input.min.css')}}" rel="stylesheet">
@endpush

@section('content')
    {{-- Added by Me --}}
    <script>
        var global = {};
        global['has_sides'] =false;
        global['has_drinks'] =false;
        global['has_dips'] =false;
        global['has_items'] =false;
    </script>
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/product.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Product_Update')}}
                </span>
            </h2>
        </div>
        <!-- End Page Header -->


        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="javascript:" method="post" id="product_form" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-2">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    @php($data = Helpers::get_business_settings('language'))
                                    @php($default_lang = Helpers::get_default_language())

                                    @if($data && array_key_exists('code', $data[0]))
                                        <ul class="nav nav-tabs w-fit-content mb-4">
                                            @foreach($data as $lang)
                                                <li class="nav-item">
                                                    <a class="nav-link lang_link {{$lang['code'] == 'en'? 'active':''}}" href="#" id="{{$lang['code']}}-link">{{Helpers::get_language_name($lang['code']).'('.strtoupper($lang['code']).')'}}</a>
                                                </li>
                                            @endforeach

                                        </ul>
                                        @foreach($data as $lang)
                                            <?php
                                            if(count($product['translations'])){
                                                $translate = [];
                                                foreach($product['translations'] as $t)
                                                {
                                                    if($t->locale == $lang['code'] && $t->key=="name"){
                                                        $translate[$lang['code']]['name'] = $t->value;
                                                    }
                                                    if($t->locale == $lang['code'] && $t->key=="description"){
                                                        $translate[$lang['code']]['description'] = $t->value;
                                                    }

                                                }
                                            }
                                            ?>
                                            <div class="{{$lang['code'] != 'en'? 'd-none':''}} lang_form" id="{{$lang['code']}}-form">
                                                <div class="form-group">
                                                    <label class="input-label" for="{{$lang['code']}}_name">{{translate('name')}} ({{strtoupper($lang['code'])}})</label>
                                                    <input type="text" {{$lang['status'] == true ? 'required':''}} name="name[]" id="{{$lang['code']}}_name" value="{{$translate[$lang['code']]['name']??$product['name']}}" class="form-control" placeholder="{{translate('New Product')}}" >
                                                </div>
                                                <input type="hidden" name="lang[]" value="{{$lang['code']}}">
                                                <div class="form-group">
                                                    <label class="input-label"
                                                        for="{{$lang['code']}}_description">{{translate('short')}} {{translate('description')}}  ({{strtoupper($lang['code'])}})</label>
                                                    {{--<div id="{{$lang}}_editor">{!! $translate[$lang]['description']??$product['description'] !!}</div>--}}
                                                    <textarea name="description[]" class="form-control textarea-h-100" id="{{$lang['code']}}_hiddenArea">{{$translate[$lang['code']]['description']??$product['description']}}</textarea>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="card p-4" id="english-form">
                                            <div class="form-group">
                                                <label class="input-label">{{translate('name')}} (EN)</label>
                                                <input type="text" name="name[]" value="{{$product['name']}}" class="form-control" placeholder="{{translate('New Product')}}" required>
                                            </div>
                                            <input type="hidden" name="lang[]" value="en">
                                            <div class="form-group pt-4">
                                                <label class="input-label">{{translate('short')}} {{translate('description')}} (EN)</label>
                                                {{--<div id="editor">{!! $product['description'] !!}</div>--}}
                                                <textarea name="description[]" class="form-control textarea-h-100" id="hiddenArea">{{ $product['description'] }}</textarea>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label class="font-weight-bold">{{translate('product')}} {{translate('image')}}</label>
                                        <small class="text-danger">* ( {{translate('ratio')}} 1:1 )</small>
                                        <!-- <div class="custom-file">
                                            <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            <label class="custom-file-label" for="customFileEg1">{{translate('choose')}} {{translate('file')}}</label>
                                        </div>

                                        <center style="display: block" id="image-viewer-section" class="pt-2">
                                            <img style="height: 200px;border: 1px solid; border-radius: 10px;" id="viewer"
                                                src="{{asset('storage/app/public/product')}}/{{$product['image']}}"
                                                onerror="this.src='{{asset('public/assets/admin/img/400x400/img2.jpg')}}'"
                                                alt="product image"/>
                                        </center> -->


                                        <div class="d-flex justify-content-center mt-4">
                                            <div class="upload-file">
                                                <input type="file" name="image" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" class="upload-file__input">
                                                <div class="upload-file__img_drag upload-file__img">
                                                    <img width="176" src="{{asset('storage/app/public/product')}}/{{$product['image']}}"
                                                        onerror="this.src='{{asset('public/assets/admin/img/400x400/img2.jpg')}}'" alt="">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h4 class="mb-0 d-flex gap-2 align-items-center">
                                                <i class="tio-user"></i>
                                                {{translate('Category')}}
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label" for="exampleFormControlSelect1">{{translate('category')}}<span
                                                                class="input-label-secondary">*</span></label>
                                                        <select name="category_id" id="category-id" class="form-control js-select2-custom"
                                                                onchange="getRequest('{{url('/')}}/admin/product/get-categories?parent_id='+this.value,'sub-categories')">
                                                            @foreach($categories as $category)
                                                                <option
                                                                    value="{{$category['id']}}" {{ $category->id==$product_category[0]->id ? 'selected' : ''}} >{{$category['name']}}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label" for="exampleFormControlSelect1">{{translate('sub_Category')}}<span
                                                                class="input-label-secondary"></span></label>
                                                        <select name="sub_category_id" id="sub-categories"
                                                                data-id="{{count($product_category)>=2?$product_category[1]->id:''}}"
                                                                class="form-control js-select2-custom"
                                                                onchange="getRequest('{{url('/')}}/admin/product/get-categories?parent_id='+this.value,'sub-sub-categories')">
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label" for="exampleFormControlInput1">{{translate('item_Type')}}</label>
                                                        <select name="item_type" class="form-control js-select2-custom">
                                                            <option value="0" {{$product['set_menu']==0?'selected':''}}>{{translate('product')}} {{translate('item')}}</option>
                                                            <option value="1" {{$product['set_menu']==1?'selected':''}}>{{translate('set_menu')}}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label" for="product_type">
                                                            {{translate('product_Type')}}
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <select name="product_type" class="form-control js-select2-custom" required>
                                                            <option value="veg" {{$product['product_type']=='veg'?'selected':''}}>{{translate('veg')}}</option>
                                                            <option value="non_veg" {{$product['product_type']=='non_veg'?'selected':''}}>{{translate('nonveg')}}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h4 class="mb-0 d-flex gap-2 align-items-center">
                                                <i class="tio-user"></i>
                                                {{translate('Price_Information')}}
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label class="input-label">{{translate('default_Price')}}</label>
                                                        <input type="number" value="{{$product['price']}}" min="0" name="price"
                                                            class="form-control" step="0.01"
                                                            placeholder="{{translate('Ex : 100')}}" required>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label">{{translate('discount_Type')}}</label>
                                                        <select name="discount_type" class="form-control js-select2-custom">
                                                            <option value="percent" {{$product['discount_type']=='percent'?'selected':''}}>
                                                                {{translate('percent')}}
                                                            </option>
                                                            <option value="amount" {{$product['discount_type']=='amount'?'selected':''}}>
                                                                {{translate('amount')}}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label">{{translate('discount_(%)')}}</label>
                                                        <input type="number" min="0" value="{{$product['discount']}}"
                                                            name="discount" class="form-control" required
                                                            placeholder="Ex : 100">
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label">{{translate('tax_Type')}}</label>
                                                        <select name="tax_type" class="form-control js-select2-custom">
                                                            <option value="percent" {{$product['tax_type']=='percent'?'selected':''}}>{{translate('percentage')}}
                                                            </option>
                                                            <option value="amount" {{$product['tax_type']=='amount'?'selected':''}}>{{translate('amount')}}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label" for="exampleFormControlInput1">{{translate('tax_Rate($)')}}</label>
                                                        <input type="number" value="{{$product['tax']}}" min="0" name="tax"
                                                            class="form-control" step="0.01"
                                                            placeholder="{{translate('Ex : 7')}}" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between gap-3">
                                                <div class="text-dark">{{translate('turning visibility off will not show this product in the user app and website')}}</div>
                                                <div class="d-flex gap-3 align-items-center">
                                                    <h5>{{translate('Visibility')}}</h5>
                                                    <label class="switcher">
                                                        <input class="switcher_input" type="checkbox" name="status" {{$product->status == 1? 'checked' : ''}} >
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h4 class="mb-0 d-flex gap-2 align-items-center">
                                                <i class="tio-user"></i>
                                                {{translate('Availability')}}
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-2">
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label">{{translate('available_From')}}</label>
                                                        <input type="time" value="{{$product['available_time_starts']}}"
                                                            name="available_time_starts" class="form-control"
                                                            placeholder="{{translate('Ex : 10:30 am')}}" required>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label">{{translate('available_Till')}}</label>
                                                        <input type="time" value="{{$product['available_time_ends']}}"
                                                            name="available_time_ends" class="form-control" placeholder="{{translate('5:45 pm')}}"
                                                            required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- Added by Me  Meal--}}
                                <div class="col-12">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h4 class="mb-0 d-flex gap-2 align-items-center">
                                                <i class="tio-puzzle"></i>
                                                {{ translate('Meal Deal') }}
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <label class="input-label" for="has_meal_deal">
                                                Has Meal Deal?
                                            </label>
                                            <select name="has_meal_deal" class="form-control" id="has_meal_deal">
                                                <option value="1" {{ isset($product['meal_price']) ? "selected":"" }}>Yes</option>
                                                <option value="0" {{ isset($product['meal_price']) ? "":"selected" }}>No</option>
                                            </select>
                                            <div id="meal_deal" style={{ isset($product['meal_price']) ? "":"display:none" }}>
                                                <div class="form-group col-sm-5 mt-4">
                                                    <label id="meal_price_label" class="input-label"
                                                        for="meal_priceitem_input">{{ translate('Meal Deal Price') }}</label>
                                                    <input id="meal_price_input" type="number" min="0" step="any" name="meal_price"
                                                        class="form-control"
                                                        value={{ isset($product['meal_price']) ? $product['meal_price']:null }}
                                                        placeholder="{{ translate('Enter Price') }}">
                                                </div>
                                                <section class="sides">
                                                    <div class="card-header">
                                                        <h4 class="mb-0 d-flex gap-2 align-items-center">
                                                            {{ translate('Sides') }}
                                                        </h4>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="form-group col-sm-5">
                                                                <label id="side_label" class="input-label"
                                                                    for="side_input">{{ translate('Side') }}</label>
                                                                <input id="side_input" type="text" name="side" class="side-form-control form-control"
                                                                    placeholder="{{ translate('Side Name') }}">
                                                            </div>
                                                            <div class="form-group col-sm-5">
                                                                <label id="side_Price_label" class="side_Price-label"
                                                                    for="side_Price_input">{{ translate('Price') }}</label>
                                                                <input id="side_Price_input" type="number" min="0" step="any" name="side_Price"
                                                                    class="side-form-control form-control" placeholder="{{ translate('Price') }}">
                                                            </div>
                                                        </div>
                                                        <button data-side id="add_side_btn" type="button"
                                                            class="btn btn-primary add_meal_type_btn">{{ translate('Add') }}</button>
                                                    </div>
                                                    <div class="side_list" id="side_list">
                                                        @if($product['sides'] != null)
                                                            <table id="sides_table" class="table table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <td class="text-center">
                                                                            <label for="" class="control-label">{{translate('Name')}}</label>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <label for="" class="control-label">{{translate('Price')}}</label>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <label for="" class="control-label">{{translate('Action')}}</label>
                                                                        </td>
                                                                    </tr>
                                                                </thead>
                                                                 <tbody>
                                                                    @foreach (json_decode($product['sides'],true) as $key => $side)
                                                                        <tr>
                                                                            <td>{{$side['Name']}}</td>
                                                                            <td>{{$side['Price']}}</td>
                                                                            <td>
                                                                                <button type="button" data-side="{{ $key }}" class="btn btn-outline-danger side-delete">
                                                                                    <i class="tio-delete" data-side="{{ $key }}"  aria-hidden="true"></i>
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                            <script>
                                                                window.onload = (event) => {
                                                                    let sidearr = new Array();
                                                                    document.querySelectorAll("#sides_table tbody tr").forEach((el,ind)=>{
                                                                        let side = {};
                                                                        side['Name']=el.children[0].innerText;
                                                                        side['Price']=el.children[1].innerText;
                                                                        console.log(side);
                                                                        sidearr.push(side);
                                                                    })
                                                                    sessionStorage.setItem('side_list', JSON.stringify(itmarr));
                                                                };  
                                                            </script>
                                                        @endif
                                                    </div>
                                                </section>
                                                <section class="drinks">
                                                    <div class="card-header">
                                                        <h4 class="mb-0 d-flex gap-2 align-items-center">
                                                            {{ translate('Drinks') }}
                                                        </h4>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="form-group col-sm-5">
                                                                <label id="drink_label" class="input-label"
                                                                    for="drink_input">{{ translate('Drink') }}</label>
                                                                <input id="drink_input" type="text" name="drink" class="drink-form-control form-control"
                                                                    placeholder="{{ translate('Drink Name') }}">
                                                            </div>
                                                            <div class="form-group col-sm-5">
                                                                <label id="drink_Price_label" class="drink_Price-label"
                                                                    for="drink_Price_input">{{ translate('Price') }}</label>
                                                                <input id="drink_Price_input" type="number" min="0" step="any" name="drink_Price"
                                                                    class="drink-form-control form-control" placeholder="{{ translate('Price') }}">
                                                            </div>
                                                        </div>
                                                        <button data-drink id="add_drink_btn" type="button"
                                                            class="btn btn-primary add_meal_type_btn">{{ translate('Add') }}</button>
                                                    </div>
                                                    <div class="drink_list" id="drink_list"></div>

                                                </section>

                                                <section class="dips">
                                                    <div class="card-header">
                                                        <h4 class="mb-0 d-flex gap-2 align-items-center">
                                                            {{ translate('Dips') }}
                                                        </h4>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="form-group col-sm-5">
                                                                <label id="dip_label" class="input-label"
                                                                    for="dip_input">{{ translate('Dip') }}</label>
                                                                <input id="dip_input" type="text" name="dip" class="dip-form-control form-control"
                                                                    placeholder="{{ translate('Dip Name') }}">
                                                            </div>
                                                            <div class="form-group col-sm-5">
                                                                <label id="dip_Price_label" class="dip_Price-label"
                                                                    for="dip_Price_input">{{ translate('Price') }}</label>
                                                                <input id="dip_Price_input" type="number" min="0" step="any" name="dip_Price"
                                                                    class="dip-form-control form-control" placeholder="{{ translate('Price') }}">
                                                            </div>
                                                        </div>
                                                        <button data-dip id="add_dip_btn" type="button"
                                                            class="btn btn-primary add_meal_type_btn">{{ translate('Add') }}</button>
                                                    </div>
                                                    <div class="dip_list" id="dip_list"></div>
                                                </section>

                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-12">
                        <div class="card h-100">
                            <div class="card-header">
                                <h4 class="mb-0 d-flex gap-2 align-items-center">
                                    <i class="tio-user"></i>
                                    {{translate('Addons_&_Attributes')}}
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Select_Addons')}}</label>
                                    <select name="addon_ids[]" class="form-control" id="choose_addons" multiple="multiple">
                                        @foreach(\App\Model\AddOn::orderBy('name')->get() as $addon)
                                            <option
                                                value="{{$addon['id']}}" {{in_array($addon->id,json_decode($product['add_ons'],true))?'selected':''}}>{{$addon['name']}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="input-label">{{translate('Select_Attributes')}}</label>
                                    <select name="attribute_id[]" id="choice_attributes"
                                            class="form-control"
                                            multiple="multiple">
                                        @foreach(\App\Model\Attribute::orderBy('name')->get() as $attribute)
                                            <option
                                                value="{{$attribute['id']}}" {{in_array($attribute->id,json_decode($product['attributes'],true))?'selected':''}}>{{$attribute['name']}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="from_part_2">
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mt-2 mb-2">
                                        <div class="customer_choice_options" id="customer_choice_options">
                                            @include('admin-views.product.partials._choices',['choice_no'=>json_decode($product['attributes']),'choice_options'=>json_decode($product['choice_options'],true)])
                                        </div>
                                    </div>
                                    <div class="col-md-12 mt-2 mb-2">
                                        <div class="variant_combination" id="variant_combination">
                                            @include('admin-views.product.partials._edit-combinations',['combinations'=>json_decode($product['variations'],true)])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Added by Me (Sopan) --}}
                    <div class="col-12">
                        <div class="card h-100">
                            <div class="card-header">
                                <h4 class="mb-0 d-flex gap-2 align-items-center">
                                    <i class="tio-puzzle"></i>
                                    {{ translate('Product Structure') }}
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-sm-5">
                                        <label id="item_label" class="input-label"
                                            for="item_input">{{ translate('Item') }}</label>
                                        <input id="item_input" type="text" name="item"
                                            class="item-form-control form-control"
                                            placeholder="{{ translate('Item Name') }}">
                                    </div>
                                    <div class="form-group col-sm-5">
                                        <label id="item_defAmount_label" class="item_defAmount-label"
                                            for="item_defAmount_input">{{ translate('Default Amount') }}</label>
                                        <input id="item_defAmount_input" type="number" min="0" step="1" name="item_defAmount"
                                            class="item-form-control form-control"
                                            placeholder="{{ translate('Defaut Amount') }}">
                                    </div>
                                    <div class="form-group col-sm-5">
                                        <label id="item_maxAmount_label" class="item_maxAmount-label"
                                            for="item_maxAmount_input">{{ translate('Max Amount') }}</label>
                                        <input id="item_maxAmount_input" type="number" min="1" step="1" name="item_maxAmount"
                                            class="item-form-control form-control"
                                            placeholder="{{ translate('Max Amount') }}">
                                    </div>
                                    <div class="form-group col-sm-5">
                                        <label id="item_Price_label" class="item_Price-label"
                                            for="item_Price_input">{{ translate('Price') }}</label>
                                        <input id="item_Price_input" type="number" min="0" step="any" name="item_Price"
                                            class="item-form-control form-control"
                                            placeholder="{{ translate('Price') }}">
                                    </div>
                                    <div class="form-group col-sm-5">
                                        <label id="item_freeAmount_label" class="item_freeAmount-label"
                                            for="item_freeAmount_input">{{ translate('Free Upto') }}</label>
                                        <input id="item_freeAmount_input" value="1" min="0" step="1" type="number"
                                            name="item_freeAmount"  class="item-form-control form-control"
                                            placeholder="{{ translate('Enter Free Amount') }}">
                                    </div>
                                </div>
                                <button id="add_item_btn" type="button"
                                    class="btn btn-primary">{{ translate('Add') }}</button>
                                <div class="col-md-12">
                                    <div class="item_list" id="item_list">
                                        @if($product['structure'] != null)
                                            {{-- <script>
                                                sessionStorage.setItem('itm_list', JSON.stringify(itmarr))
                                            </script> --}}
                                            <table id="itm_table" class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <td class="text-center">
                                                            <label for="" class="control-label">Item</label>
                                                        </td>
                                                        <td class="text-center">
                                                            <label for="" class="control-label">Price</label>
                                                        </td>
                                                        <td class="text-center">
                                                            <label for="" class="control-label">Default Amount</label>
                                                        </td>
                                                        <td class="text-center">
                                                            <label for="" class="control-label">Max Amount</label>
                                                        </td>
                                                        <td class="text-center">
                                                            <label for="" class="control-label">Free Upto</label>
                                                        </td>
                                                        <td>
                                                            <label for="" class="control-label">Action</label>
                                                        </td>
                                                    </tr>
                                                </thead>
                                                {{-- @php(Illuminate\Support\Facades\Log::info($product['meal_price']))                 --}}
                                                <tbody>
                                                    @foreach (json_decode($product['structure'],true) as $key => $item)
                                                        <tr>
                                                            <td>{{$item['item']}}</td>
                                                            <td>{{$item['item_Price']}}</td>
                                                            <td>{{$item['item_defAmount']}}</td>
                                                            <td>{{$item['item_maxAmount']}}</td>
                                                            <td>{{$item['item_freeAmount']}}</td>
                                                            <td>
                                                                <button type="button" data-itm="{{ $key }}" class="btn btn-outline-danger itm-delete">
                                                                    <i class="tio-delete" data-itm="{{ $key }}"  aria-hidden="true"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            <script>
                                               global["has_items"] = true;
                                            </script>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group col-sm-5 mt-4">
                                    <label id="item_ttl_free_label" class="item_ttl_free-label"
                                        for="item_ttl_free_input">{{ translate('Total Free Amount') }}</label>
                                    <input id="item_ttl_free_input" type="number" value="0" min="0" step="1" name="item_ttl_free" class="form-control"
                                        placeholder="{{ translate('Enter Amount') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                  
                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                        <button type="submit" class="btn btn-primary">{{translate('update')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script')

@endpush

@push('script_2')
    <script src="{{asset('public/assets/admin/js/spartan-multi-image-picker.js')}}"></script>

    <script>
        //Select 2
        $("#choose_addons").select2({
            placeholder: "Select Addons",
            allowClear: true
        });
        $("#choice_attributes").select2({
            placeholder: "Select Attributes",
            allowClear: true
        });
    </script>

    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#viewer').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function () {
            readURL(this);
            $('#image-viewer-section').show(1000)
        });
    </script>

    <script>
        $(".lang_link").click(function(e){
            e.preventDefault();
            $(".lang_link").removeClass('active');
            $(".lang_form").addClass('d-none');
            $(this).addClass('active');

            let form_id = this.id;
            let lang = form_id.split("-")[0];
            console.log(lang);
            $("#"+lang+"-form").removeClass('d-none');
            if(lang == 'en')
            {
                $("#from_part_2").removeClass('d-none');
            }
            else
            {
                $("#from_part_2").addClass('d-none');
            }


        })
    </script>
    <script type="text/javascript">
        $(function () {
            $("#coba").spartanMultiImagePicker({
                fieldName: 'images[]',
                maxCount: 4,
                rowHeight: '215px',
                groupClassName: 'col-3',
                maxFileSize: '',
                placeholderImage: {
                    image: '{{asset('public/assets/admin/img/400x400/img2.jpg')}}',
                    width: '100%'
                },
                dropFileLabel: "Drop Here",
                onAddRow: function (index, file) {

                },
                onRenderedPreview: function (index) {

                },
                onRemoveRow: function (index) {

                },
                onExtensionErr: function (index, file) {
                    toastr.error('{{translate("Please only input png or jpg type file")}}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                onSizeErr: function (index, file) {
                    toastr.error('{{translate("File size too big")}}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        });
    </script>

    <script>
        function getRequest(route, id) {
            $.get({
                url: route,
                dataType: 'json',
                success: function (data) {
                    $('#' + id).empty().append(data.options);
                },
            });
        }

        $(document).ready(function () {
            setTimeout(function () {
                let category = $("#category-id").val();
                let sub_category = '{{count($product_category)>=2?$product_category[1]->id:''}}';
                let sub_sub_category = '{{count($product_category)>=3?$product_category[2]->id:''}}';
                getRequest('{{url('/')}}/admin/product/get-categories?parent_id=' + category + '&&sub_category=' + sub_category, 'sub-categories');
                getRequest('{{url('/')}}/admin/product/get-categories?parent_id=' + sub_category + '&&sub_category=' + sub_sub_category, 'sub-sub-categories');
            }, 1000)
            if(global['has_items'] == true){
                let itmarr = new Array();
                document.querySelectorAll("#itm_table tbody tr").forEach((el,ind)=>{
                    let itm = {};
                    itm['item']=el.children[0].innerText;
                    itm['item_Price']=el.children[1].innerText;
                    itm['item_defAmount']=el.children[2].innerText;
                    itm['item_maxAmount']=el.children[3].innerText;
                    itm['item_freeAmount']=el.children[4].innerText; 
                    //console.log(itm);
                    itmarr.push(itm);
                })
                sessionStorage.setItem('itm_list', JSON.stringify(itmarr));
            }
        });
    </script>

    <script>
        $(document).on('ready', function () {
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>

    <script src="{{asset('public/assets/admin')}}/js/tags-input.min.js"></script>

    <script>
        $('#choice_attributes').on('change', function () {
            $('#customer_choice_options').html(null);
            $.each($("#choice_attributes option:selected"), function () {
                add_more_customer_choice_option($(this).val(), $(this).text());
            });
        });

        function add_more_customer_choice_option(i, name) {
            let n = name.split(' ').join('');
            $('#customer_choice_options').append('<div class="row"><div class="col-md-3"><input type="hidden" name="choice_no[]" value="' + i + '"><input type="text" class="form-control" name="choice[]" value="' + n + '" placeholder="Choice Title" readonly></div><div class="col-lg-9"><input type="text" class="form-control" name="choice_options_' + i + '[]" placeholder="Enter choice values" data-role="tagsinput" onchange="combination_update()"></div></div>');
            $("input[data-role=tagsinput], select[multiple][data-role=tagsinput]").tagsinput();
        }

        function combination_update() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                type: "POST",
                url: '{{route('admin.product.variant-combination')}}',
                data: $('#product_form').serialize(),
                success: function (data) {
                    $('#variant_combination').html(data.view);
                    if (data.length > 1) {
                        $('#quantity').hide();
                    } else {
                        $('#quantity').show();
                    }
                }
            });
        }
    </script>

{{--    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>--}}

    <script>
{{--        @if($language)--}}
{{--        @foreach(json_decode($language) as $lang)--}}
{{--        var {{$lang}}_quill = new Quill('#{{$lang}}_editor', {--}}
{{--            theme: 'snow'--}}
{{--        });--}}
{{--        @endforeach--}}
{{--        @else--}}
{{--        var en_quill = new Quill('#editor', {--}}
{{--            theme: 'snow'--}}
{{--        });--}}
{{--        @endif--}}


        // Added by Me (Sopan)
        function RenderItemTable(itmarr){
            let temp=`<table id="itm_table" class="table table-bordered">
            <thead>
            <tr>
                <td class="text-center">
                    <label for="" class="control-label">{{translate('Item')}}</label>
                </td>
                <td class="text-center">
                    <label for="" class="control-label">{{translate('Price')}}</label>
                </td>
                <td class="text-center">
                    <label for="" class="control-label">{{translate('Default Amount')}}</label>
                </td>
                <td class="text-center">
                    <label for="" class="control-label">{{translate('Max Amount')}}</label>
                </td>
                <td class="text-center">
                    <label for="" class="control-label">{{translate('Free Upto')}}</label>
                </td>
                <td>
                    <label for="" class="control-label">Action</label>
                </td>
            </tr>
            </thead>
             <tbody>
             </tbody>
            </table>`
            $('#item_list').html(temp);
            itmarr.forEach((item,ind)=>{
                /*let trtemp=`
                <tr>
                    <td>${item["item"]}</td>
                    <td>${item["item_defAmount"]}</td>
                    <td>${item["item_Price"]}</td>
                    <td>${item["item_freeAmount"]}</td>
                    <td>${item["item_maxAmount"]}</td>
                </tr>
                `*/
                 let tditem = $("<td></td>").text(item["item"]);
                let tditmprice = $("<td></td>").text(item["item_Price"]);
                let tditem_defAmount = $("<td></td>").text(item["item_defAmount"]);
                let tditem_maxAmount = $("<td></td>").text(item["item_maxAmount"]);
                let tditem_freeAmount = $("<td></td>").text(item["item_freeAmount"]);
                let tddltbtn = `
                    <td>
                        <button type="button" data-itm="${ind}" class="btn btn-outline-danger itm-delete">
                            <i class="tio-delete" data-itm="${ind}" aria-hidden="true"></i>
                        </button>
                    </td>
                `
                let tr=$("<tr></tr>").append(tditem,tditmprice,tditem_defAmount,tditem_maxAmount,tditem_freeAmount,tddltbtn);
                $("#itm_table tbody").append(tr);
            })
            console.log(itmarr);
            $(".itm-delete").on("click",(el)=>{
                console.log(el.target.getAttribute("data-itm"));
                let itmarr = JSON.parse(sessionStorage.getItem('itm_list'));
                if(itmarr.length != 1){
                    itmarr.splice(el.target.getAttribute("data-itm") * 1, 1);
                    sessionStorage.setItem('itm_list', JSON.stringify(itmarr));
                    RenderItemTable(itmarr);
                }else{
                    sessionStorage.removeItem('itm_list');
                    $('#item_list').html(null);
                }
            });
        }
        $("#add_item_btn").on('click', function() {
            let idata = $(".item-form-control").serializeArray();
            let itm = new Object();

            for(let i = 0;i <idata.length;i++){
                let itmprop = idata[i];
                if(!itmprop.value) {
                    toastr.error(`${itmprop.name} is empty`, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                    return;
                }
                itm[itmprop.name] = itmprop.value;
            }
            /*idata.forEach((el) => {
                let itmprop = el.split("=");
                itm[itmprop[0]] = itmprop[1];
            })*/
            if(itm.item_defAmount * 1 > itm.item_maxAmount * 1){
                toastr.error(`Default amount cannot be higher than max amount`, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                return;
            }
            if(itm.item_defAmount % 1 != 0 || itm.item_maxAmount % 1 != 0 || itm.item_freeAmount % 1 != 0){
                toastr.error(`Amount cannot be Decimal`, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                return;
            }
            if(itm.item_freeAmount * 1 > itm.item_maxAmount * 1){
                toastr.error(`Free amount cannot be higher than max amount`, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                return;
            }
            let itmarr = new Array();
            if (sessionStorage.getItem('itm_list') == null) {
                itmarr.push(itm);
                sessionStorage.setItem('itm_list', JSON.stringify(itmarr))
            } else {
                itmarr = JSON.parse(sessionStorage.getItem('itm_list'));

                for (let i = 0; i < itmarr.length; i++) {
                    if (itmarr[i].item == itm.item) {
                        toastr.error("Item Already Exists", {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        return;
                    }
                }
                itmarr.push(itm);
                sessionStorage.setItem('itm_list', JSON.stringify(itmarr));
            }
            RenderItemTable(itmarr);

        });
        $(".itm-delete").on("click",(el)=>{
            console.log(el.target.getAttribute("data-itm"));
            let itmarr = JSON.parse(sessionStorage.getItem('itm_list'));
            if(itmarr.length != 1){
                itmarr.splice(el.target.getAttribute("data-itm") * 1, 1);
                sessionStorage.setItem('itm_list', JSON.stringify(itmarr));
                RenderItemTable(itmarr);
            }else{
                sessionStorage.removeItem('itm_list');
                $('#item_list').html(null);
            }
        });
        //Added by Me (Sopan)
        $("#has_meal_deal").on("change",function (el){
            if(el.target.value * 1 == 1){
                $("#meal_deal").show();
                let variantheader = 
                `<td class="text-center" id="variantheader">
                    <label for="" class="control-label">{{translate('Variant Meal Price')}}</label>
                </td>`;
                $("#variant-table thead tr").append(variantheader);
                $(".variant_meal_price").show();
                $(".variant_meal_price input").prop("required",true);
            }else{
                $("#meal_deal").hide();
                $("#variantheader").remove();
                $(".variant_meal_price input").prop("required",false);;
                $(".variant_meal_price").hide();
            };
        });

        $(".add_meal_type_btn").on('click', function(el) {
            let is_side = false;
            let type = "";
            if(el.target.hasAttribute("data-side")){
                is_side = true;
                type = "side";
            }else if(el.target.hasAttribute("data-dip")){
                type = "dip";
            }else{
                type = "drink";
            }
            let sdata = $(`.${type}-form-control`).serializeArray();
            let sde = new Object();

            if($("#has_meal_deal")[0].value * 1 != 1){
                toastr.error("Has Meal Deal is set to No", {
                            CloseButton: true,
                            ProgressBar: true
                        });
                return;
            }

            if(!sdata[0].value || !sdata[1].value){
                toastr.error("Name or price is empty", {
                            CloseButton: true,
                            ProgressBar: true
                        });
                return;
            }
            sde["Name"] = sdata[0].value; 
            sde["Price"] = sdata[1].value * 1; 

            let sidearr = new Array();
            if (sessionStorage.getItem(`${type}_list`) == null) {
                sidearr.push(sde);
                sessionStorage.setItem(`${type}_list`, JSON.stringify(sidearr))
            } else {
                sidearr = JSON.parse(sessionStorage.getItem(`${type}_list`));

                for (let i = 0; i < sidearr.length; i++) {
                    if (sidearr[i].Name == sde.Name) {
                        toastr.error(`${type} Already Exists`, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        return;
                    }
                }
                sidearr.push(sde);
                sessionStorage.setItem(`${type}_list`, JSON.stringify(sidearr));
            }
            let temp = `
            <table id="${type}_table" class="table table-bordered">
                <thead>
                <tr>
                    <td class="text-center">
                        <label for="" class="control-label">{{translate('Name')}}</label>
                    </td>
                    <td class="text-center">
                        <label for="" class="control-label">{{translate('Price')}}</label>
                    </td>
                </tr>
                </thead>
                <tbody>
                </tbody>
             </table>   
            `

            $(`#${type}_list`).html(temp);
            sidearr.forEach((el)=>{
                /*let trtemp=`
                <tr>
                    <td>${el["Name"]}</td>
                    <td>${el["Price"]}</td>
                </tr>
                `*/
                let tdn = $("<td></td>").text(el["Name"]);
                let tdp = $("<td></td>").text(el["Price"]);
                let tr=$("<tr></tr>").append(tdn,tdp);
                $(`#${type}_table tbody`).append(tr);
            })
            console.log(sidearr);

        });



        $('#product_form').on('submit', function () {

{{--            @if($language)--}}
{{--            @foreach(json_decode($language) as $lang)--}}
{{--            var {{$lang}}_myEditor = document.querySelector('#{{$lang}}_editor');--}}
{{--            $("#{{$lang}}_hiddenArea").val({{$lang}}_myEditor.children[0].innerHTML);--}}
{{--            @endforeach--}}
{{--            @else--}}
{{--            var myEditor = document.querySelector('#editor');--}}
{{--            $("#hiddenArea").val(myEditor.children[0].innerHTML);--}}
{{--            @endif--}}

            var formData = new FormData(this);

            formData.delete("item");
            formData.delete("item_defAmount");
            formData.delete("item_maxAmount");
            formData.delete("item_Price");
            formData.delete("item_freeAmount");
            // formData.delete("has_meal_deal");
            formData.delete("side");
            formData.delete("side_Price");
            formData.delete("drink");
            formData.delete("drink_Price");

            if(formData.get("has_meal_deal")*1 == 1){
                if(sessionStorage.getItem('side_list')) formData.append("sides", sessionStorage.getItem('side_list'));
                if(sessionStorage.getItem('drink_list')) formData.append("drinks", sessionStorage.getItem('drink_list'));
                if(sessionStorage.getItem('dip_list')) formData.append("dips", sessionStorage.getItem('dip_list'));
            }else{
                formData.delete("has_meal_deal");
            };
            
            if(sessionStorage.getItem('itm_list')){
                formData.append("structure", sessionStorage.getItem('itm_list'));
            }

            console.log(formData);

            
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.product.update',[$product['id']])}}',
                // data: $('#product_form').serialize(),
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    if (data.errors) {
                        for (var i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        toastr.success('{{translate("product updated successfully!")}}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function () {
                            location.href = '{{route('admin.product.list')}}';
                        }, 2000);
                    }
                }
            });
        });
    </script>
@endpush
