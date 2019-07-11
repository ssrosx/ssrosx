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
use App\Http\Models\SsNodeOnlineLog;
use App\Mail\activeUser;
use Illuminate\Http\Request;
use Response;
use Cache;
use Mail;
use DB;
use Log;

/**
 * 快速接口
 * Class QuickMacController
 *
 * @package App\Http\Controllers
 */
class QuickMacController extends Controller
{
    private static $success_code = 1;//成功
    private static $faulure_code = 0;//失败

    private static $none_code               = 10000;//无
//    private static $create_order_failure    = 10001;//创建支付单失败
//    private static $payment_not_exist       = 10002;//支付信息不存在
//    private static $ads_reward_closed       = 10003;//Ads未开启
//    private static $create_ads_waiting      = 10004;//Ads等待期
//    private static $create_ads_failure      = 10005;//Ads创建失败
//    private static $create_ads_daymax       = 10006;//Ads今日达到上限
//    private static $check_ads_failure       = 10007;//Ads校验失败
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
//    private static $bundleid_error          = 10018;//bundleid错误
//    private static $goods_id_error          = 10019;//商品id错误
//    private static $product_id_error        = 10020;//产品id错误
//    private static $in_app_error            = 10021;//内购错误
//    private static $traffic_added_error     = 10022;//流量已经加过了


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

//    private static $AdsOrder1               = '1';//Ads等待期
//    private static $AdsOrder2               = '2';//Ads创建失败
//    private static $AdsOrder3               = '3';//Ads今日达到上限
    private static $AdsOrder4               = '4';//Ads未开启

//    private static $BundleList=["com.softmt.patatas"];

    protected static $systemConfig;

    function __construct()
    {
        self::$systemConfig = Helpers::systemConfig();
    }

    private function findAdsOrder(User $user)
    {
        return self::$AdsOrder4;//Ads未开启
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
                $remember_token_mac = makeRandStr(20);
                User::query()->where('id', $user->id)->update(['ssruuid' => $ssruuid, 'remember_token_mac' => $remember_token_mac, 'last_login' => time()]);

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

                        $last_log_time = time() - 600; // 10分钟内
                        $online_log = SsNodeOnlineLog::query()->where('node_id', $node->id)->where('log_time', '>=', $last_log_time)->orderBy('id', 'desc')->first();
                        $online_users = empty($online_log) ? 0 : $online_log->online_user;

                        $online_level = 0;
                        if ($online_users > 100)
                        {
                            $online_level = 2;
                        }
                        elseif ($online_users > 50)
                        {
                            $online_level = 1;
                        }

                        $ssrList[] = [
                            'group_id' => $node->group_id,
                            'id' => $node->id,
                            'name' => $node->name,
                            'country_code' => $node->country_code,
                            'status' => $node->compatible ? 2 : 1,
                            'level' => $online_level
                        ];
                    }
                }

                $ads_sn = $this->findAdsOrder($user);

                // 基本信息
                $baseData = [
                    'id'                  => $user->id,
                    'level'               => $user->level,
                    'account_status'      => 1,
                    'token'               => $remember_token_mac,
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
                $remember_token_mac = makeRandStr(20);
                User::query()->where('id', $user->id)->update(['remember_token_mac' => $remember_token_mac, 'last_login' => time()]);

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
                        $last_log_time = time() - 600; // 10分钟内
                        $online_log = SsNodeOnlineLog::query()->where('node_id', $node->id)->where('log_time', '>=', $last_log_time)->orderBy('id', 'desc')->first();
                        $online_users = empty($online_log) ? 0 : $online_log->online_user;

                        $online_level = 0;
                        if ($online_users > 100)
                        {
                            $online_level = 2;
                        }
                        elseif ($online_users > 50)
                        {
                            $online_level = 1;
                        }
                        $ssrList[] = [
                            'group_id' => $node->group_id,
                            'id' => $node->id,
                            'name' => $node->name,
                            'country_code' => $node->country_code,
                            'status' => $node->compatible ? 2 : 1,
                            'level' => $online_level
                        ];
                    }
                }

                $ads_sn = $this->findAdsOrder($user);

//                return Response::json(['status' => self::$faulure_code, 'data' => $ads_sn, 'message' => 111]);
                // 0 : 开关账号密码设置
                // 1 : 有账号和密码----------------打开重置密码功能
                // 2 : 有账号无密码，账号并未经激活--打开邮箱激活功能
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
                    'token'               => $remember_token_mac,
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
            $user->remember_token_mac = makeRandStr(20);
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
                    $last_log_time = time() - 600; // 10分钟内
                    $online_log = SsNodeOnlineLog::query()->where('node_id', $node->id)->where('log_time', '>=', $last_log_time)->orderBy('id', 'desc')->first();
                    $online_users = empty($online_log) ? 0 : $online_log->online_user;

                    $online_level = 0;
                    if ($online_users > 100)
                    {
                        $online_level = 2;
                    }
                    elseif ($online_users > 50)
                    {
                        $online_level = 1;
                    }
                    $ssrList[] = [
                        'group_id' => $node->group_id,
                        'id' => $node->id,
                        'name' => $node->name,
                        'country_code' => $node->country_code,
                        'status' => $node->compatible ? 2 : 1,
                        'level' => $online_level
                    ];
                }
            }

            $ads_sn = $this->findAdsOrder($user);

            // 基本信息
            $baseData = [
                'id'                  => $user->id,
                'level'               => $user->level,
                'account_status'      => 0,
                'token'               => $user->remember_token_mac,
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

            if (!$user->remember_token_mac || $user->remember_token_mac != $token) {
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

                if (!$user->remember_token_mac || $user->remember_token_mac != $token) {
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

            if (!$user->remember_token_mac || $user->remember_token_mac != $token) {
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

            if (!$user->remember_token_mac || $user->remember_token_mac != $token) {
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

            if (!$user->remember_token_mac || $user->remember_token_mac != $token) {
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
                    $last_log_time = time() - 600; // 10分钟内
                    $online_log = SsNodeOnlineLog::query()->where('node_id', $node->id)->where('log_time', '>=', $last_log_time)->orderBy('id', 'desc')->first();
                    $online_users = empty($online_log) ? 0 : $online_log->online_user;

                    $online_level = 0;
                    if ($online_users > 100)
                    {
                        $online_level = 2;
                    }
                    elseif ($online_users > 50)
                    {
                        $online_level = 1;
                    }
                    $ssrList[] = [
                        'group_id' => $node->group_id,
                        'id' => $node->id,
                        'name' => $node->name,
                        'country_code' => $node->country_code,
                        'status' => $node->compatible ? 2 : 1,
                        'level' => $online_level
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
            // 2 : 有账号无密码，账号并未经激活--打开邮箱激活功能
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

            if (!$user->remember_token_mac || $user->remember_token_mac != $token) {
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

            if (!$user->remember_token_mac || $user->remember_token_mac != $token) {
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
                $last_log_time = time() - 600; // 10分钟内
                $online_log = SsNodeOnlineLog::query()->where('node_id', $node->id)->where('log_time', '>=', $last_log_time)->orderBy('id', 'desc')->first();
                $online_users = empty($online_log) ? 0 : $online_log->online_user;

                $online_level = 0;
                if ($online_users > 100)
                {
                    $online_level = 2;
                }
                elseif ($online_users > 50)
                {
                    $online_level = 1;
                }
                $ssrList[] = [
                    'group_id'     => $node->group_id,
                    'id'           => $node->id,
                    'name'         => $node->name,
                    'country_code' => $node->country_code,
                    'status'       => $node->compatible ? 2 : 1,
                    'has_port'     => $user->port != 0 ? 1 : 0,
                    'level' => $online_level
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

            if (!$user->remember_token_mac || $user->remember_token_mac != $token) {
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

//    public function getGoodsList(Request $request)
//    {
//
//    }
//
//    // 验证收据
//    public function verifyReceipt(Request $request)
//    {
//
//    }
//
//    public function createAdsOrder(Request $request)
//    {
//
//    }
//
//    public function checkAdsOrder(Request $request)
//    {
//
//    }
}
