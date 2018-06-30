<?php

namespace App\Http\Controllers;

use App\Http\Models\UserSubscribe;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Models\Config;
use App\Http\Models\EmailLog;
use App\Http\Models\Level;
use App\Http\Models\SsConfig;
use App\Http\Models\User;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    // 生成订阅地址的唯一码
    public function makeSubscribeCode()
    {
        $code = makeRandStr(5);
        if (UserSubscribe::query()->where('code', $code)->exists()) {
            $code = $this->makeSubscribeCode();
        }

        return $code;
    }

    // 加密方式
    public function methodList()
    {
        return SsConfig::query()->where('type', 1)->get();
    }

    // 默认加密方式
    public function getDefaultMethod()
    {
        $config = SsConfig::query()->where('type', 1)->where('is_default', 1)->first();

        return $config ? $config->name : 'aes-192-ctr';
    }

    // 协议
    public function protocolList()
    {
        return SsConfig::query()->where('type', 2)->get();
    }

    // 默认协议
    public function getDefaultProtocol()
    {
        $config = SsConfig::query()->where('type', 2)->where('is_default', 1)->first();

        return $config ? $config->name : 'origin';
    }

    // 混淆
    public function obfsList()
    {
        return SsConfig::query()->where('type', 3)->get();
    }

    // 默认混淆
    public function getDefaultObfs()
    {
        $config = SsConfig::query()->where('type', 3)->where('is_default', 1)->first();

        return $config ? $config->name : 'plain';
    }

    // 等级
    public function levelList()
    {
        return Level::query()->get()->sortBy('level');
    }

    // 系统配置
    public function systemConfig()
    {
        $config = Config::query()->get();
        $data = [];
        foreach ($config as $vo) {
            $data[$vo->name] = $vo->value;
        }

        return $data;
    }

    // 获取一个随机端口
    public function getRandPort()
    {
        $config = $this->systemConfig();

        $port = mt_rand($config['min_port'], $config['max_port']);
        $deny_port = [1068, 1109, 1434, 3127, 3128, 3129, 3130, 3332, 4444, 5554, 6669, 8080, 8081, 8082, 8181, 8282, 9996, 17185, 24554, 35601, 60177, 60179]; // 不生成的端口

        $exists_port = User::query()->pluck('port')->toArray();
        if (in_array($port, $exists_port) || in_array($port, $deny_port)) {
            $port = $this->getRandPort();
        }

        return $port;
    }

    // 获取一个端口
    public function getOnlyPort()
    {
        $config = $this->systemConfig();

        $port = $config['min_port'];
        $deny_port = [1068, 1109, 1434, 3127, 3128, 3129, 3130, 3332, 4444, 5554, 6669, 8080, 8081, 8082, 8181, 8282, 9996, 17185, 24554, 35601, 60177, 60179]; // 不生成的端口

        $exists_port = User::query()->where('port', '>=', $config['min_port'])->pluck('port')->toArray();
        while (in_array($port, $exists_port) || in_array($port, $deny_port)) {
            $port = $port + 1;
        }

        return $port;
    }

    // 类似Linux中的tail命令
    public function tail($file, $n, $base = 5)
    {
        $fileLines = $this->countLine($file);
        if ($fileLines < 15000) {
            return false;
        }

        $fp = fopen($file, "r+");
        assert($n > 0);
        $pos = $n + 1;
        $lines = [];
        while (count($lines) <= $n) {
            try {
                fseek($fp, -$pos, SEEK_END);
            } catch (\Exception $e) {
                fseek(0);
                break;
            }

            $pos *= $base;
            while (!feof($fp)) {
                array_unshift($lines, fgets($fp));
            }
        }

        return array_slice($lines, 0, $n);
    }

    /**
     * 计算文件行数
     */
    public function countLine($file)
    {
        $fp = fopen($file, "r");
        $i = 0;
        while (!feof($fp)) {
            //每次读取2M
            if ($data = fread($fp, 1024 * 1024 * 2)) {
                //计算读取到的行数
                $num = substr_count($data, "\n");
                $i += $num;
            }
        }

        fclose($fp);

        return $i;
    }

    /**
     * 写入邮件发送日志
     *
     * @param int    $user_id 用户ID
     * @param string $title   标题
     * @param string $content 内容
     * @param int    $status  投递状态
     * @param string $error   投递失败时记录的异常信息
     */
    public function sendEmailLog($user_id, $title, $content, $status = 1, $error = '')
    {
        $emailLogObj = new EmailLog();
        $emailLogObj->user_id = $user_id;
        $emailLogObj->title = $title;
        $emailLogObj->content = $content;
        $emailLogObj->status = $status;
        $emailLogObj->error = $error;
        $emailLogObj->created_at = date('Y-m-d H:i:s');
        $emailLogObj->save();
    }

    // 将Base64图片转换为本地图片并保存
    function base64ImageSaver($base64_image_content)
    {
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];

            $directory = date('Ymd');
            $path = '/assets/images/qrcode/' . $directory . '/';
            if (!file_exists(public_path($path))) { //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir(public_path($path), 0700, true);
            }

            $fileName = makeRandStr(18, true) . ".{$type}";
            if (file_put_contents(public_path($path . $fileName), base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return $path . $fileName;
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
}
