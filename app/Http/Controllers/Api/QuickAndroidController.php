<?php

namespace App\Http\Controllers\Api;

use App\Components\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Models\User;
use App\Http\Models\Goods;
use App\Http\Models\Order;
use App\Http\Models\Payment;
use App\Http\Models\SsConfig;
use App\Http\Models\SsGroup;
use App\Http\Models\Verify;
use App\Http\Models\UserLabel;
use App\Http\Models\GoodsLabel;
use App\Mail\activeUser;
use Illuminate\Http\Request;
use Response;
use Cache;
use Mail;
use DB;
use Log;

/**
 * 快速接口
 * Class QuickAndroidController
 *
 * @package App\Http\Controllers
 */
class QuickAndroidController extends Controller
{
    private static $success_code = 1;//成功
    private static $faulure_code = 0;//失败

    private static $none_code               = 10000;//无
    private static $create_order_failure    = 10001;//创建支付单失败
    private static $payment_not_exist       = 10002;//支付信息不存在
    private static $ads_reward_closed       = 10003;//Ads未开启
    private static $create_ads_waiting      = 10004;//Ads等待期
    private static $create_ads_failure      = 10005;//Ads创建失败
    private static $create_ads_daymax       = 10006;//Ads今日达到上限
    private static $check_ads_failure       = 10007;//Ads校验失败
    private static $param_error             = 10008;//参数错误
    private static $user_not_exist          = 10009;//用户不存在
    private static $act_psd_error           = 10010;//账号密码错误
    private static $ssruuid_error           = 10011;//ssruuid错误
    private static $register_limit          = 10012;//注册限制
    private static $quick_regist_failure    = 10013;//快速注册失败
    private static $id_user_not_exist       = 10014;//token用户不存在
    private static $send_email_failure      = 10015;//发送邮件失败
    private static $user_name_exist         = 10016;//邮箱账号已存在
    private static $token_exception         = 10017;//token异常，异地登陆
    private static $bundleid_error          = 10018;//bundleid错误
    private static $goods_id_error          = 10019;//商品id错误
    private static $product_id_error        = 10020;//产品id错误
    private static $in_app_error            = 10021;//内购错误
    private static $traffic_added_error     = 10022;//流量已经加过了


//  Status Code Description
//  21000 The App Store could not read the JSON object you provided.
//  21002 The data in the receipt-data property was malformed or missing.
//  21003 The receipt could not be authenticated.
//  21004 The shared secret you provided does not match the shared secret on file for your account.
//  21005 The receipt server is not currently available.
//  21006 This receipt is valid but the subscription has expired. When this status code is returned to your server, the receipt data is also decoded and returned as part of the response. Only returned for iOS 6 style transaction receipts for auto-renewable subscriptions.
//  21007 This receipt is from the test environment, but it was sent to the production environment for verification. Send it to the test environment instead.
//  21008 This receipt is from the production environment, but it was sent to the test environment for verification. Send it to the production environment instead.
//  21010 This receipt could not be authorized. Treat this the same as if a purchase was never made.
//  21100-21199 Internal data access error.
    
    private static $AdsOrder1               = '1';//Ads等待期
    private static $AdsOrder2               = '2';//Ads创建失败
    private static $AdsOrder3               = '3';//Ads今日达到上限
    private static $AdsOrder4               = '4';//Ads未开启

    private static $BundleList=["com.softmt.patatas"];

    protected static $systemConfig;

    function __construct()
    {
        self::$systemConfig = Helpers::systemConfig();
    }

    private function findAdsOrder(User $user)
    {
        if (self::$systemConfig['ads_add_traffic']) {
            //有未看的Ads，直接返回该Ads
            if (Cache::has('adsAddTraffic_' . md5($user['id']))) {
                return self::$AdsOrder1;//等待期
            }
            $goods = Goods::query()->where('type', 3)->where('status', 1)->first();
            if ($goods) {
                $goods_id = $goods['id'];
                // 判断是否存在未看的Ads
                $existsOrder = Order::query()->where('user_id', $user['id'])->where('goods_id', $goods_id)->where('status', 0)->where('created_at', '>=', date('Y-m-d', time()))->first();
                if ($existsOrder) {
                    return $existsOrder->order_sn;
                }
                $count = Order::query()->where('user_id', $user['id'])->where('goods_id', $goods_id)->where('status', 2)->where('created_at', '>=', date('Y-m-d', time()))->count();;
                if ($count < self::$systemConfig['ads_daily_count'])
                {
                    $traffic = mt_rand(self::$systemConfig['min_rand_traffic'], self::$systemConfig['max_rand_traffic']);
                    DB::beginTransaction();
                    try {

                        $orderSn = date('ymdHis') . mt_rand(100000, 999999);
                        
                        // 生成订单
                        $order = new Order();
                        $order->order_sn = $orderSn;
                        $order->user_id = $user['id'];
                        $order->goods_id = $goods_id;
                        $order->coupon_id = 0;
                        $order->origin_amount = 0;
                        $order->amount = 0;
                        $order->traffic = $traffic;
                        $order->is_expire = 0;
                        $order->pay_way = 5;
                        $order->status = 0;
                        $order->save();

                        DB::commit();

                        return $orderSn;
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return self::$AdsOrder2;//创建失败
                    }
                }
                else {
                    return self::$AdsOrder3;//Ads今日达到上限
                }
            }
            else
            {
                return self::$AdsOrder4;//Ads未开启
            }
        }
        else
        {
            return self::$AdsOrder4;//Ads未开启
        }
    }

    /**
     *
     * Generate v4 UUID
     *
     * Version 4 UUIDs are pseudo-random.
     */
    private function uuidv4()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    // 账号登录
    public function login(Request $request)
    {
        if ($request->method() == 'POST')
        {
            $username = trim($request->get('username'));
            $password = trim($request->get('password'));

            if (!$username || !$password) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 校验用户名是否已存在
            $exists = User::query()->where('username', $username)->first();
            if ($exists) {
                $user = User::query()->where('username', $username)->where('password', md5($password))->where('status', '>=', 0)->first();
                if (!$user) {
                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$act_psd_error]);
                }

                $ssruuid = $this->uuidv4();
                while (User::query()->where('ssruuid', $ssruuid)->first()) {
                    $ssruuid = $this->uuidv4();
                }
                $remember_token_android = makeRandStr(20);
                User::query()->where('id', $user->id)->update(['ssruuid' => $ssruuid, 'remember_token_android' => $remember_token_android, 'last_login' => time()]);

                // 节点列表
                $ssrList = [];
                $userLabelIds = UserLabel::query()->where('user_id', $user['id'])->pluck('label_id');
                if (!empty($userLabelIds)) {
                    $nodeList = DB::table('ss_node')
                        ->leftJoin('ss_node_label', 'ss_node.id', '=', 'ss_node_label.node_id')
                        ->whereIn('ss_node_label.label_id', $userLabelIds)
                        ->where('ss_node.status', 1)
                        ->groupBy('ss_node.id')
                        ->get();

                    foreach ($nodeList as &$node) {
                        $ssrList[] = [
                            'group_id' => $node->group_id,
                            'id' => $node->id,
                            'name' => $node->name,
                            'country_code' => $node->country_code,
                            'status' => $node->compatible ? 2 : 1
                        ];
                    }
                }

                $ads_sn = $this->findAdsOrder($user);

                // 基本信息
                $baseData = [
                    'id'                  => $user->id,
                    'level'               => $user->level,
                    'account_status'      => 1,
                    'token'               => $remember_token_android,
                    'status'              => $user->status,
                    'expire_time'         => $user->expire_time < date('Y-m-d H:i:s') ? ' ' : $user->expire_time,
                    'ads_sn'              => $ads_sn,
                    'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
                    'ssr_list'            => json_encode($ssrList),
                    'ssruuid'             => $ssruuid,
                    'has_port'            => $user->port != 0 ? 1 : 0
                ];

                $data = json_encode($baseData);

                return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
            }
            else
            {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$user_not_exist]);
            }
        }
    }

    // 快速登录
    public function quick(Request $request)
    {
        if ($request->method() == 'POST')
        {
            $ssruuid = trim($request->get('ssruuid'));
            $cacheKey = 'request_times_' . md5($request->getClientIp());

            if (!$ssruuid) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 校验ssruuid是否已存在
            $exists = User::query()->where('ssruuid', $ssruuid)->first();
            if ($exists) {
                $user = User::query()->where('ssruuid', $ssruuid)->where('status', '>=', 0)->first();
                if (!$user) {
                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$ssruuid_error]);
                }

                // 更新登录信息
                $remember_token_android = makeRandStr(20);
                User::query()->where('id', $user->id)->update(['remember_token_android' => $remember_token_android, 'last_login' => time()]);

                // 节点列表
                $ssrList = [];
                $userLabelIds = UserLabel::query()->where('user_id', $user['id'])->pluck('label_id');
                if (!empty($userLabelIds)) {
                    $nodeList = DB::table('ss_node')
                        ->leftJoin('ss_node_label', 'ss_node.id', '=', 'ss_node_label.node_id')
                        ->whereIn('ss_node_label.label_id', $userLabelIds)
                        ->where('ss_node.status', 1)
                        ->groupBy('ss_node.id')
                        ->get();

                    foreach ($nodeList as &$node) {
                        $ssrList[] = [
                            'group_id' => $node->group_id,
                            'id' => $node->id,
                            'name' => $node->name,
                            'country_code' => $node->country_code,
                            'status' => $node->compatible ? 2 : 1
                        ];
                    }
                }

                $ads_sn = $this->findAdsOrder($user);

//                return Response::json(['status' => self::$faulure_code, 'data' => $ads_sn, 'message' => 111]);
                // 0 : 开关账号密码设置
                // 1 : 有账号和密码----------------打开重置密码功能
                // 2 : 有账号无密码，账号并未经激活--打开邮箱激活功能功能
                // 3 : 有账号无密码，账号并已经激活--打开设置密码功能
                // 4 : 无账号---------------------打开设置账号功能
//                $account_status = 0;
                if (strlen($user->username) > 0 && strlen($user->password) > 0)
                {
                    $account_status = 1;
                }
                elseif (strlen($user->username) > 0)
                {
                    if (self::$systemConfig['is_active_register']) {
                        $account_status = $user->status == 1 ? 3 : 2;
                    }
                    else
                    {
                        $account_status = 3;
                    }
                }
                else {
                    $account_status = 4;
                }

                // 基本信息
                $baseData = [
                    'id'                  => $user->id,
                    'level'               => $user->level,
                    'account_status'      => $account_status,
                    'token'               => $remember_token_android,
                    'status'              => $user->status,
                    'expire_time'         => $user->expire_time < date('Y-m-d H:i:s') ? ' ' : $user->expire_time,
                    'ads_sn'              => $ads_sn,
                    'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
                    'ssr_list'            => json_encode($ssrList),
                    'has_port'            => $user->port != 0 ? 1 : 0
                ];

                $data = json_encode($baseData);

                return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
            }

            // 24小时内同IP注册限制
            if (self::$systemConfig['register_ip_limit']) {
                if (Cache::has($cacheKey)) {
                    $registerTimes = Cache::get($cacheKey);
                    if ($registerTimes >= self::$systemConfig['register_ip_limit']) {
                        return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$register_limit]);
                    }
                }
            }

            // 最后一个可用端口
            $last_user = User::query()->orderBy('id', 'desc')->first();
            $port = self::$systemConfig['is_rand_port'] ? Helpers::getRandPort() : $last_user->port + 1;

            // 默认加密方式、协议、混淆
            $method = SsConfig::query()->where('type', 1)->where('is_default', 1)->first();
            $protocol = SsConfig::query()->where('type', 2)->where('is_default', 1)->first();
            $obfs = SsConfig::query()->where('type', 3)->where('is_default', 1)->first();

            // 创建新用户
            $transfer_enable = self::$systemConfig['default_traffic'] * 1048576;
            $user = new User();
            $user->ssruuid = $ssruuid;
            $user->port = $port;
            $user->passwd = makeRandStr();
            $user->transfer_enable = $transfer_enable;
            $user->method = $method ? $method->name : 'aes-192-ctr';
            $user->protocol = $protocol ? $protocol->name : 'auth_chain_a';
            $user->obfs = $obfs ? $obfs->name : 'tls1.2_ticket_auth';
            $user->enable_time = date('Y-m-d H:i:s');
            $user->expire_time = date('Y-m-d H:i:s', strtotime("+" . self::$systemConfig['default_days'] . " days"));
            $user->reg_ip = $request->getClientIp();
            $user->referral_uid = 0;
            $user->last_login = time();
            $user->remember_token_android = makeRandStr(20);
            $user->save();

            if ($user->id) {
                // 注册次数+1
                if (Cache::has($cacheKey)) {
                    Cache::increment($cacheKey);
                } else {
                    Cache::put($cacheKey, 1, 1440); // 24小时
                }

                // 初始化默认标签
                if (strlen(self::$systemConfig['initial_labels_for_user'])) {
                    $labels = explode(',', self::$systemConfig['initial_labels_for_user']);
                    foreach ($labels as $label) {
                        $userLabel = new UserLabel();
                        $userLabel->user_id = $user->id;
                        $userLabel->label_id = $label;
                        $userLabel->save();
                    }
                }
            }

            $user = User::query()->where('ssruuid', $ssruuid)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$quick_regist_failure]);
            }

            // 节点列表
            $ssrList = [];
            $userLabelIds = UserLabel::query()->where('user_id', $user['id'])->pluck('label_id');
            if (!empty($userLabelIds)) {
                $nodeList = DB::table('ss_node')
                    ->leftJoin('ss_node_label', 'ss_node.id', '=', 'ss_node_label.node_id')
                    ->whereIn('ss_node_label.label_id', $userLabelIds)
                    ->where('ss_node.status', 1)
                    ->groupBy('ss_node.id')
                    ->get();

                foreach ($nodeList as &$node) {
                    $ssrList[] = [
                        'group_id' => $node->group_id,
                        'id' => $node->id,
                        'name' => $node->name,
                        'country_code' => $node->country_code,
                        'status' => $node->compatible ? 2 : 1
                    ];
                }
            }

            $ads_sn = $this->findAdsOrder($user);

            // 基本信息
            $baseData = [
                'id'                  => $user->id,
                'level'               => $user->level,
                'account_status'      => 0,
                'token'               => $user->remember_token_android,
                'status'              => $user->status,
                'expire_time'         => $user->expire_time < date('Y-m-d H:i:s') ? ' ' : $user->expire_time,
                'ads_sn'              => $ads_sn,
                'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
                'ssr_list'            => json_encode($ssrList),
                'has_port'            => $user->port != 0 ? 1 : 0
            ];

            $data = json_encode($baseData);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
        }
    }

    // 设置账号
    public function setAccount(Request $request)
    {
        if ($request->method() == 'POST') {
            $username = trim($request->get('username'));
            $token = trim($request->get('token'));
            $userid = intval($request->get('userid', 0));
            if (!$token || !$username || $userid <= 0) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('id', $userid)->where('status', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
            }

            if (!$user->remember_token_android || $user->remember_token_android != $token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
            }

            if ($user->username && $user->username == $username) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$user_name_exist]);
            }

            if (User::query()->where('username', $username)->first()) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$user_name_exist]);
            }

            // 更新信息
            User::query()->where('id', $user->id)->update(['username' => $username, 'last_login' => time()]);
            // 发送邮件
            if (self::$systemConfig['is_active_register']) {

                $verify = Verify::query()->where('user_id', $user->id)->where('status', 0)->first();

                // 生成激活账号的地址
                $token = md5(self::$systemConfig['website_name'] . $username . microtime());

                if ($verify)
                {
                    Verify::query()->where('user_id', $user->id)->update(['token' => $token]);
                }
                else
                {
                    $verify = new Verify();
                    $verify->user_id = $user->id;
                    $verify->username = $username;
                    $verify->token = $token;
                    $verify->status = 0;
                    $verify->save();
                }

                $activeUserUrl = self::$systemConfig['website_url'] . '/active/' . $token;
                $title = '注册激活';
                $content = '请求地址：' . $activeUserUrl;

                try {
                    Mail::to($username)->send(new activeUser(self::$systemConfig['website_name'], $activeUserUrl));
                    $this->sendEmailLog($user->id, $title, $content);

                    // 基本信息
                    $baseData = [
                        'account_status'  => 2
                    ];

                    $data = json_encode($baseData);

                    return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
                } catch (\Exception $e) {
                    $this->sendEmailLog($user->id, $title, $content, 0, $e->getMessage());
                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$send_email_failure]);
                }
            }

            // 基本信息
            $baseData = [
                'account_status'  => 3
            ];

            $data = json_encode($baseData);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
        }
    }


    public  function verifyAccount(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));
            $userid = intval($request->get('userid', 0));
            if (!$token || $userid <= 0) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 发送邮件
            if (self::$systemConfig['is_active_register']) {
                // 校验用户名是否已存在
                $user = User::query()->where('id', $userid)->first();
                if (!$user || !$user->username) {
                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
                }

                if (!$user->remember_token_android || $user->remember_token_android != $token) {
                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
                }

                if ($user->status == 1)
                {
                    // 基本信息
                    $baseData = [
                        'account_status' => 3
                    ];

                    $data = json_encode($baseData);

                    return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);

                }

                // 更新信息
                User::query()->where('id', $user->id)->update(['last_login' => time()]);

                $verify = Verify::query()->where('user_id', $user->id)->where('status', 0)->first();

                // 生成激活账号的地址
                $token = md5(self::$systemConfig['website_name'] . $user->username . microtime());

                if ($verify)
                {
                    $verify->token = $token;
                    $verify->save();
                }
                else
                {
                    $verify = new Verify();
                    $verify->user_id = $user->id;
                    $verify->username = $user->username;
                    $verify->token = $token;
                    $verify->status = 0;
                    $verify->save();
                }

                $activeUserUrl = self::$systemConfig['website_url'] . '/active/' . $token;
                $title = '注册激活';
                $content = '请求地址：' . $activeUserUrl;

                try {
                    Mail::to($user->username)->send(new activeUser(self::$systemConfig['website_name'], $activeUserUrl));
                    $this->sendEmailLog($user->id, $title, $content);

                    // 基本信息
                    $baseData = [
                        'account_status' => 2
                    ];

                    $data = json_encode($baseData);

                    return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
                } catch (\Exception $e) {
                    $this->sendEmailLog($user->id, $title, $content, 0, $e->getMessage());

                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$send_email_failure]);
                }
            }

            // 基本信息
            $baseData = [
                'account_status'  => 3
            ];

            $data = json_encode($baseData);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
        }
    }

    // 设置密码
    public function setPassword(Request $request)
    {
        if ($request->method() == 'POST') {
            $password = trim($request->get('password'));
            $token = trim($request->get('token'));
            $userid = intval($request->get('userid', 0));
            if (!$token || !$password || $userid <= 0) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('id', $userid)->where('status', 1)->first();
            if (!$user || !$user->username || $user->password) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
            }

            if (!$user->remember_token_android || $user->remember_token_android != $token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
            }

            // 更新信息
            User::query()->where('id', $user->id)->update(['password' => md5($password), 'last_login' => time()]);

            // 基本信息
            $baseData = [
                'account_status'  => 1
            ];

            $data = json_encode($baseData);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
        }
    }

    public  function resetPassword(Request $request)
    {
        if ($request->method() == 'POST') {
            $password = trim($request->get('password'));
            $token = trim($request->get('token'));
            $userid = intval($request->get('userid', 0));
            if (!$token || !$password || $userid <= 0) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('id', $userid)->where('status', 1)->first();
            if (!$user || !$user->username || !$user->password) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
            }

            if (!$user->remember_token_android || $user->remember_token_android != $token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
            }

            // 更新信息
            User::query()->where('id', $user->id)->update(['password' => md5($password), 'last_login' => time()]);

            // 基本信息
            $baseData = [
                'account_status'  => 1
            ];

            $data = json_encode($baseData);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
        }
    }

    // 获取数据
    public function getData(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));
            $userid = intval($request->get('userid', 0));
            if (!$token || $userid <= 0) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('id', $userid)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
            }

            if (!$user->remember_token_android || $user->remember_token_android != $token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
            }

            // 更新登录信息
            User::query()->where('id', $user->id)->update(['last_login' => time()]);

            // 节点列表
            $ssrList = [];
            $userLabelIds = UserLabel::query()->where('user_id', $user['id'])->pluck('label_id');
            if (!empty($userLabelIds)) {
                $nodeList = DB::table('ss_node')
                    ->leftJoin('ss_node_label', 'ss_node.id', '=', 'ss_node_label.node_id')
                    ->whereIn('ss_node_label.label_id', $userLabelIds)
                    ->where('ss_node.status', 1)
                    ->groupBy('ss_node.id')
                    ->get();

                foreach ($nodeList as &$node) {
                    $ssrList[] = [
                        'group_id' => $node->group_id,
                        'id' => $node->id,
                        'name' => $node->name,
                        'country_code' => $node->country_code,
                        'status' => $node->compatible ? 2 : 1
                    ];
                }
            }

            $ads_sn = $this->findAdsOrder($user);

//            return Response::json(['status' => self::$faulure_code, 'data' => $ads_sn, 'message' => 222]);
            // if (strlen($ads_sn) == 1) {
            //     $ads_sn = '';
            // }

            // 0 : 开关账号密码设置
            // 1 : 有账号和密码--打开重置密码功能
            // 2 : 有账号无密码，账号并未经激活--打开邮箱激活功能功能
            // 3 : 有账号无密码，账号并已经激活--打开设置密码功能
            // 4 : 无账号打开设置账号功能
//            $account_status = 0;
            if (strlen($user->username) > 0 && strlen($user->password) > 0)
            {
                $account_status = 1;
            }
            elseif (strlen($user->username) > 0)
            {
                if (self::$systemConfig['is_active_register']) {
                    $account_status = $user->status == 1 ? 3 : 2;
                }
                else
                {
                    $account_status = 3;
                }
            }
            else {
                $account_status = 4;
            }

            // 基本信息
            $baseData = [
                'level'           => $user->level,
                'account_status'  => $account_status,
                'status'          => $user->status,
                'expire_time'     => $user->expire_time < date('Y-m-d H:i:s') ? ' ' : $user->expire_time,
                'ads_sn'          => $ads_sn,
                'unused_transfer' => $user->transfer_enable - $user->u - $user->d,
                'ssr_list'        => json_encode($ssrList),
                'has_port'        => $user->port != 0 ? 1 : 0
            ];

            $data = json_encode($baseData);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
        }
    }

    // 获取流量信息
    public function getTraffic(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));
            $userid = intval($request->get('userid', 0));
            if (!$token || $userid <= 0) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('id', $userid)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
            }

            if (!$user->remember_token_android || $user->remember_token_android != $token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
            }

            $traffic = [
                'status'              => $user->status,
                'expire_time'         => $user->expire_time < date('Y-m-d H:i:s') ? ' ' : $user->expire_time,
                'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
             ];

            $data = json_encode($traffic);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
        }
    }

    //获取节点列表
    public function getNodeList(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));
            $userid = intval($request->get('userid', 0));
            if (!$token || $userid <= 0) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('id', $userid)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
            }

            if (!$user->remember_token_android || $user->remember_token_android != $token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
            }

            $ssrList = [];
            // 节点列表
            $userLabelIds = UserLabel::query()->where('user_id', $user['id'])->pluck('label_id');
            if (empty($userLabelIds)) {
                $data = json_encode($ssrList);
                return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
            }

            $nodeList = DB::table('ss_node')
                ->leftJoin('ss_node_label', 'ss_node.id', '=', 'ss_node_label.node_id')
                ->whereIn('ss_node_label.label_id', $userLabelIds)
                ->where('ss_node.status', 1)
                ->groupBy('ss_node.id')
                ->get();


            foreach ($nodeList as &$node) {
                $ssrList[] = [
                    'group_id'     => $node->group_id,
                    'id'           => $node->id,
                    'name'         => $node->name,
                    'country_code' => $node->country_code,
                    'status'       => $node->compatible ? 2 : 1,
                    'has_port'     => $user->port != 0 ? 1 : 0
                ];
            }

            $data = json_encode($ssrList);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
        }
    }

    //获取节点信息
    public function getNodeInfo(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));
            $nodeid = trim($request->get('nodeid'));
            $userid = intval($request->get('userid', 0));
            $type = intval($request->get('type', 0));
            if (!$token || !$nodeid || $userid <= 0) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('id', $userid)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
            }

            if (!$user->remember_token_android || $user->remember_token_android != $token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
            }

            $ssrList = [];
            // 节点列表
            $userLabelIds = UserLabel::query()->where('user_id', $user['id'])->pluck('label_id');
            if (empty($userLabelIds)) {
                $data = json_encode($ssrList);
                return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
            }

            $nodeList = DB::table('ss_node')
                ->leftJoin('ss_node_label', 'ss_node.id', '=', 'ss_node_label.node_id')
                ->whereIn('ss_node_label.label_id', $userLabelIds)
                ->where('ss_node.status', 1)
                ->groupBy('ss_node.id')
                ->get();

            foreach ($nodeList as &$node) {
                if ($node->id == $nodeid)
                {
                    // 获取分组名称
                    $group = SsGroup::query()->where('id', $node->group_id)->first();

                    if ($node->compatible && $type == 1)
                    {
                        // 生成ss scheme
                        $ss_str = $user->method . ':' . $user->passwd . '@';
                        $ss_str .= ($node->server ? $node->server : $node->ip) . ':' . $user->port;
                        $ss_str = base64url_encode($ss_str);
                        $ss_scheme = 'ss://' . $ss_str;
                    }
                    else {
                    // 生成ssr scheme
                    $obfs_param = $user->obfs_param ? $user->obfs_param : $node->obfs_param;
                    $protocol_param = $node->single ? $user->port . ':' . $user->passwd : $user->protocol_param;

                    $ssr_str = '';
                    $ssr_str .= ($node->server ? $node->server : $node->ip) . ':' . ($node->single ? $node->single_port : $user->port);
                    $ssr_str .= ':' . ($node->single ? $node->single_protocol : $user->protocol) . ':' . ($node->single ? $node->single_method : $user->method);
                    $ssr_str .= ':' . ($node->single ? $node->single_obfs : $user->obfs) . ':' . ($node->single ? base64url_encode($node->single_passwd) : base64url_encode($user->passwd));
                    $ssr_str .= '/?obfsparam=' . base64url_encode($obfs_param);
                    $ssr_str .= '&protoparam=' . ($node->single ? base64url_encode($user->port . ':' . $user->passwd) : base64url_encode($protocol_param));
                    $ssr_str .= '&remarks=' . base64url_encode($node->name);
                    $ssr_str .= '&group=' . base64url_encode(empty($group) ? '' : $group->name);
                    $ssr_str .= '&udpport=0';
                    $ssr_str .= '&uot=0';
                    $ssr_str = base64url_encode($ssr_str);
                    $ssr_scheme = 'ssr://' . $ssr_str;
                    }

                    // 节点在线状态
//                    $nodeInfo = SsNodeInfo::query()->where('node_id', $node->id)->where('log_time', '>=', strtotime("-10 minutes"))->orderBy('id', 'desc')->first();
//                    $online_status = empty($nodeInfo) || empty($nodeInfo->load) ? 0 : 1;

                    $ssrList = [
                        'group_id'     => $node->group_id,
                        'id'           => $node->id,
                        'name'         => $node->name,
                        'country_code' => $node->country_code,
                        'status'       => $node->compatible ? 2 : 1,
                        'ssr'          => $type == 1 ? $ss_scheme : $ssr_scheme,
                        'has_port'     => $user->port != 0 ? 1 : 0
                    ];
                }
            }

            $data = json_encode($ssrList);

//            usleep(5000000);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
        }
    }

    public function getGoodsList(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));
            $userid = intval($request->get('userid', 0));
            $bundle = trim($request->get('bundle'));
            if (!$token || !$bundle || $userid <= 0) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('id', $userid)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
            }

            if (!$user->remember_token_android || $user->remember_token_android != $token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
            }

            $goodsList = [];
            $productList = Goods::query()->where("bundle", $bundle)->where('is_del', 0)->where("status", 1)->where("type", '!=', 3)->orderBy('sort', 'desc')->paginate(30);
            foreach ($productList as $product) {
                $goodsList [] = [
                    'id'      => $product->id,
                    'desc'    => $product->desc,
                    'status'  => $product->status,
                    'traffic' => $product->traffic * 1048576,
                    'days'    => $product->days,
                    'type'    => $product->type,
                ];
            }
            $data = json_encode($goodsList);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
        }
    }

//    /**
//     * curl请求苹果app_store验证地址
//     * @param $data_string      验证字符串
//     * @param $istest           是否是测试地址 true正式地址 false测试地址
//     * @return mixed
//     */
//    private function http_post_data($data_string, $istest) {
//        if ($istest) {
//            // 正式验证地址
//            $url = 'https://buy.itunes.apple.com/verifyReceipt';
//        } else {
//            // 测试验证地址
//            $url = 'https://sandbox.itunes.apple.com/verifyReceipt';
//        }
//        $curl_handle=curl_init();
//        curl_setopt($curl_handle,CURLOPT_URL, $url);
//        curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($curl_handle,CURLOPT_HEADER, 0);
//        curl_setopt($curl_handle,CURLOPT_POST, true);
//        curl_setopt($curl_handle,CURLOPT_POSTFIELDS, $data_string);
//        curl_setopt($curl_handle,CURLOPT_SSL_VERIFYHOST, 0);
//        curl_setopt($curl_handle,CURLOPT_SSL_VERIFYPEER, 0);
//        $response_json =curl_exec($curl_handle);
//        Log::info('AppStore $response_json: ' . $response_json);
//        $response =json_decode($response_json);
//        curl_close($curl_handle);
//        return $response;
//    }

    // 验证收据
    public function verifyReceipt(Request $request)
    {
//        if ($request->method() == 'POST') {
//            $content = $request->getContent();
//            //Attempt to decode the incoming RAW post data from JSON.
//            $decoded = json_decode($content, true);
//
//            //If json_decode failed, the JSON is invalid.
//            if(!is_array($decoded)){
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
//            }
//
//            $token = $decoded['token'];
//            $userid = $decoded['userid'];
//            $receipt_data = $decoded['receipt-data'];
//            $password = $decoded['password'];
//
//            if (!$token || $userid <= 0 || empty($password) || !$receipt_data || strlen($receipt_data) < 20) {
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
//            }
//
//            // 校验用户名是否已存在
//            $user = User::query()->where('id', $userid)->where('status', '>=', 0)->first();
//            if (!$user) {
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
//            }
//
//            if (!$user->remember_token_android || $user->remember_token_android != $token) {
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
//            }
//
//            $jobStartTime = microtime(true);
//            $jsonData = array("receipt-data"=>$decoded['receipt-data'], "password" =>$decoded['password']);
//            $jsonReceiptData = json_encode($jsonData);
//
//            $response = $this->http_post_data($jsonReceiptData, true);
//            Log::info('AppStore Receipt status：' . $response->status);
//            if($response->status == 21007) {
//                $response = $this->http_post_data($jsonReceiptData, false);
//            }
//            elseif ($response->status == 21008) {
//                $response = $this->http_post_data($jsonReceiptData, true);
//            }
//            Log::info('AppStore Receipt status：' . $response->status);
//
//            $jobEndTime = microtime(true);
//            $jobUsedTime = round(($jobEndTime - $jobStartTime) , 4);
//
//            Log::info('AppStore Receipt ，耗时' . $jobUsedTime . '秒');
//            if($response->status == 0){
//                $bundleid= $response->receipt->bundle_id;
//                if($bundleid && in_array($bundleid, self::$BundleList)) {
//                    $in_app = $response->receipt->in_app;
//                    if($in_app && !empty($in_app)){
//                        // 取出第一个支付时间
//                        $firsttime = $in_app[0]->purchase_date;
//                        foreach($in_app as $k=>$v){
//                            if($firsttime < $v->purchase_date){
//                                $firsttime = $v->purchase_date;
//                            }
//                        }
//                        foreach($in_app as $key=>$value){
//                            if($value->purchase_date == $firsttime){
//                                $arr = $value;
//                            }
//                        }
//                        // 产品的ID
//                        $product_id = $arr->product_id;
//                        // 原始购买时间毫秒
////                        $purchase_date_pst = $arr->original_purchase_date_ms;
//                        // 到期时间毫秒
////                        $expires_date_formatted = $arr->expires_date_ms;
//                        // 支付时间毫秒
////                        $purchase_date_ms = $arr->purchase_date_ms;
////                        if(empty($expires_date_formatted)){
//                            $expires_date_formatted = 0;
////                        }
//                        if($product_id && !empty($product_id)){
//                            $transaction_id = $arr->transaction_id;
//
//                            // 判断是否存在同个商品的未支付订单
//                            $existsOrder = Order::query()->where('order_sn', $transaction_id)->where('user_id', $user->id)->first();
//                            if ($existsOrder) {
//                                //订单已存在，流量已加过
//                                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$traffic_added_error]);
//                            }
//
//                            $goods = Goods::query()->where('is_del', 0)->where("status", 1)->where("desc", $product_id)->first();
//                            if ($goods)
//                            {
//                                DB::beginTransaction();
//                                try {
//                                    $orderSn = $transaction_id;//date('ymdHis') . mt_rand(100000, 999999);
//                                    $sn = makeRandStr(12);
//
//                                    // 生成订单
//                                    $order = new Order();
//                                    $order->order_sn = $orderSn;
//                                    $order->user_id = $user->id;
//                                    $order->goods_id = $goods->id;
//                                    $order->coupon_id = 0;
//                                    $order->origin_amount = $goods->price;
//                                    $order->amount = $goods->price;
//                                    $order->expire_at = date("Y-m-d H:i:s", strtotime("+" . $goods->days . " days"));
//                                    $order->is_expire = 0;
//                                    $order->pay_way = 3;
//                                    $order->status = 2;
//                                    $order->save();
//
//                                    // 生成支付单
//                                    $payment = new Payment();
//                                    $payment->sn = $sn;
//                                    $payment->user_id = $user->id;
//                                    $payment->oid = $order->oid;
//                                    $payment->order_sn = $orderSn;
//                                    $payment->pay_way = 3;
//                                    $payment->amount = $goods->price;
//                                    $payment->status = 1;
//                                    $payment->save();
//
//
//                                    // 把商品的流量加到账号上
//                                    User::query()->where('id', $user->id)->increment('transfer_enable', $goods->traffic * 1048576);
//
//                                    // 套餐就改流量重置日，流量包不改
//                                    if ($goods->type == 2) {
//                                        // 将商品的有效期和流量自动重置日期加到账号上
//                                        $traffic_reset_day = in_array(date('d'), [29, 30, 31]) ? 28 : abs(date('d'));
//                                        User::query()->where('id', $order->user_id)->update(['traffic_reset_day' => $traffic_reset_day, 'expire_time' => date('Y-m-d H:i:s', strtotime("+" . $goods->days . " days", strtotime($order->user->expire_time))), 'enable' => 1]);
//                                    } else {
//
//                                        // 剩余有效期小于商品有效期时更新有效期
//                                        $lastCanUseDays = floor(round(strtotime($user->expire_time) - strtotime(date('Y-m-d H:i:s'))) / 3600 / 24);
//                                        if ($lastCanUseDays < $goods->days) {
//                                            User::query()->where('id', $user->id)->update(['expire_time' => date('Y-m-d H:i:s', strtotime("+" . $goods->days . " days")), 'enable' => 1]);
//                                        }
//
//                                    }
//                                    $hasPort = 1;
//                                    $ssrList = [];
//                                    $portUser = User::query()->where('id', $order->user_id)->where('enable', 1)->where('port', 0)->whereRaw("u + d < transfer_enable")->get();
//                                    if (!$portUser) {
//                                        $port = self::$systemConfig['is_rand_port'] ? Helpers::getRandPort() : Helpers::getOnlyPort();
//                                        if ($port == 0)
//                                        {
//                                            $hasPort = 0;
//                                        }
//                                        else
//                                        {
//                                            User::query()->where('id', $order->user_id)->update(['port' => $port]);
//                                        }
//                                        $userLabelIds = UserLabel::query()->where('user_id', $user->id)->pluck('label_id');
//                                        if (!empty($userLabelIds)) {
//                                            $nodeList = DB::table('ss_node')
//                                                ->leftJoin('ss_node_label', 'ss_node.id', '=', 'ss_node_label.node_id')
//                                                ->whereIn('ss_node_label.label_id', $userLabelIds)
//                                                ->where('ss_node.status', 1)
//                                                ->groupBy('ss_node.id')
//                                                ->get();
//
//                                            foreach ($nodeList as &$node) {
//                                                $ssrList[] = [
//                                                    'group_id' => $node->group_id,
//                                                    'id' => $node->id,
//                                                    'name' => $node->name,
//                                                    'country_code' => $node->country_code,
//                                                    'status' => $node->compatible ? 2 : 1
//                                                ];
//                                            }
//                                        }
//                                    }
//
//                                    // 写入用户标签
//                                    if ($goods->label) {
//                                        // 取出现有的标签
//                                        $userLabels = UserLabel::query()->where('user_id', $order->user_id)->pluck('label_id')->toArray();
//                                        $goodsLabels = GoodsLabel::query()->where('goods_id', $order->goods_id)->pluck('label_id')->toArray();
//                                        $newUserLabels = array_merge($userLabels, $goodsLabels);
//
//                                        // 删除用户所有标签
//                                        UserLabel::query()->where('user_id', $order->user_id)->delete();
//
//                                        // 生成标签
//                                        foreach ($newUserLabels as $vo) {
//                                            $obj = new UserLabel();
//                                            $obj->user_id = $order->user_id;
//                                            $obj->label_id = $vo;
//                                            $obj->save();
//                                        }
//                                    }
//                                    DB::commit();
//
//
//                                    $user = User::query()->where('remember_token_android', $token)->where('status', '>=', 0)->first();
//                                    if (!$user) {
//                                        return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
//                                    }
//
//                                    if (!empty($ssrList)) {
//                                        $result = [
//                                            'status'              => $user->status,
//                                            'expire_time'         => $user->expire_time < date('Y-m-d H:i:s') ? ' ' : $user->expire_time,
//                                            'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
//                                            'purchase_transfer'   => flowAutoShow($goods->traffic * 1048576),
//                                            'has_port'            => $hasPort,
//                                            'days'                => $goods->days,
//                                            'ssr_list'            => json_encode($ssrList)
//                                        ];
//                                    }
//                                    else
//                                    {
//                                        $result = [
//                                            'status'              => $user->status,
//                                            'expire_time'         => $user->expire_time < date('Y-m-d H:i:s') ? ' ' : $user->expire_time,
//                                            'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
//                                            'purchase_transfer'   => flowAutoShow($goods->traffic * 1048576),
//                                            'days'                => $goods->days,
//                                            'has_port'            => $hasPort
//                                        ];
//                                    }
//
//                                    $data = json_encode($result);
//
//                                    return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
//                                } catch (\Exception $e) {
//                                    DB::rollBack();
//                                    Log::error('创建支付订单失败：' . $e->getMessage());
//                                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$create_order_failure]);
//                                }
//                            }
//                            else
//                                {
//                                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$goods_id_error]);
//                            }
//                        }else{
//                            return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$product_id_error]);
//                        }
//                    }else{
//                        return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$in_app_error]);
//                    }
//                }else{
//                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$bundleid_error]);
//                }
//            }else{
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => $response->status]);
//            }
//        }
    }

//    // 创建支付单
//    public function createOrder(Request $request)
//    {
//        if ($request->method() == 'POST') {
//            $token = trim($request->get('token'));
//            $userid = intval($request->get('userid', 0));
//            if (!$token || $userid <= 0) {
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
//            }
//
//            // 校验用户名是否已存在
//            $user = User::query()->where('id', $userid)->where('status', '>=', 0)->first();
//            if (!$user) {
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
//            }
//
//            if (!$user->remember_token_android || $user->remember_token_android != $token) {
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
//            }
//
//            $goods_id = intval($request->get('goods_id'));
//
//            $goods = Goods::query()->where('id', $goods_id)->where('status', 1)->first();
//            if (!$goods) {
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$goods_removed]);
//            }
//
//            // 判断是否存在同个商品的未支付订单
//            $existsOrder = Order::query()->where('goods_id', $goods_id)->where('status', 0)->where('user_id', $user['id'])->first();
//            if ($existsOrder) {
//                //有未支付的订单，直接返回该订单信息
//                return Response::json(['status' => self::$success_code, 'data' => $existsOrder->order_sn, 'message' => self::$none_code]);
//            }
//
//            $amount = $goods->price;
//            DB::beginTransaction();
//            try {
//                $orderSn = date('ymdHis') . mt_rand(100000, 999999);
//                $sn = makeRandStr(12);
//
//                // 生成订单
//                $order = new Order();
//                $order->order_sn = $orderSn;
//                $order->user_id = $user['id'];
//                $order->goods_id = $goods_id;
//                $order->coupon_id = !empty($coupon) ? $coupon->id : 0;
//                $order->origin_amount = $goods->price;
//                $order->amount = $amount;
//                $order->expire_at = date("Y-m-d H:i:s", strtotime("+" . $goods->days . " days"));
//                $order->is_expire = 0;
//                $order->pay_way = 3;
//                $order->status = 0;
//                $order->save();
//
//                // 生成支付单
//                $yzy = new Yzy();
//                $result = $yzy->createQrCode($goods->name, $amount * 100, $orderSn);
//                if (isset($result['error_response'])) {
//                    Log::error('【有赞云】创建二维码失败：' . $result['error_response']['msg']);
//
//                    throw new \Exception($result['error_response']['msg']);
//                }
//
//                $payment = new Payment();
//                $payment->sn = $sn;
//                $payment->user_id = $user['id'];
//                $payment->oid = $order->oid;
//                $payment->order_sn = $orderSn;
//                $payment->pay_way = 1;
//                $payment->amount = $amount;
//                $payment->qr_id = $result['response']['qr_id'];
//                $payment->qr_url = $result['response']['qr_url'];
//                $payment->qr_code = $result['response']['qr_code'];
//                $payment->qr_local_url = $this->base64ImageSaver($result['response']['qr_code']);
//                $payment->status = 1;
//                $payment->save();
//
//                DB::commit();
//
//                return Response::json(['status' => self::$success_code, 'data' => $orderSn, 'message' => self::$none_code]);
//            } catch (\Exception $e) {
//                DB::rollBack();
//
//                Log::error('创建支付订单失败：' . $e->getMessage());
//
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$create_order_failure]);
//            }
//        }
//    }
//
//    public function checkOrder(Request $request)
//    {
//
//        if ($request->method() == 'POST') {
//            $token = trim($request->get('token'));
//            $userid = intval($request->get('userid', 0));
//            if (!$token || $userid <= 0) {
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
//            }
//
//            // 校验用户名是否已存在
//            $user = User::query()->where('id', $userid)->where('status', '>=', 0)->first();
//            if (!$user) {
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
//            }
//
//            if (!$user->remember_token_android || $user->remember_token_android != $token) {
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
//            }
//
//            $order_sn = $request->get('order_sn');
//            $payment = Payment::query()->where('order_sn', $order_sn)->first();
//            if (!$payment) {
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$payment_not_exist]);
//            }
//
//            //缺少去苹果服务器校验步骤
//
//            // 处理订单
//            DB::beginTransaction();
//            try {
//                // 更新支付单
//                $payment->status = 1;
//                $payment->save();
//
//                // 更新订单
//                $order = Order::query()->with(['user'])->where('oid', $payment->oid)->first();
//                $order->status = 2;
//                $order->save();
//
//                // 如果买的是套餐，则先将之前购买的所有套餐置都无效，并扣掉之前所有套餐的流量
//                $goods = Goods::query()->where('id', $order->goods_id)->first();
//                if ($goods->type == 2) {
//                    $existOrderList = Order::query()
//                        ->with(['goods'])
//                        ->whereHas('goods', function ($q) {
//                            $q->where('type', 2);
//                        })
//                        ->where('user_id', $order->user_id)
//                        ->where('oid', '<>', $order->oid)
//                        ->where('is_expire', 0)
//                        ->get();
//
//                    foreach ($existOrderList as $vo) {
//                        Order::query()->where('oid', $vo->oid)->update(['is_expire' => 1]);
//                        User::query()->where('id', $order->user_id)->decrement('transfer_enable', $vo->goods->traffic * 1048576);
//                    }
//                }
//
//                // 把商品的流量加到账号上
//                User::query()->where('id', $order->user_id)->increment('transfer_enable', $goods->traffic * 1048576);
//
//                // 套餐就改流量重置日，流量包不改
//                if ($goods->type == 2) {
//                    // 将商品的有效期和流量自动重置日期加到账号上
//                    $traffic_reset_day = in_array(date('d'), [29, 30, 31]) ? 28 : abs(date('d'));
//                    User::query()->where('id', $order->user_id)->update(['traffic_reset_day' => $traffic_reset_day, 'expire_time' => date('Y-m-d H:i:s', strtotime("+" . $goods->days . " days", strtotime($order->user->expire_time))), 'enable' => 1]);
//                } else {
//
//                    // 剩余有效期小于商品有效期时更新有效期
//                    $lastCanUseDays = floor(round(strtotime($user->expire_time) - strtotime(date('Y-m-d H:i:s'))) / 3600 / 24);
//                    if ($lastCanUseDays < $goods->days) {
//                        User::query()->where('id', $user->id)->update(['expire_time' => date('Y-m-d H:i:s', strtotime("+" . $goods->days . " days")), 'enable' => 1]);
//                    }
//
//                }
//                $hasPort = 1;
//                $ssrList = [];
//                $portUser = User::query()->where('id', $order->user_id)->where('enable', 1)->where('port', 0)->whereRaw("u + d < transfer_enable")->get();
//                if (!$portUser) {
//                    $port = self::$systemConfig['is_rand_port'] ? Helpers::getRandPort() : Helpers::getOnlyPort();
//                    if ($port == 0)
//                    {
//                        $hasPort = 0;
//                    }
//                    else
//                    {
//                        User::query()->where('id', $order->user_id)->update(['port' => $port]);
//                    }
//                    $userLabelIds = UserLabel::query()->where('user_id', $user->id)->pluck('label_id');
//                    if (!empty($userLabelIds)) {
//                        $nodeList = DB::table('ss_node')
//                            ->leftJoin('ss_node_label', 'ss_node.id', '=', 'ss_node_label.node_id')
//                            ->whereIn('ss_node_label.label_id', $userLabelIds)
//                            ->where('ss_node.status', 1)
//                            ->groupBy('ss_node.id')
//                            ->get();
//
//                        foreach ($nodeList as &$node) {
//                            $ssrList[] = [
//                                'group_id' => $node->group_id,
//                                'id' => $node->id,
//                                'name' => $node->name,
//                                'country_code' => $node->country_code,
//                                'status' => $node->compatible ? 2 : 1,
//                            ];
//                        }
//                    }
//                }
//
//                // 写入用户标签
//                if ($goods->label) {
//                    // 取出现有的标签
//                    $userLabels = UserLabel::query()->where('user_id', $order->user_id)->pluck('label_id')->toArray();
//                    $goodsLabels = GoodsLabel::query()->where('goods_id', $order->goods_id)->pluck('label_id')->toArray();
//                    $newUserLabels = array_merge($userLabels, $goodsLabels);
//
//                    // 删除用户所有标签
//                    UserLabel::query()->where('user_id', $order->user_id)->delete();
//
//                    // 生成标签
//                    foreach ($newUserLabels as $vo) {
//                        $obj = new UserLabel();
//                        $obj->user_id = $order->user_id;
//                        $obj->label_id = $vo;
//                        $obj->save();
//                    }
//                }
//                DB::commit();
//
//
//                $user = User::query()->where('remember_token_android', $token)->where('status', '>=', 0)->first();
//                if (!$user) {
//                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
//                }
//
//                if (!empty($ssrList)) {
//                    $result = [
//                        'status'              => $user->status,
//                        'expire_time'         => $user->expire_time < date('Y-m-d H:i:s') ? ' ' : $user->expire_time,
//                        'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
//                        'purchase_transfer'   => flowAutoShow($goods->traffic * 1048576),
//                        'has_port'            => $hasPort,
//                        'days'                => $goods->days,
//                        'ssr_list'            => json_encode($ssrList)
//                    ];
//                }
//                else
//                {
//                    $result = [
//                        'status'              => $user->status,
//                        'expire_time'         => $user->expire_time < date('Y-m-d H:i:s') ? ' ' : $user->expire_time,
//                        'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
//                        'purchase_transfer'   => flowAutoShow($goods->traffic * 1048576),
//                        'days'                => $goods->days,
//                        'has_port'            => $hasPort
//                    ];
//                }
//
//                $data = json_encode($result);
//
//                return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
//            } catch (\Exception $e) {
//                DB::rollBack();
//                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$check_order_failure]);
//            }
//        }
//    }

    public function createAdsOrder(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));
            $userid = intval($request->get('userid', 0));
            if (!$token || $userid <= 0) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('id', $userid)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
            }

            if (!$user->remember_token_android || $user->remember_token_android != $token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
            }

            $ads_sn = $this->findAdsOrder($user);

            if ($ads_sn == self::$AdsOrder1) {
                Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$create_ads_waiting]);
            } elseif ($ads_sn == self::$AdsOrder2) {
                Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$create_ads_failure]);
            } elseif ($ads_sn == self::$AdsOrder3) {
                Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$create_ads_daymax]);
            } elseif ($ads_sn == self::$AdsOrder4) {
                Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$ads_reward_closed]);
            } else {
                return Response::json(['status' => self::$success_code, 'data' => $ads_sn, 'message' => self::$none_code]);
            }
        }
    }

    public function checkAdsOrder(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));
            $userid = intval($request->get('userid', 0));
            if (!$token || $userid <= 0) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$param_error]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('id', $userid)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$id_user_not_exist]);
            }

            if (!$user->remember_token_android || $user->remember_token_android != $token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$token_exception]);
            }

            $ads_sn = $request->get('ads_sn');
            if (self::$systemConfig['ads_add_traffic'] && $ads_sn) {

                $goods = Goods::query()->where('type', 3)->where('status', 1)->first();
                if ($goods) {

                    // 判断是否存在未看的Ads
                    $existsOrder = Order::query()->where('user_id', $user->id)->where('order_sn', $ads_sn)->where('status', 0)->first();
                    if (!$existsOrder) {
                        return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$payment_not_exist]);
                    }

                    DB::beginTransaction();
                    try {
                        $traffic = $existsOrder->traffic * 1048576;

                        // 把商品的流量加到账号上
                        User::query()->where('id', $user->id)->increment('transfer_enable', $traffic);

                        // 剩余有效期小于商品有效期时更新有效期
                        $lastCanUseDays = floor(round(strtotime($user->expire_time) - strtotime(date('Y-m-d H:i:s'))) / 3600 / 24);
                        if ($lastCanUseDays < $goods->days) {
                            User::query()->where('id', $user->id)->update(['expire_time' => date('Y-m-d H:i:s', strtotime("+" . $goods->days . " days")), 'enable' => 1]);
                        }

                        $hasPort = 1;
                        // 节点列表
                        $ssrList = [];
                        $portUser = User::query()->where('id', $user->id)->where('enable', 1)->where('port', 0)->whereRaw("u + d < transfer_enable")->get();
                        if (!$portUser) {
                            $port = self::$systemConfig['is_rand_port'] ? Helpers::getRandPort() : Helpers::getOnlyPort();
                            if ($port == 0)
                            {
                                $hasPort = 0;
                            }
                            else {
                                User::query()->where('id', $user->id)->update(['port' => $port]);
                            }
                            $userLabelIds = UserLabel::query()->where('user_id', $user->id)->pluck('label_id');
                            if (!empty($userLabelIds)) {
                                $nodeList = DB::table('ss_node')
                                    ->leftJoin('ss_node_label', 'ss_node.id', '=', 'ss_node_label.node_id')
                                    ->whereIn('ss_node_label.label_id', $userLabelIds)
                                    ->where('ss_node.status', 1)
                                    ->groupBy('ss_node.id')
                                    ->get();

                                foreach ($nodeList as &$node) {
                                    $ssrList[] = [
                                        'group_id' => $node->group_id,
                                        'id' => $node->id,
                                        'name' => $node->name,
                                        'country_code' => $node->country_code,
                                        'status' => $node->compatible ? 2 : 1
                                    ];
                                }
                            }
                        }

                        $existsOrder->status = 2;
                        $existsOrder->expire_at = date("Y-m-d H:i:s", strtotime("+" . $goods->days . " days"));
                        $existsOrder->save();

                        // 写入用户标签
                        if ($goods->label) {
                            // 取出现有的标签
                            $userLabels = UserLabel::query()->where('user_id', $user->id)->pluck('label_id')->toArray();
                            $goodsLabels = GoodsLabel::query()->where('goods_id', $existsOrder->goods_id)->pluck('label_id')->toArray();
                            $newUserLabels = array_merge($userLabels, $goodsLabels);

                            // 删除用户所有标签
                            UserLabel::query()->where('user_id', $user->id)->delete();

                            // 生成标签
                            foreach ($newUserLabels as $vo) {
                                $obj = new UserLabel();
                                $obj->user_id = $user->id;
                                $obj->label_id = $vo;
                                $obj->save();
                            }
                        }

                        $user = User::query()->where('id', $user->id)->where('status', '>=', 0)->first();

                        if (!empty($ssrList)) {
                            $result = [
                                'status'              => $user->status,
                                'expire_time'         => $user->expire_time < date('Y-m-d H:i:s') ? ' ' : $user->expire_time,
                                'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
                                'reward_transfer'     => flowAutoShow($traffic),
                                'has_port'            => $hasPort,
                                'hours'               => $goods->days * 24,
                                'ssr_list'            => json_encode($ssrList)
                            ];
                        } else
                        {
                            $result = [
                                'status'              => $user->status,
                                'expire_time'         => $user->expire_time < date('Y-m-d H:i:s') ? ' ' : $user->expire_time,
                                'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
                                'reward_transfer'     => flowAutoShow($traffic),
                                'hours'               => $goods->days * 24,
                                'has_port'            => $hasPort
                            ];
                        }

                        $data = json_encode($result);
                        DB::commit();

                        // 广告最小时间间隔
                        $aat = self::$systemConfig['ads_add_traffic_range'] ? self::$systemConfig['ads_add_traffic_range'] : 10;
                        Cache::put('adsAddTraffic_' . md5($user['id']), '1', $aat);

                        return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$none_code]);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$check_ads_failure]);
                    }
                } else {
                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$ads_reward_closed]);
                }
            } else {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$ads_reward_closed]);
            }
        }
    }
}
