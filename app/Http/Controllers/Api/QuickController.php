<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Models\User;
use App\Http\Models\Goods;
use App\Http\Models\Order;
use App\Components\Yzy;
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

/**
 * 快速接口
 * Class QuickController
 *
 * @package App\Http\Controllers
 */
class QuickController extends Controller
{
    protected static $config;

    private static $success_code = 1;
    private static $faulure_code = 0;

    private static $none_code       = 10000;
    private static $regist_code     = 10001;
    private static $login_code      = 10002;
    private static $traffic_code    = 10003;
    private static $node_list_code  = 10004;
    private static $node_empty_code = 10005;
    private static $node_info_code  = 10006;
    private static $goods_list_code    = 10007;
    private static $goods_removed_code = 10008;//创建支付单失败：商品或服务已下架
    private static $order_exist_code   = 10009;//'创建支付单失败：尚有未支付的订单，请先去支付'
    private static $create_order_failure_code   = 10010;//'创建支付单失败'
    private static $create_order_success_code   = 10011;//'创建支付单成功'
    private static $payment_not_exist_code      = 10012;//支付信息不存在
    private static $check_order_failure_code    = 10013;//'校验订单失败'
    private static $check_order_success_code    = 10014;//'校验订单成功'
    private static $create_ads_failure_code     = 10015;//'创建ADS失败'
    private static $create_ads_success_code     = 10016;//'创建ADS成功'

    function __construct()
    {
        self::$config = $this->systemConfig();
    }


    private function findAdsOrder(User $user)
    {
        if (self::$config['ads_add_traffic']) {
            //有未看的Ads，直接返回该Ads
            if (Cache::has('adsAddTraffic_' . md5($user['id']))) {
                return '1';//等待期
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
                if ($count < self::$config['ads_daily_count'])
                {
                    $traffic = mt_rand(self::$config['min_rand_traffic'], self::$config['max_rand_traffic']);
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

                        // 广告最小时间间隔
                        $aat = self::$config['ads_add_traffic_range'] ? self::$config['ads_add_traffic_range'] : 10;
                        Cache::put('adsAddTraffic_' . md5($user['id']), '1', $aat);

                        return $orderSn;
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return '2';//创建失败
                    }
                }
                else {
                    return '3';//Ads今日达到上限
                }
            }
            else
            {
                return '4';//Ads未开启
            }
        }
        else
        {
            return '4';//Ads未开启
        }
    }

    // 账号登录
    public function login(Request $request)
    {

        if ($request->method() == 'POST')
        {
            $username = trim($request->get('username'));
            $password = trim($request->get('password'));
            $ssruuid = trim($request->get('ssruuid'));

            if (!$username || !$password || !$ssruuid) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验用户名是否已存在
            $exists = User::query()->where('username', $username)->first();
            if ($exists) {
                $user = User::query()->where('username', $username)->where('password', md5($password))->where('status', '>=', 0)->first();
                if (!$user) {
                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
                }

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
                            'status' => $node->status,
                        ];
                    }
                }

                $ads_sn = $this->findAdsOrder($user);

                if (strlen($ads_sn) == 1) {
                    $ads_sn = '';
                }

                // 基本信息
                $baseData = [
                    'id'                  => $user->id,
                    'level'               => $user->level,
                    'token'               => $user->remember_token,
                    'status'              => $user->status,
                    'expire_time'         => $user->expire_time,
                    'ads_sn'              => $ads_sn,
                    'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
                    'ssr_list'            => json_encode($ssrList)
                ];

                $data = json_encode($baseData);

                return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$login_code]);
            }
            else
            {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
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
                Cache::increment($cacheKey);
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验ssruuid是否已存在
            $exists = User::query()->where('ssruuid', $ssruuid)->first();
            if ($exists) {
                $user = User::query()->where('ssruuid', $ssruuid)->where('status', '>=', 0)->first();
                if (!$user) {
                    Cache::increment($cacheKey);

                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
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
                            'status' => $node->status,
                        ];
                    }
                }

                $ads_sn = $this->findAdsOrder($user);

                if (strlen($ads_sn) == 1) {
                    $ads_sn = '';
                }

                // 基本信息
                $baseData = [
                    'id'                  => $user->id,
                    'level'               => $user->level,
                    'token'               => $user->remember_token,
                    'status'              => $user->status,
                    'expire_time'         => $user->expire_time,
                    'ads_sn'              => $ads_sn,
                    'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
                    'ssr_list'            => json_encode($ssrList)
                ];

                $data = json_encode($baseData);

                return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$login_code]);
            }

            // 24小时内同IP注册限制
            if (self::$config['register_ip_limit']) {
                if (Cache::has($cacheKey)) {
                    $registerTimes = Cache::get($cacheKey);
                    if ($registerTimes >= self::$config['register_ip_limit']) {
                        return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
                    }
                }
            }

            // 最后一个可用端口
            $last_user = User::query()->orderBy('id', 'desc')->first();
            $port = self::$config['is_rand_port'] ? $this->getRandPort() : $last_user->port + 1;

            // 默认加密方式、协议、混淆
            $method = SsConfig::query()->where('type', 1)->where('is_default', 1)->first();
            $protocol = SsConfig::query()->where('type', 2)->where('is_default', 1)->first();
            $obfs = SsConfig::query()->where('type', 3)->where('is_default', 1)->first();

            // 创建新用户
            $transfer_enable = self::$config['default_traffic'] * 1048576;
            $user = new User();
            $user->ssruuid = $ssruuid;
            $user->port = $port;
            $user->passwd = makeRandStr();
            $user->transfer_enable = $transfer_enable;
            $user->method = $method ? $method->name : 'aes-192-ctr';
            $user->protocol = $protocol ? $protocol->name : 'auth_chain_a';
            $user->obfs = $obfs ? $obfs->name : 'tls1.2_ticket_auth';
            $user->enable_time = date('Y-m-d H:i:s');
            $user->expire_time = date('Y-m-d H:i:s', strtotime("+" . self::$config['default_days'] . " days"));
            $user->reg_ip = $request->getClientIp();
            $user->referral_uid = 0;
            $user->last_login = time();
            $user->remember_token = makeRandStr(20);
            $user->save();

            if ($user->id) {
                // 注册次数+1
                if (Cache::has($cacheKey)) {
                    Cache::increment($cacheKey);
                } else {
                    Cache::put($cacheKey, 1, 1440); // 24小时
                }

                // 初始化默认标签
                if (strlen(self::$config['initial_labels_for_user'])) {
                    $labels = explode(',', self::$config['initial_labels_for_user']);
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
                Cache::increment($cacheKey);

                return Response::json(['status' => self::$faulure_code, 'data' => [], 'message' => self::$none_code]);
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
                        'status' => $node->status,
                    ];
                }
            }

            $ads_sn = $this->findAdsOrder($user);

            if (strlen($ads_sn) == 1) {
                $ads_sn = '';
            }

            // 基本信息
            $baseData = [
                'id'                  => $user->id,
                'level'               => $user->level,
                'token'               => $user->remember_token,
                'status'              => $user->status,
                'expire_time'         => $user->expire_time,
                'ads_sn'              => $ads_sn,
                'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
                'ssr_list'            => json_encode($ssrList)
            ];

            $data = json_encode($baseData);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$regist_code]);
        }
    }

    // 设置账号
    public function setAccount(Request $request)
    {
        if ($request->method() == 'POST') {
            $username = trim($request->get('username'));
            $token = trim($request->get('token'));
            if (!$token || !$username) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('remember_token', $token)->where('status', 0)->first();
            if (!$user || $user->username) {
                return Response::json(['status' => self::$faulure_code, 'data' => $user->username, 'message' => self::$none_code]);
            }

            // 更新信息
            User::query()->where('id', $user->id)->update(['username' => $username, 'last_login' => time()]);

            // 发送邮件
            if (self::$config['is_active_register']) {
                // 生成激活账号的地址
                $token = md5(self::$config['website_name'] . $username . microtime());
                $verify = new Verify();
                $verify->user_id = $user->id;
                $verify->username = $username;
                $verify->token = $token;
                $verify->status = 0;
                $verify->save();

                $activeUserUrl = self::$config['website_url'] . '/active/' . $token;
                $title = '注册激活';
                $content = '请求地址：' . $activeUserUrl;

                try {
                    Mail::to($username)->send(new activeUser(self::$config['website_name'], $activeUserUrl));
                    $this->sendEmailLog($user->id, $title, $content);
                    return Response::json(['status' => self::$success_code, 'data' => '激活邮件已发送，请查看邮箱', 'message' => self::$none_code]);
                } catch (\Exception $e) {
                    $this->sendEmailLog($user->id, $title, $content, 0, $e->getMessage());

                    return Response::json(['status' => self::$faulure_code, 'data' => $e->getMessage(), 'message' => self::$none_code]);
                }
            }
            return Response::json(['status' => self::$success_code, 'data' => '账号设置成功', 'message' => self::$none_code]);
        }
    }


    // 设置密码
    public function setPassword(Request $request)
    {
        if ($request->method() == 'POST') {
            $password = trim($request->get('password'));
            $token = trim($request->get('token'));
            if (!$token || !$password) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('remember_token', $token)->where('status', 1)->first();
            if (!$user || !$user->username || $user->password) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 更新信息
            User::query()->where('id', $user->id)->update(['password' => md5($password), 'last_login' => time()]);

            return Response::json(['status' => self::$success_code, 'data' => '密码设置成功', 'message' => self::$none_code]);
        }
    }

    // 获取数据
    public function getData(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));
            if (!$token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('remember_token', $token)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
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
                        'status' => $node->status,
                    ];
                }
            }

            $ads_sn = $this->findAdsOrder($user);

            if (strlen($ads_sn) == 1) {
                $ads_sn = '';
            }

            // 基本信息
            $baseData = [
                'id' => $user->id,
                'level' => $user->level,
                'status' => $user->status,
                'expire_time' => $user->expire_time,
                'ads_sn' => $ads_sn,
                'unused_transfer' => $user->transfer_enable - $user->u - $user->d,
                'ssr_list' => json_encode($ssrList)
            ];

            $data = json_encode($baseData);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$login_code]);
        }
    }

    // 获取流量信息
    public function getTraffic(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));
            if (!$token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('remember_token', $token)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            $traffic = [
                'status'              => $user->status,
                'expire_time'         => $user->expire_time,
                'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
             ];

            $data = json_encode($traffic);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$traffic_code]);
        }
    }

    //获取节点列表
    public function getNodeList(Request $request)
    {

        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));
            if (!$token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('remember_token', $token)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            $ssrList = [];
            // 节点列表
            $userLabelIds = UserLabel::query()->where('user_id', $user['id'])->pluck('label_id');
            if (empty($userLabelIds)) {
                $data = json_encode($ssrList);
                return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$node_empty_code]);
            }

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
                    'status' => $node->status,
                ];
            }

            $data = json_encode($ssrList);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$node_list_code]);
        }
    }

    //获取节点信息
    public function getNodeInfo(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));
            $nodeid = trim($request->get('nodeid'));

            if (!$token || !$nodeid) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('remember_token', $token)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            $ssrList = [];
            // 节点列表
            $userLabelIds = UserLabel::query()->where('user_id', $user['id'])->pluck('label_id');
            if (empty($userLabelIds)) {
                $data = json_encode($ssrList);
                return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$node_empty_code]);
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

                    // 节点在线状态
//                    $nodeInfo = SsNodeInfo::query()->where('node_id', $node->node_id)->where('log_time', '>=', strtotime("-10 minutes"))->orderBy('id', 'desc')->first();
//                    $node->online_status = empty($nodeInfo) || empty($nodeInfo->load) ? 0 : 1;

                    $ssrList = [
                        'group_id' => $node->group_id,
                        'id' => $node->id,
                        'name' => $node->name,
                        'country_code' => $node->country_code,
                        'status' => $node->status,
                        'ssr' => $ssr_scheme,
//                        'online_status' => $node->online_status,
                    ];
                }

            }

            $data = json_encode($ssrList);

//            usleep(5000000);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$node_info_code]);
        }
    }


    public function getGoodsList(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));

            if (!$token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('remember_token', $token)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }
            $goodsList = [];
            $productList = Goods::query()->where('is_del', 0)->where("status", 1)->where("type", '!=', 3)->orderBy('id', 'desc')->paginate(30);
            foreach ($productList as $product) {
                $goodsList [] = [
                    'id'      => $product->id,
                    'name'    => $product->name,
                    'desc'    => $product->desc,
                    'price'   => $product->price,
                    'status'  => $product->status,
                    'traffic' => $product->traffic * 1048576,
                    'days'    => $product->days,
                    'sku'     => $product->sku,
                    'type'    => $product->type,
                ];
            }

            $data = json_encode($goodsList);

            return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$goods_list_code]);
        }
    }

    // 创建支付单
    public function createOrder(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));

            if (!$token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('remember_token', $token)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            $goods_id = intval($request->get('goods_id'));

            $goods = Goods::query()->where('id', $goods_id)->where('status', 1)->first();
            if (!$goods) {
                return Response::json(['status' => self::$faulure_code, 'data' => 'goods removed', 'message' => self::$goods_removed_code]);
            }

            // 判断是否存在同个商品的未支付订单
            $existsOrder = Order::query()->where('goods_id', $goods_id)->where('status', 0)->where('user_id', $user['id'])->first();
            if ($existsOrder) {
                //有未支付的订单，直接返回该订单信息
                return Response::json(['status' => self::$success_code, 'data' => $existsOrder->order_sn, 'message' => self::$order_exist_code]);
            }

            $amount = $goods->price;
            DB::beginTransaction();
            try {
                $orderSn = date('ymdHis') . mt_rand(100000, 999999);
                $sn = makeRandStr(12);

                // 生成订单
                $order = new Order();
                $order->order_sn = $orderSn;
                $order->user_id = $user['id'];
                $order->goods_id = $goods_id;
                $order->coupon_id = !empty($coupon) ? $coupon->id : 0;
                $order->origin_amount = $goods->price;
                $order->amount = $amount;
                $order->expire_at = date("Y-m-d H:i:s", strtotime("+" . $goods->days . " days"));
                $order->is_expire = 0;
                $order->pay_way = 3;
                $order->status = 0;
                $order->save();

                // 生成支付单
                $yzy = new Yzy();
                $result = $yzy->createQrCode($goods->name, $amount * 100, $orderSn);
                if (isset($result['error_response'])) {
                    Log::error('【有赞云】创建二维码失败：' . $result['error_response']['msg']);

                    throw new \Exception($result['error_response']['msg']);
                }

                $payment = new Payment();
                $payment->sn = $sn;
                $payment->user_id = $user['id'];
                $payment->oid = $order->oid;
                $payment->order_sn = $orderSn;
                $payment->pay_way = 1;
                $payment->amount = $amount;
                $payment->qr_id = $result['response']['qr_id'];
                $payment->qr_url = $result['response']['qr_url'];
                $payment->qr_code = $result['response']['qr_code'];
                $payment->qr_local_url = $this->base64ImageSaver($result['response']['qr_code']);
                $payment->status = 0;
                $payment->save();

                DB::commit();

                return Response::json(['status' => self::$success_code, 'data' => $orderSn, 'message' => self::$create_order_success_code]);
            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('创建支付订单失败：' . $e->getMessage());

                return Response::json(['status' => self::$faulure_code, 'data' => '创建支付订单失败：' . $e->getMessage(), 'message' => self::$create_order_failure_code]);
            }
        }
    }

    public function checkOrder(Request $request)
    {

        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));

            if (!$token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('remember_token', $token)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            $order_sn = $request->get('order_sn');
            $payment = Payment::query()->where('order_sn', $order_sn)->first();
            if (!$payment) {
                return Response::json(['status' => self::$faulure_code, 'data' => 'order not exist', 'message' => self::$payment_not_exist_code]);
            }

            //缺少去苹果服务器校验步骤

            // 处理订单
            DB::beginTransaction();
            try {
                // 更新支付单
                $payment->status = 1;
                $payment->save();

                // 更新订单
                $order = Order::query()->with(['user'])->where('oid', $payment->oid)->first();
                $order->status = 2;
                $order->save();

                // 如果买的是套餐，则先将之前购买的所有套餐置都无效，并扣掉之前所有套餐的流量
                $goods = Goods::query()->where('id', $order->goods_id)->first();
                if ($goods->type == 2) {
                    $existOrderList = Order::query()
                        ->with(['goods'])
                        ->whereHas('goods', function ($q) {
                            $q->where('type', 2);
                        })
                        ->where('user_id', $order->user_id)
                        ->where('oid', '<>', $order->oid)
                        ->where('is_expire', 0)
                        ->get();

                    foreach ($existOrderList as $vo) {
                        Order::query()->where('oid', $vo->oid)->update(['is_expire' => 1]);
                        User::query()->where('id', $order->user_id)->decrement('transfer_enable', $vo->goods->traffic * 1048576);
                    }
                }

                // 把商品的流量加到账号上
                User::query()->where('id', $order->user_id)->increment('transfer_enable', $goods->traffic * 1048576);

                // 套餐就改流量重置日，流量包不改
                if ($goods->type == 2) {
                    // 将商品的有效期和流量自动重置日期加到账号上
                    $traffic_reset_day = in_array(date('d'), [29, 30, 31]) ? 28 : abs(date('d'));
                    User::query()->where('id', $order->user_id)->update(['traffic_reset_day' => $traffic_reset_day, 'expire_time' => date('Y-m-d', strtotime("+" . $goods->days . " days", strtotime($order->user->expire_time))), 'enable' => 1]);
                } else {
                    // 将商品的有效期和流量自动重置日期加到账号上
                    User::query()->where('id', $order->user_id)->update(['expire_time' => date('Y-m-d', strtotime("+" . $goods->days . " days")), 'enable' => 1]);
                }

                // 写入用户标签
                if ($goods->label) {
                    // 取出现有的标签
                    $userLabels = UserLabel::query()->where('user_id', $order->user_id)->pluck('label_id')->toArray();
                    $goodsLabels = GoodsLabel::query()->where('goods_id', $order->goods_id)->pluck('label_id')->toArray();
                    $newUserLabels = array_merge($userLabels, $goodsLabels);

                    // 删除用户所有标签
                    UserLabel::query()->where('user_id', $order->user_id)->delete();

                    // 生成标签
                    foreach ($newUserLabels as $vo) {
                        $obj = new UserLabel();
                        $obj->user_id = $order->user_id;
                        $obj->label_id = $vo;
                        $obj->save();
                    }
                }
                DB::commit();


                $user = User::query()->where('remember_token', $token)->where('status', '>=', 0)->first();
                if (!$user) {
                    return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
                }

                $traffic = [
                    'status'              => $user->status,
                    'expire_time'         => $user->expire_time,
                    'unused_transfer'     => $user->transfer_enable - $user->u - $user->d,
                ];

                $data = json_encode($traffic);

                return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$check_order_success_code]);
            } catch (\Exception $e) {
                DB::rollBack();

                return Response::json(['status' => self::$faulure_code, 'data' => '校验订单失败：' . $e->getMessage(), 'message' => self::$check_order_failure_code]);
            }
        }
    }

    public function createAdsOrder(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));

            if (!$token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('remember_token', $token)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            $ads_sn = $this->findAdsOrder($user);

            if ($ads_sn == '1') {
                Response::json(['status' => self::$faulure_code, 'data' => 'Ads等待期', 'message' => self::$create_ads_failure_code]);
            } elseif ($ads_sn == '2') {
                Response::json(['status' => self::$faulure_code, 'data' => 'Ads创建失败', 'message' => self::$create_ads_failure_code]);
            } elseif ($ads_sn == '3') {
                Response::json(['status' => self::$faulure_code, 'data' => 'Ads今日达到上限', 'message' => self::$create_ads_failure_code]);
            } elseif ($ads_sn == '4') {
                Response::json(['status' => self::$faulure_code, 'data' => 'Ads未开启', 'message' => self::$create_ads_failure_code]);
            } else {
                return Response::json(['status' => self::$success_code, 'data' => $ads_sn, 'message' => self::$create_ads_success_code]);
            }
        }
    }

    public function checkAdsOrder(Request $request)
    {
        if ($request->method() == 'POST') {
            $token = trim($request->get('token'));

            if (!$token) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            // 校验用户名是否已存在
            $user = User::query()->where('remember_token', $token)->where('status', '>=', 0)->first();
            if (!$user) {
                return Response::json(['status' => self::$faulure_code, 'data' => '', 'message' => self::$none_code]);
            }

            $ads_sn = $request->get('ads_sn');
            if (self::$config['ads_add_traffic'] && $ads_sn) {

                $goods = Goods::query()->where('type', 3)->where('status', 1)->first();
                if ($goods) {

                    // 判断是否存在未看的Ads
                    $existsOrder = Order::query()->where('user_id', $user->id)->where('order_sn', $ads_sn)->where('status', 0)->first();
                    if (!$existsOrder) {
                        return Response::json(['status' => self::$faulure_code, 'data' => 'ads not exist', 'message' => self::$payment_not_exist_code]);
                    }

                    DB::beginTransaction();
                    try {
                        $traffic = $existsOrder->traffic * 1048576;

                        // 把商品的流量加到账号上
                        User::query()->where('id', $user->id)->increment('transfer_enable', $traffic);

                        // 更新账号过期时间、流量重置日
                        $lastCanUseDays = floor(round(strtotime($user->expire_time) - strtotime(date('Y-m-d H:i:s'))) / 3600 / 24);
                        if ($lastCanUseDays < $goods->days) {
                            User::query()->where('id', $user->id)->update(['expire_time' => date('Y-m-d', strtotime("+" . $goods->days . " days")), 'enable' => 1]);
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

                        $traffic = [
                            'status' => $user->status,
                            'expire_time' => $user->expire_time,
                            'unused_transfer' => $user->transfer_enable - $user->u - $user->d,
                            'reward_transfer' => flowAutoShow($traffic),
                        ];

                        $data = json_encode($traffic);
                        DB::commit();

                        // 广告最小时间间隔
                        $aat = self::$config['ads_add_traffic_range'] ? self::$config['ads_add_traffic_range'] : 10;
                        Cache::put('adsAddTraffic_' . md5($user['id']), '1', $aat);

                        return Response::json(['status' => self::$success_code, 'data' => $data, 'message' => self::$create_ads_success_code]);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return Response::json(['status' => self::$faulure_code, 'data' => '领取Ads失败：' . $e->getMessage(), 'message' => self::$create_ads_failure_code]);
                    }
                } else {
                    return Response::json(['status' => self::$faulure_code, 'data' => 'Ads1未开启', 'message' => self::$create_ads_failure_code]);
                }
            } else {
                return Response::json(['status' => self::$faulure_code, 'data' => 'Ads2未开启', 'message' => self::$create_ads_failure_code]);
            }
        }
    }
}