<?php

namespace App\Console\Commands;

use App\Components\Helpers;
use Illuminate\Console\Command;
use App\Http\Models\Order;
use App\Http\Models\User;
use App\Http\Models\UserLabel;
use App\Http\Models\GoodsLabel;
use Log;
use DB;

class AutoDecGoodsTraffic extends Command
{
    protected $signature = 'autoDecGoodsTraffic';
    protected $description = '自动扣减用户到期商品的流量';
    protected static $systemConfig;

    public function __construct()
    {
        parent::__construct();
        self::$systemConfig = Helpers::systemConfig();
    }

    public function handle()
    {
        $jobStartTime = microtime(true);

        // 扣减用户到期商品的流量
        $this->decGoodsTraffic();

        $jobEndTime = microtime(true);
        $jobUsedTime = round(($jobEndTime - $jobStartTime), 4);

        Log::info('执行定时任务【' . $this->description . '】，耗时' . $jobUsedTime . '秒');
    }

    // 扣减用户到期商品的流量
    private function decGoodsTraffic()
    {
        $orderList = Order::query()->with(['user', 'goods'])->where('status', 2)->where('is_expire', 0)->where('expire_at', '<=', date('Y-m-d H:i:s'))->get();
        if (!$orderList->isEmpty()) {
            // 用户默认标签
            $defaultLabels = [];
            if (self::$systemConfig['initial_labels_for_user']) {
                $defaultLabels = explode(',', self::$systemConfig['initial_labels_for_user']);
            }

            DB::beginTransaction();
            try {
                foreach ($orderList as $order) {
                    Order::query()->where('oid', $order->oid)->update(['is_expire' => 1]);

                    if (empty($order->user) || empty($order->goods)) {
                        continue;
                    }

                    // 到期自动处理
                    if ($order->expire_at) {
                        $traffic = $order->traffic * 1048576;
                        if ($traffic == 0) {
                            $traffic = $order->goods->traffic * 1048576;
                        }

                        if ($traffic == 0) {
                            continue;
                        }

                        if ($order->user->transfer_enable - $traffic <= 0) {
                            User::query()->where('id', $order->user_id)->update(['transfer_enable' => 0, 'u' => 0, 'd' => 0]);
                        } else if ($order->user->d - $traffic >= 0) {
                            User::query()->where('id', $order->user_id)->decrement('transfer_enable', $traffic);
                            User::query()->where('id', $order->user_id)->decrement('d', $traffic);
                        } else {
                            if ($order->user->u + $order->user->d - $traffic <= 0) {
                                User::query()->where('id', $order->user_id)->decrement('transfer_enable', $traffic);
                                User::query()->where('id', $order->user_id)->update(['u' => 0, 'd' => 0]);
                            } else {
                                User::query()->where('id', $order->user_id)->decrement('transfer_enable', $traffic);
                                User::query()->where('id', $order->user_id)->decrement('u', $traffic - $order->user->d);
                                User::query()->where('id', $order->user_id)->update(['d' => 0]);
                            }
                        }

                        // 删除该商品对应用户的所有标签
                        UserLabel::query()->where('user_id', $order->user->id)->delete();

                        // 取出用户的其他商品带有的标签
                        $goodsIds = Order::query()->where('user_id', $order->user->id)->where('oid', '<>', $order->oid)->where('status', 2)->where('is_expire', 0)->groupBy('goods_id')->pluck('goods_id')->toArray();
                        $goodsLabels = GoodsLabel::query()->whereIn('goods_id', $goodsIds)->groupBy('label_id')->pluck('label_id')->toArray();

                        // 生成标签
                        $labels = array_values(array_unique(array_merge($goodsLabels, $defaultLabels))); // 标签去重
                        foreach ($labels as $vo) {
                            $userLabel = new UserLabel();
                            $userLabel->user_id = $order->user->id;
                            $userLabel->label_id = $vo;
                            $userLabel->save();
                        }
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                \Log::error($this->description . '：' . $e);

                DB::rollBack();
            }
        }
    }
}
