<?php

namespace App\Http\Controllers\Muv2;
use App\Http\Controllers\Controller;
use App\Http\Models\SsNode;
use App\Http\Models\SsNodeInfo;
use App\Http\Models\SsNodeLabel;
use App\Http\Models\SsNodeOnlineLog;
use App\Http\Models\User;
use App\Http\Models\UserLabel;
use App\Http\Models\UserTrafficLog;
use App\Http\V2ray\V2rayGenerator;
use App\Mail\resetPassword;
use Illuminate\Http\Request;
use Response;

class NodeController extends Controller
{
    protected static $config;
    protected static $userLevel;

    function __construct()
    {
        self::$config = $this->systemConfig();
    }
    //V2ray 用户
    public function users(Request $request){
        $node_id = $request->route('id');
        $ssr_node = SsNode::query()->where('id',$node_id)->first();//节点是否存在
        if($ssr_node == null){
            $res = [
                "ret" => 0
            ];
            return Response::json($res,400);
        }
        //找出该节点的标签id
        $ssr_node_label = SsNodeLabel::query()
            ->where('node_id',$node_id)->pluck('label_id');
        //找出有这个标签的用户
        $user_with_label = UserLabel::query()
            ->whereIn('label_id',$ssr_node_label)->pluck('user_id');
        //提取用户信息
        $userids = User::query()->whereIn('id',$user_with_label)
            ->where('enable',1)->where('id','<>',self::$config['free_node_users_id'])->pluck('id')->toArray();

        $users = User::query()->where('id','<>',self::$config['free_node_users_id'])
            ->select(
            "id","username","passwd","t","u","d","transfer_enable",
            "port","protocol","obfs","enable","expire_time as expire_time_d","method",
            "v2ray_uuid","v2ray_level","v2ray_alter_id")
            ->get();
        $data = [];
        foreach($users as $user){
            //datetime 转timestamp
            $user['switch']=1;
            $user['email']=$user['username'];
            $user['expire_time']=strval((new \DateTime($user['expire_time_d']))->getTimestamp());
            if(in_array($user->id,$userids)){
                $user->enable = 1;
            }
            else{
                $user->enable = 0;
            }
            //v2ray用户信息
            $user->v2ray_user = [
                "uuid" => $user->v2ray_uuid,
                "email" => sprintf("%s@sspanel.xyz", $user->v2ray_uuid),
                "alter_id" => $user->v2ray_alter_id,
                "level" => $user->v2ray_level,
                ];
            array_push($data, $user);
        }
        if(self::$config['is_free_node']){
            if(self::$config['free_node_id'] == $node_id){
                $user = User::query()->whereIn('id',$user_with_label)
                    ->where('id', self::$config['free_node_users_id'])
                    ->select(
                        "id","enable","username","passwd","t","u","d","transfer_enable",
                        "port","protocol","obfs","enable","expire_time as expire_time_d","method",
                        "v2ray_uuid","v2ray_level","v2ray_alter_id")
                    ->first();
                //datetime 转timestamp
                $user['switch']=1;
                $user['email']=$user['username'];
                $user['expire_time']=strval((new \DateTime($user['expire_time_d']))->getTimestamp());
                //v2ray用户信息
                $user->v2ray_user = [
                    "uuid" => $user->v2ray_uuid,
                    "email" => sprintf("%s@sspanel.xyz", $user->v2ray_uuid),
                    "alter_id" => $user->v2ray_alter_id,
                    "level" => $user->v2ray_level,
                ];
                array_push($data, $user);
            }
        }

        $load = '1';
        $uptime = time();

        $log = new SsNodeInfo();
        $log->node_id = $node_id;
        $log->load = $load;
        $log->uptime = $uptime;
        $log->log_time = time();
        $log->save();

        $res = [
            'msg' => 'ok',
            'data' => $data,
        ];
        return Response::json($res);
    }

    //写在线用户日志
    public function onlineUserLog(Request $request)
    {
        $node_id =$request->route('id');
        $count = $request->get('count');
        $log = new SsNodeOnlineLog();
        $log->node_id = $node_id;
        $log->online_user = $count;
        $log->log_time = time();
        if (!$log->save()) {
            $res = [
                "ret" => 0,
                "msg" => "update failed",
            ];
            return response()->json($res);
        }
        $res = [
            "ret" => 1,
            "msg" => "ok",
        ];
        return response()->json($res);
    }

    //节点信息
    public function info(Request $request)
    {
        $node_id = $request->route('id');
        $load = $request->get('load');
        $uptime = $request->get('uptime');

        $log = new SsNodeInfo();
        $log->node_id = $node_id;
        $log->load = $load;
        $log->uptime = $uptime;
        $log->log_time = time();
        if (!$log->save()) {
            $res = [
                "ret" => 0,
                "msg" => "update failed",
            ];
            return response()->json($res);
        }
        $res = [
            "ret" => 1,
            "msg" => "ok",
        ];
        return response()->json($res);
    }

    //PostTraffic
    public function postTraffic(Request $request){
        $nodeId = $request->route('id');
        $node = SsNode::query()->where('id',$nodeId)->first();
        $rate = $node->traffic_rate;
        $input = $request->getContent();
        $datas = json_decode($input, true);
        foreach ($datas as $data){
            $user = User::query()->where('id',$data['user_id'])->first();
            if(!$user){continue;}
            $user->t = time();
            $user->u = $user->u + ($data['u'] * $rate);
            $user->d = $user->d + ($data['d'] * $rate);
            $user->save();

            // 写usertrafficlog
            $totalTraffic = self::flowAutoShow(($data['u'] + $data['d']) * $rate);
            $traffic = new UserTrafficLog();
            $traffic->user_id = $data['user_id'];
            $traffic->u = $data['u'];
            $traffic->d = $data['d'];
            $traffic->node_id = $nodeId;
            $traffic->rate = $rate;
            $traffic->traffic = $totalTraffic;
            $traffic->log_time = time();
            $traffic->save();

        }
        $res = [
            'ret' => 1,
            "msg" => "ok",
        ];
        return response()->json($res);
    }

    //V2ray Users
    public function v2rayUsers(Request $request){
        $node = SsNode::query()->where('id',$request->route('id'))->first();
        $users = User::query()->where('enable',1)->where('id','<>',self::$config['free_node_users_id'])->get();
        $v = new V2rayGenerator();
        $v->setPort($node->v2ray_port);
        foreach ($users as $user){
            $email = sprintf("%s@sspanel.io", $user->v2ray_uuid);
            $v->addUser($user->v2ray_uuid, $user->v2ray_level, $user->v2ray_alter_id, $email);
        }
        if(self::$config['is_free_node']){
            if($request->route('id') == self::$config['free_node_id']){
                $freeuser = User::query()->where('enable',1)->where('id',self::$config['free_node_users_id'])->first();
                $email = sprintf("%s@sspanel.io", $freeuser->v2ray_uuid);
                $v->addUser($freeuser->v2ray_uuid, $freeuser->v2ray_level, $freeuser->v2ray_alter_id, $email);
            }
        }
        return Response::json($v->getArr());
    }

    /**
     * 根据流量值自动转换单位输出
     * @param int $value
     * @return string
     */
    public static function flowAutoShow($value = 0)
    {
        $kb = 1024;
        $mb = 1048576;
        $gb = 1073741824;
        $tb = $gb * 1024;
        $pb = $tb * 1024;
        if (abs($value) > $pb) {
            return round($value / $pb, 2) . "PB";
        } elseif (abs($value) > $tb) {
            return round($value / $tb, 2) . "TB";
        } elseif (abs($value) > $gb) {
            return round($value / $gb, 2) . "GB";
        } elseif (abs($value) > $mb) {
            return round($value / $mb, 2) . "MB";
        } elseif (abs($value) > $kb) {
            return round($value / $kb, 2) . "KB";
        } else {
            return round($value, 2) . "B";
        }
    }
}