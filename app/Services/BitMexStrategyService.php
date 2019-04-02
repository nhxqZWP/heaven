<?php
/**
 * Created by PhpStorm.
 * User: gundam
 * Date: 2019/4/2
 * Time: 11:50 PM
 */

namespace App\Services;


use App\PlatformApi\BitMexApi;

class BitMexStrategyService
{
    private $primaryKey;
    private $bitmex;

    private function __construct($key = 'BM1')
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
     * ----无买卖单,下买单----买单成交,下不低于买单价的卖单(止损单)----卖单成交,清空状态----
     *            |                             |
     *            ----买单3分钟不成交,重下买单     ----卖单6分钟不成交,取当前卖单深度价
     *
     * null                         状态0
     * 无买单id,无卖单id        -->  状态1
     * 下买单 买单下成功        -->  状态2
     * 有买单id,查询状态,如果成交-->  状态3
     * 下卖单 卖单下成功        -->  状态4
     * 有卖单id,查询状态,如果成交-->  状态1
     *
     * 状态2计时3分钟,超时取消买单,标记状态1,重新下买单
     * 状态4计时6分钟,超时取消卖单,标记状态3,重新下卖单
     *
     *
     */
    public function similarBuySellPrice()
    {

    }

}