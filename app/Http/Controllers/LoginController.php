<?php

namespace App\Http\Controllers;

use App\Components\Helpers;
use App\Components\QQWry;
use App\Http\Models\User;
use App\Http\Models\UserLoginLog;
use Illuminate\Http\Request;
use Response;
use Redirect;
use Captcha;
use Session;
use Cache;
use Log;

/**
 * 登录控制器
 * Class LoginController
 *
 * @package App\Http\Controllers
 */
class LoginController extends Controller
{
    protected static $systemConfig;

    function __construct()
    {
        self::$systemConfig = Helpers::systemConfig();
    }

    // 登录页
    public function index(Request $request)
    {
        return $this->indexPublic($request, false);
    }

    private function indexPublic(Request $request, $lang)
    {
        if ($request->method() == 'POST') {
            $username = trim($request->get('username'));
            $password = trim($request->get('password'));
            $captcha = trim($request->get('captcha'));

            if (empty($username) || empty($password)) {
                Session::flash('errorMsg', 'home.input_account_password');

                return Redirect::back();
            }

            // 是否校验验证码
            if (self::$systemConfig['is_captcha']) {
                if (!Captcha::check($captcha)) {
                    Session::flash('errorMsg', 'home.verfy_error_reinput');

                    return Redirect::back()->withInput();
                }
            }

            $user = User::query()->where('username', $username)->where('password', md5($password))->first();
            if (!$user) {
                Session::flash('errorMsg', 'home.account_password_error');

                return Redirect::back()->withInput();
            } else if (!$user->is_admin && $user->status < 0) {
                Session::flash('errorMsg', 'home.account_can_not_use');

                return Redirect::back();
            } else if ($user->status == 0 && self::$systemConfig['is_active_register'] && $user->is_admin == 0) {
                Session::flash('errorMessage', '<a href="/activeUser?username=' . $user->username . '" target="_blank"><span style="color:#000">【Active】</span></a>');

                return Redirect::back()->withInput();
            }

            // 更新登录信息
            $remember_token = "";
            User::query()->where('id', $user->id)->update(['last_login' => time()]);
            if ($request->get('remember')) {
                $remember_token = makeRandStr(20);

                User::query()->where('id', $user->id)->update(['last_login' => time(), 'remember_token' => $remember_token]);
            } else {
                User::query()->where('id', $user->id)->update(['last_login' => time(), 'remember_token' => '']);
            }

            // 登录送积分
            if (self::$systemConfig['login_add_score']) {
                if (!Cache::has('loginAddScore_' . md5($username))) {
                    $score = mt_rand(self::$systemConfig['min_rand_score'], self::$systemConfig['max_rand_score']);
                    $ret = User::query()->where('id', $user->id)->increment('score', $score);
                    if ($ret) {
                        $this->addUserScoreLog($user->id, $user->score, $user->score + $score, $score, '登录送积分');

                        // 登录多久后再登录可以获取积分
                        $ttl = self::$systemConfig['login_add_score_range'] ? self::$systemConfig['login_add_score_range'] : 1440;
                        Cache::put('loginAddScore_' . md5($username), '1', $ttl);

                        Session::flash('successMsg', '欢迎回来，系统自动赠送您 ' . $score . ' 积分，您可以用它兑换流量包');
                    }
                }
            }

            // 写入登录日志
            $this->addUserLoginLog($user->id, getClientIp());

            // 重新取出用户信息
            $userInfo = User::query()->where('id', $user->id)->first();

            Session::put('user', $userInfo->toArray());

            // 根据权限跳转
            if ($user->is_admin) {
                return Redirect::to('admin')->cookie('remember', $remember_token, 36000);
            }

            return Redirect::to('/')->cookie('remember', $remember_token, 36000);
        } else {
            if ($request->cookie("remember")) {
                $u = User::query()->where('status', '>=', 0)->where("remember_token", $request->cookie("remember"))->first();
                if ($u) {
                    Session::put('user', $u->toArray());

                    if ($u->is_admin) {
                        return Redirect::to('admin');
                    }

                    return Redirect::to('/');
                }
            }

            $view['is_captcha'] = self::$systemConfig['is_captcha'];
            $view['is_register'] = self::$systemConfig['is_register'];
            $view['website_home_logo'] = self::$systemConfig['website_home_logo'];
            $view['website_analytics'] = self::$systemConfig['website_analytics'];
            $view['website_customer_service'] = self::$systemConfig['website_customer_service'];

            if ($lang == true)
            {
                Response::view('login', $view);
                return Redirect::back();
            }
            else
            {
            return Response::view('login', $view);
        }
    }
    }

    // 中文登录页
    public function indexCN(Request $request)
    {
        Session::put("locale", 'zh-CN');
        return $this->indexPublicWithLanguage($request, true);
    }

    // 英文登录页
    public function indexEN(Request $request)
    {
        Session::put("locale", 'en');
        return $this->indexPublicWithLanguage($request, true);
    }

    private function indexPublicWithLanguage(Request $request, $lang)
    {
        if ($request->method() == 'GET') {
            $token = trim($request->get('token'));
            $userid = intval($request->get('userid', 0));
            $page = trim($request->get('page', ''));
            if (!$token || $userid <= 0) {
                if ($request->cookie("remember")) {
                    $u = User::query()->where('status', '>=', 0)->where("remember_token", $request->cookie("remember"))->first();
                    if ($u) {
                        Session::put('user', $u->toArray());

                        if ($u->is_admin) {
                            return Redirect::to('admin');
                        }

                        return Redirect::to('/');
                    }
                }

                $view['is_captcha'] = self::$systemConfig['is_captcha'];
                $view['is_register'] = self::$systemConfig['is_register'];
                $view['website_home_logo'] = self::$systemConfig['website_home_logo'];
                $view['website_analytics'] = self::$systemConfig['website_analytics'];
                $view['website_customer_service'] = self::$systemConfig['website_customer_service'];

                if ($lang == true)
                {
                    Response::view('login', $view);
                    return Redirect::back();
                }
                else
                {
                    return Response::view('login', $view);
                }
            }

            // 校验用户名是否已存在
            $user = User::query()->where('id', $userid)->where('status', '>=', 0)->first();
            if (!$user) {
                return Redirect::back();
            }

            if (!$user->is_admin && $user->status < 0) {
                Session::flash('errorMsg', 'home.account_can_not_use');

                return Redirect::back();
            }

            if (!$user->remember_token_ios || $user->remember_token_ios != $token) {
                return Redirect::back();
            }

            // 更新登录信息
            $remember_token = "";
            User::query()->where('id', $user->id)->update(['last_login' => time()]);
            if ($request->get('remember')) {
                $remember_token = makeRandStr(20);

                User::query()->where('id', $user->id)->update(['last_login' => time(), 'remember_token' => $remember_token]);
            } else {
                User::query()->where('id', $user->id)->update(['last_login' => time(), 'remember_token' => $remember_token]);
            }

            // 重新取出用户信息
            $userInfo = User::query()->where('id', $user->id)->first();

            Session::put('user', $userInfo->toArray());
            Session::put('is_open_shop', self::$systemConfig['is_open_shop']);
            Session::put('is_open_ticket', self::$systemConfig['is_open_ticket']);
            // 根据权限跳转
            if ($user->is_admin) {
                return Redirect::to('admin')->cookie('remember', $remember_token, 36000);
            }

            return Redirect::to('/' . $page)->cookie('remember', $remember_token, 36000);
        }
        else
        {
            if ($request->cookie("remember")) {
                $u = User::query()->where('status', '>=', 0)->where("remember_token", $request->cookie("remember"))->first();
                if ($u) {
                    Session::put('user', $u->toArray());
                    Session::put('is_open_shop', self::$systemConfig['is_open_shop']);
                    Session::put('is_open_ticket', self::$systemConfig['is_open_ticket']);
                    if ($u->is_admin) {
                        return Redirect::to('admin');
                    }

                    return Redirect::to('/');
                }
            }

            $view['is_captcha'] = self::$systemConfig['is_captcha'];
            $view['is_register'] = self::$systemConfig['is_register'];
            $view['website_home_logo'] = self::$systemConfig['website_home_logo'];
            $view['website_analytics'] = self::$systemConfig['website_analytics'];
            $view['website_customer_service'] = self::$systemConfig['website_customer_service'];

            if ($lang == true)
            {
                Response::view('login', $view);
                return Redirect::back();
            }
            else
            {
                return Response::view('login', $view);
            }
        }
    }

    // 退出
    public function logout(Request $request)
    {
        Session::flush();

        return Redirect::to('login')->cookie('remember', "", 36000);
    }

    // 添加用户登录日志
    private function addUserLoginLog($userId, $ip)
    {
        // 解析IP信息
        $qqwry = new QQWry();
        $ipInfo = $qqwry->ip($ip);
        if (isset($ipInfo['error'])) {
            Log::info('无法识别IP，可能是IPv6，尝试解析：' . $ip);
            $ipInfo = getIPv6($ip);
        }

        if (empty($ipInfo) || empty($ipInfo['country'])) {
            \Log::warning("获取IP地址信息异常：" . $ip);
        }

        $log = new UserLoginLog();
        $log->user_id = $userId;
        $log->ip = $ip;
        $log->country = $ipInfo['country'] ?? '';
        $log->province = $ipInfo['province'] ?? '';
        $log->city = $ipInfo['city'] ?? '';
        $log->county = $ipInfo['county'] ?? '';
        $log->isp = $ipInfo['isp'] ?? ($ipInfo['organization'] ?? '');
        $log->area = $ipInfo['area'] ?? '';
        $log->save();
    }
}
