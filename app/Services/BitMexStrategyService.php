<?php
/**
 * Created by PhpStorm.
 * User: gundam
 * Date: 2019/4/2
 * Time: 11:50 PM
 */

namespace App\Services;


use App\PlatformApi\BitMexApi;
use Illuminate\Support\Facades\Redis;

class BitMexStrategyService
{
    const STATUS_INIT = 0;
    const STATUS_NOT_BUY_NOT_SELL = 1;
    const STATUS_HAS_BUY_NOT_FINISHED = 2;
    const STATUS_HAS_BUY_FINISHED = 3;
    const STATUS_HAS_SELL_NOT_FINISHED = 4;

    private $primaryKey;
    private $bitmex;

    public function __construct($key = 'BM1')
    {
        $this->primaryKey = $key;
        $envKey = $key . 'KEY';
        $envSecret = $key . 'SECRET';
        $this->bitmex = new BitMexApi(
            env($envKey),
            env($envSecret)
        );
    }


    /**
     * 相近买卖赚取平台返利策略1 单账号顺序交易
     * 状态划分：
     * ----无买卖单,下买单----买单成交,下不低于买单价的卖单----卖单成交,清空状态----
     *            |                             |
     *            ----买单3分钟不成交,重下买单     ----卖单6分钟不成交,取当前卖单深度价
     *
     * null                        状态0
     * 无买单id,无卖单id       -->   状态1
     * 下买单 买单下成功        -->  状态2
     * 有买单id,查询状态,如果成交-->  状态3
     * 下卖单 卖单下成功        -->  状态4
     * 有卖单id,查询状态,如果成交-->  状态1
     *
     * 状态2计时3分钟,超时如果部分成交则不取消,15分钟未全部成交则取消,标记状态1,重新下买单
     * 状态4计时6分钟,超时如果部分成交则不取消,30分钟未全部成交则取消,标记状态3,重新下卖单
     *
     *
     */
    public function similarBuySellPrice()
    {
        $status = $this->_getStatus();
        if ($status == self::STATUS_NOT_BUY_NOT_SELL) { //无买单 无卖单
            dd(1);

        } elseif ($status == self::STATUS_HAS_BUY_NOT_FINISHED) { //有未完成单买单

        } elseif ($status == self::STATUS_HAS_BUY_FINISHED) { //买单完成

        } elseif ($status == self::STATUS_HAS_SELL_NOT_FINISHED) {//有未完成的卖单

        } else {
            return null;
        }
    }

    private function _getStatus()
    {
        $buyOrderKey = $this->primaryKey . 'buy_order';
        $sellOrderKey = $this->primaryKey . 'sell_order';
        $buyOrderValue = Redis::get($buyOrderKey);
        $sellOrderValue = Redis::get($sellOrderKey);
        $buyIsNull = empty($buyOrderValue) ? true : false;
        $sellIsNull = empty($sellOrderValue) ? true : false;
        if ($buyIsNull && $sellIsNull) {
            return self::STATUS_NOT_BUY_NOT_SELL;
        } elseif ($sellIsNull) { //有买单id
            $buyOrder = $this->bitmex->getOrder($buyOrderValue);
        } else { // 有卖单id
            $sellOrder = $this->bitmex->getOrder($sellOrderValue);
        }

        return self::STATUS_INIT;
    }

}