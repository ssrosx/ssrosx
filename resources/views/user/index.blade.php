@extends('user.layouts')

@section('css')
    <link href="/assets/global/plugins/fancybox/source/jquery.fancybox.css" rel="stylesheet" type="text/css" />
    <style type="text/css">
        .fancybox > img {
            width: 75px;
            height: 75px;
        }
        .ticker {
            background-color: #fff;
            margin-bottom: 20px;
            border: 1px solid #e7ecf1!important;
            border-radius: 4px;
            -webkit-border-radius: 4px;
        }
        .ticker ul {
            padding: 0;
        }
        .ticker li {
            list-style: none;
            padding: 15px;
        }
    </style>
@endsection
@section('title', trans('home.panel'))
@section('content')
    <!-- BEGIN CONTENT BODY -->
    <div class="page-content" style="padding-top:0;">
        <!-- BEGIN PAGE BASE CONTENT -->
        @if (Session::has('successMsg'))
            <div class="alert alert-success">
                <button class="close" data-close="alert"></button>
                {{trans(Session::get('successMsg'))}}
            </div>
        @endif
        <div class="row">
            <div class="col-md-8">
                @if($notice)
                    <div class="row">
                        <div class="col-md-12">
                            <div class="portlet light bordered">
                                <div class="portlet-title tabbable-line">
                                    <div class="caption">
                                        <i class="icon-directions font-green hide"></i>
                                        <span class="caption-subject font-blue bold"> {{trans('home.announcement')}} </span>
                                    </div>
                                    <div class="actions">
                                        <div class="btn-group btn-group-devided" data-toggle="buttons">
                                            <button type="button" class="btn btn-default btn-circle" data-toggle="collapse" data-target="#open">展开</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="portlet-body">
                                    <div class="tab-content">
                                        <div id="open" class="collapse">
                                             {!!$notice->content!!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="row">
                    <div class="col-md-12">
                        <div class="portlet light">
                            <div class="portlet-title">
                                <div class="caption">
                                    <span class="caption-subject font-blue bold">{{trans('home.download_platform_app')}}</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div class="mt-clipboard-container" style="padding-top:0px;">
                                    <div class="tabbable-line">
                                        <ul class="nav nav-tabs ">
                                            <li class="active">
                                                <a href="#tools1" data-toggle="tab"> <i class="fa fa-apple"></i> Mac </a>
                                            </li>
                                            <li>
                                                <a href="#tools4" data-toggle="tab"> <i class="fa fa-apple"></i> iOS </a>
                                            </li>
                                            <li>
                                                <a href="#tools2" data-toggle="tab"> <i class="fa fa-windows"></i> Windows </a>
                                            </li>
                                            {{--<li>--}}
                                                {{--<a href="#tools3" data-toggle="tab"> <i class="fa fa-linux"></i> Linux </a>--}}
                                            {{--</li>--}}
                                            <li>
                                                <a href="#tools5" data-toggle="tab"> <i class="fa fa-android"></i> Android </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content" style="font-size:16px;">
                                            <div class="tab-pane active" id="tools1">
                                                <ol>
                                                    @if($is_open_shop)
                                                    {{--{{asset('clients/Patatas.dmg')}}--}}
                                                        <li> Mac: 7.9 MB <a href="https://raw.githubusercontent.com/ssrosx/ssrosx/master/public/clients/Patatas.dmg" target="_blank">{{trans('home.click_download')}}</a>{{trans('home.download_client_and_startup')}} </li>
                                                    @else
                                                    <li> Mac coming soon. </li>
                                                    @endif
                                                    {{--<li> 单击状态栏小飞机，找到服务器->编辑订阅，复制黏贴订阅地址 </li>--}}
                                                    {{--<li> 点击服务器->手动更新订阅，更新您的服务信息 </li>--}}
                                                    {{--<li> 更新成功后，请在服务器菜单处选择线路，并点击打开ShadowsocksR </li>--}}
                                                    {{--<li> 单击小飞机，选择PAC自动模式 </li>--}}
                                                </ol>
                                            </div>
                                            <div class="tab-pane" id="tools2">
                                                <ol>
                                                    <li> Windows coming soon. </li>
                                                    {{--<li> <a href="{{asset('clients/Shadowsocks-4.1.2.zip')}}" target="_blank">点击此处</a>下载客户端并启动 </li>--}}
                                                    {{--<li> 单击状态栏小飞机，找到服务器->订阅->订阅设置，复制黏贴订阅地址 </li>--}}
                                                    {{--<li> 点击状态栏小飞机，找到模式，选中PAC </li>--}}
                                                    {{--<li> 点击状态栏小飞机，找到PAC，选中更新PAC为GFWList </li>--}}
                                                    {{--<li> Windows: 18.54 MB <a href="https://raw.githubusercontent.com/ssrosx/ssrosx/master/public/clients/Patatas.exe" target="_blank">{{trans('home.click_download')}}</a>{{trans('home.download_client_and_startup')}} </li>--}}
                                                </ol>
                                            </div>
                                            {{--<div class="tab-pane" id="tools3">--}}
                                                {{--<ol>--}}
                                                    {{--<li> <a href="{{asset('clients/Shadowsocks-qt5-3.0.1.zip')}}" target="_blank">点击此处</a>下载客户端并启动 </li>--}}
                                                    {{--<li> 单击状态栏小飞机，找到服务器->编辑订阅，复制黏贴订阅地址 </li>--}}
                                                    {{--<li> 更新订阅设置即可 </li>--}}
                                                {{--</ol>--}}
                                            {{--</div>--}}
                                            <div class="tab-pane" id="tools4">
                                                <ol>
                                                    {{--@if(Agent::is('iPhone') || Agent::is('iPad'))--}}
                                                        {{--<li> <a href="{{$ipa_list}}" target="_blank">点击此处在线安装</a></li>--}}
                                                    {{--@endif--}}
                                                    {{--<li> iOS coming soon. </li>--}}
                                                    <li> iOS: 40.8 MB <a href="https://itunes.apple.com/us/app/patatas/id1433743886?l=zh&ls=1&mt=8" target="_blank">{{trans('home.click_download')}}</a>{{trans('home.download_client_and_startup')}} </li>
                                                </ol>
                                            </div>
                                            <div class="tab-pane" id="tools5">
                                                <ol>
                                                    <li> Android coming soon. </li>
                                                    {{--<li> Android <a href="{{asset('clients/Shadowsocks-universal-4.6.1.apk')}}" target="_blank">{{trans('home.click_download')}}</a>{{trans('home.download_client_and_startup')}} </li>--}}
                                                    {{--<li> 单击左上角的shadowsocksR进入配置文件页，点击右下角的“+”号，点击“添加/升级SSR订阅”，填入订阅信息并保存 </li>--}}
                                                    {{--<li> 选中任意一个节点，返回软件首页 </li>--}}
                                                    {{--<li> 在软件首页处找到“路由”选项，并将其改为“绕过局域网及中国大陆地址” </li>--}}
                                                    {{--<li> 点击右上角的小飞机图标进行连接，提示是否添加（或创建）VPN连接，点同意（或允许） </li>--}}
                                                    {{--<li> Android: 6.54 MB <a href="https://raw.githubusercontent.com/ssrosx/ssrosx/master/public/clients/Patatas.apk" target="_blank">{{trans('home.click_download')}}</a>{{trans('home.download_client_and_startup')}} </li>--}}
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <ul class="list-group">
                    @if($info['status'] != -1)
                    <li class="list-group-item">
                            {{trans('home.account_status')}}：{{trans('home.enabled')}}
                        </li>
                    @else
                        <li class="list-group-item list-group-item-danger">
                            {{trans('home.account_status')}}：{{trans('home.disabled')}}
                    </li>
                @endif
                    @if($login_add_score)
                        <li class="list-group-item">
                            {{trans('home.account_score')}}：{{$info['score']}}
                            <span class="badge badge-info">
                            <a href="javascript:;" data-toggle="modal" data-target="#exchange_modal" style="color:#FFF;">{{trans('home.redeem_coupon')}}</a>
                        </span>
                        </li>
                    @endif
                    @if($is_open_shop)
                    <li class="list-group-item">
                        {{trans('home.account_balance')}}：{{$info['balance']}}
                        <span class="badge badge-danger">
                            <a href="javascript:;" data-toggle="modal" data-target="#charge_modal" style="color:#FFF;">{{trans('home.recharge')}}</a>
                        </span>
                    </li>
                    @endif
                    @if(date('Y-m-d') > $info['expire_time'])
                        <li class="list-group-item list-group-item-danger">
                            {{trans('home.account_expire')}}：{{trans('home.expired')}}
                        </li>
                    @else
                        <li class="list-group-item">
                            {{trans('home.account_expire')}}：{{$info['expire_time']}}
                    </li>
                    @endif
                    <li class="list-group-item">
                        {{trans('home.account_last_usage')}}：{{empty($info['t']) ? trans('home.never_used') : date('Y-m-d H:i:s', $info['t'])}}
                    </li>
                    <li class="list-group-item">
                        {{trans('home.account_last_login')}}：{{empty($info['last_login']) ? trans('home.never_loggedin') : date('Y-m-d H:i:s', $info['last_login'])}}
                    </li>
                    <li class="list-group-item">
                        {{trans('home.account_bandwidth_unused')}}：{{$info['unusedTransfer']}} @if($info['traffic_reset_day']) &ensp;{{trans('home.account_reset_notice', ['reset_day' => $info['traffic_reset_day']])}}  @endif
                    </li>
                </ul>
            </div>
            <div class="col-md-8">
                <div class="row widget-row">
                    @if(!$nodeList->isEmpty())
                        @foreach($nodeList as $node)
                            <div class="col-md-6">
                                <div class="widget-thumb widget-bg-color-white text-uppercase margin-bottom-20 ">
                                    <div class="widget-thumb-wrap">
                                        <div style="float:left;display: inline-block;padding-right:10px;">
                                            @if($node->country_code)
                                                <img src="{{asset('assets/images/country/' . $node->country_code . '.png')}}"/>
                                            @else
                                                <img src="{{asset('/assets/images/country/un.png')}}"/>
                                            @endif
                                        </div>
                                        <div class="widget-thumb-body">
                                            <span class="widget-thumb-subtitle"><a data-toggle="modal">{{$node->name}}</a></span>
                                            <span class="widget-thumb-body-stat">
                                                @if($node->online_status)
                                                    <a class="btn btn-sm green">{{trans('home.node_normal')}}</a>
                                                @else
                                                    <a class="btn btn-sm red">{{trans('home.node_crash')}}</a>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <div id="charge_modal" class="modal fade" tabindex="-1" data-focus-on="input:first" data-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                        <h4 class="modal-title">{{trans('home.recharge_balance')}}</h4>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger" style="display: none; text-align: center;" id="charge_msg"></div>
                        <form action="#" method="post" class="form-horizontal">
                            <div class="form-body">
                                <div class="form-group">
                                    <label for="charge_type" class="col-md-4 control-label">{{trans('home.payment_method')}}</label>
                                    <div class="col-md-6">
                                        <select class="form-control" name="charge_type" id="charge_type">
                                            <option value="1" selected>{{trans('home.coupon_code')}}</option>
                                            @if(!$goodsList->isEmpty())
                                                <option value="2">{{trans('home.online_pay')}}</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                @if(!$goodsList->isEmpty())
                                    <div class="form-group" id="charge_balance" style="display: none;">
                                        <label for="online_pay" class="col-md-4 control-label">{{trans('home.recharge_amount')}}</label>
                                        <div class="col-md-6">
                                            <select class="form-control" name="online_pay" id="online_pay">
                                                @foreach($goodsList as $key => $goods)
                                                    <option value="{{$goods->id}}">{{trans('home.recharge_prev')}}{{$goods->price}}{{trans('home.amount_ext')}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endif
                                <div class="form-group" id="charge_coupon_code">
                                    <label for="charge_coupon" class="col-md-4 control-label"> {{trans('home.coupon_code')}} </label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="charge_coupon" id="charge_coupon" placeholder="{{trans('home.please_input_coupon')}}">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-dismiss="modal" class="btn dark btn-outline">{{trans('home.close')}}</button>
                        <button type="button" class="btn red btn-outline" onclick="return charge();">{{trans('home.recharge')}}</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="exchange_modal" class="modal fade" tabindex="-1" data-focus-on="input:first" data-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                        <h4 class="modal-title"> {{trans('home.redeem_score')}} </h4>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info" id="msg">{{trans('home.redeem_info', ['score' => $info['score']])}}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-dismiss="modal" class="btn dark btn-outline">{{trans('home.close')}}</button>
                        <button type="button" class="btn red btn-outline" onclick="return exchange();">{{trans('home.redeem')}}</button>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($nodeList as $node)
            <div class="modal fade draggable-modal" id="txt_{{$node->id}}" tabindex="-1" role="basic" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                            <h4 class="modal-title">{{trans('home.setting_info')}}</h4>
                        </div>
                        <div class="modal-body">
                            <textarea class="form-control" rows="10" readonly="readonly">{{$node->txt}}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade draggable-modal" id="link_{{$node->id}}" tabindex="-1" role="basic" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                            <h4 class="modal-title">{{$node->name}}</h4>
                        </div>
                        <div class="modal-body">
                            <textarea class="form-control" rows="5" readonly="readonly">{{$node->ssr_scheme}}</textarea>
                            <a href="{{$node->ssr_scheme}}" class="btn purple uppercase" style="display: block; width: 100%;margin-top: 10px;">打开SSR</a>
                            @if($node->ss_scheme)
                            <p></p>
                            <textarea class="form-control" rows="3" readonly="readonly">{{$node->ss_scheme}}</textarea>
                            <a href="{{$node->ss_scheme}}" class="btn blue uppercase" style="display: block; width: 100%;margin-top: 10px;">打开SS</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="qrcode_{{$node->id}}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog @if(!$node->compatible) modal-sm @endif">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                            <h4 class="modal-title">{{trans('home.scan_qrcode')}}</h4>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                @if ($node->compatible)
                                    <div class="col-md-6">
                                        <div id="qrcode_ssr_img_{{$node->id}}" style="text-align: center;"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="qrcode_ss_img_{{$node->id}}" style="text-align: center;"></div>
                                    </div>
                                @else
                                    <div class="col-md-12">
                                        <div id="qrcode_ssr_img_{{$node->id}}" style="text-align: center;"></div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        <!-- END PAGE BASE CONTENT -->
    </div>
    <!-- END CONTENT BODY -->
@endsection
@section('script')
    <script src="/assets/global/plugins/clipboardjs/clipboard.min.js" type="text/javascript"></script>
    <script src="/assets/pages/scripts/components-clipboard.min.js" type="text/javascript"></script>
    <script src="/assets/global/plugins/jquery-qrcode/jquery.qrcode.min.js" type="text/javascript"></script>
    <script src="/assets/global/plugins/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
    <script src="/js/layer/layer.js" type="text/javascript"></script>

    <script type="text/javascript">
        // 切换充值方式
        $("#charge_type").change(function(){
            if ($(this).val() == 2) {
                $("#charge_balance").show();
                $("#charge_coupon_code").hide();
            } else {
                $("#charge_balance").hide();
                $("#charge_coupon_code").show();
            }
        });

        // 充值
        function charge() {
            var charge_type = $("#charge_type").val();
            var charge_coupon = $("#charge_coupon").val();
            var online_pay = $("#online_pay").val();

            if (charge_type == '2') {
                $("#charge_msg").show().html("正在跳转支付界面");
                window.location.href = '/buy/' + online_pay;
                return false;
            }

            if (charge_type == '1' && (charge_coupon == '' || charge_coupon == undefined)) {
                $("#charge_msg").show().html("{{trans('home.coupon_not_empty')}}");
                $("#charge_coupon").focus();
                return false;
            }

            $.ajax({
                url:'{{url('charge')}}',
                type:"POST",
                data:{_token:'{{csrf_token()}}', coupon_sn:charge_coupon},
                beforeSend:function(){
                    $("#charge_msg").show().html("{{trans('home.recharging')}}");
                },
                success:function(ret){
                    if (ret.status == 'fail') {
                        $("#charge_msg").show().html(ret.message);
                        return false;
                    }

                    $("#charge_modal").modal("hide");
                    window.location.reload();
                },
                error:function(){
                    $("#charge_msg").show().html("{{trans('home.error_response')}}");
                },
                complete:function(){}
            });
        }

        // 积分兑换流量
        function exchange() {
            $.ajax({
                type: "POST",
                url: "{{url('exchange')}}",
                async: false,
                data: {_token:'{{csrf_token()}}'},
                dataType: 'json',
                success: function (ret) {
                    layer.msg(ret.message, {time:1000}, function() {
                        if (ret.status == 'success') {
                            window.location.reload();
                        }
                    });
                }
            });

            return false;
        }
    </script>

    <script type="text/javascript">
        var UIModals = function () {
            var n = function () {
                @foreach($nodeList as $node)
                    $("#txt_{{$node->id}}").draggable({handle: ".modal-header"});
                    $("#qrcode_{{$node->id}}").draggable({handle: ".modal-header"});
                @endforeach
            };

            return {
                init: function () {
                    n()
                }
            }
        }();

        jQuery(document).ready(function () {
            UIModals.init()
        });

        // 循环输出节点scheme用于生成二维码
        @foreach ($nodeList as $node)
            $('#qrcode_ssr_img_{{$node->id}}').qrcode("{{$node->ssr_scheme}}");
            $('#qrcode_ss_img_{{$node->id}}').qrcode("{{$node->ss_scheme}}");
        @endforeach

        // 节点订阅
        function subscribe() {
            window.location.href = '{{url('subscribe')}}';
        }

        // 显示加密、混淆、协议
        function show(txt) {
            layer.msg(txt);
        }

        // 生成消息通道订阅二维码
        @if($is_push_bear && $push_bear_qrcode)
            $('#subscribe_qrcode').qrcode({render:"canvas", text:"{{$push_bear_qrcode}}", width:170, height:170});
        @endif

        // 更换订阅地址
        function exchangeSubscribe() {
            layer.confirm('更换订阅地址将导致：<br>1.旧地址立即失效；<br>2.连接密码被更改；', {icon: 7, title:'警告'}, function(index) {
                $.post("{{url('exchangeSubscribe')}}", {_token:'{{csrf_token()}}'}, function (ret) {
                    layer.msg(ret.message, {time:1000}, function () {
                        if (ret.status == 'success') {
                            window.location.reload();
                        }
                    });
                });

                layer.close(index);
            });
        }
    </script>
@endsection
