<?php
/**
 * Created by PhpStorm.
 * User: gundam
 * Date: 2019/4/2
 * Time: 11:50 PM
 */

namespace App\Services;


use App\PlatformApi\BitMexApi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class BitMexStrategyService
{
    const STATUS_INIT = 0;
    const STATUS_NOT_BUY_NOT_SELL = 1;
    const STATUS_HAS_BUY_NOT_FINISHED = 2;
    const STATUS_HAS_BUY_FINISHED = 3;
    const STATUS_HAS_SELL_NOT_FINISHED = 4;

    const ORDER_STATUS_NEW = 'new'; //New
    const ORDER_STATUS_FILLED = 'filled'; //Filled

    const ORDER_QUANTITY = 10; //订单usd下单量

    private $primaryKey;
    private $bitmex;

    public function __construct($key = 'BM1')
    {
        $this->primaryKey = $key;
        $envKey = $key . '_KEY';
        $envSecret = $key . '_SECRET';
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
        echo $status;
        if ($status == self::STATUS_NOT_BUY_NOT_SELL) { //无买单 无卖单
            $res = $this->_createLimitBuyOrderByBook();
            if (!$res) {
                goto ERROR;
            }

            //记录买单id
            $orderId = $res['orderID'];
            Redis::set($this->_getBuyOrderKey(), $orderId);
            //记录买单超时时间
            $overTimeStamp = time() + 60 * 3;
            Redis::set($this->_getBuyOrderOverTimeKey(), $overTimeStamp);
            return null;
        } elseif ($status == self::STATUS_HAS_BUY_NOT_FINISHED) { //有未完成买单
            $isOverTime = $this->_isOverBuyOrderTime();
            if ($isOverTime) { //超时取消买单
                $res = $this->_cancelBuyOrder();
                if (!$res) {
                    goto ERROR;
                }
            }
            return null;
        } elseif ($status == self::STATUS_HAS_BUY_FINISHED) { //买单完成
            $res = $this->_createLimitSellOrder();
            if (!$res) {
                goto ERROR;
            }
            //记录卖单id
            $orderId = $res['orderID'];
            Redis::set($this->_getSellOrderKey(), $orderId);
            //记录买单超时时间
            $overTimeStamp = time() + 60 * 10;
            Redis::set($this->_getSellOrderOverTimeKey(), $overTimeStamp);
            return null;
        } elseif ($status == self::STATUS_HAS_SELL_NOT_FINISHED) {//有未完成的卖单
            $isOverTime = $this->_isOverSellOrderTime();
            if ($isOverTime) { //超时取消卖单
                $res = $this->_cancelSellOrder();
                if (!$res) {
                    goto ERROR;
                }
            }
            return null;
        }

        ERROR:
        Log::error('出现init状态,程序运行出错');
        return null;
    }

    /**
     * orderBook:
     * [
        [
            "symbol" => "XBTUSD",
            "id" => 8799503100,
            "side" => "Sell",
            "size" => 1547213,
            "price" => 4969,
        ],
            "symbol" => "XBTUSD",
            "id" => 8799503150,
            "side" => "Buy",
            "size" => 30487,
            "price" => 4968.5,
        ]
     * ]
     *
     * @return bool|mixed
     */
    private function _createLimitBuyOrderByBook()
    {
        $orderBook = $this->bitmex->getOrderBook(1);
        if (!$orderBook) {
            Log::error($this->bitmex->errorMessage);
            return false;
        }
        $price = $orderBook[1]['price']; //买单book的最高买单
        $quantity = $this->_getOrderUSDQuantity();  //买单量 几个usd
        $res = $this->bitmex->createLimitOrder($quantity, $price);
        if (!$res) {
            Log::error($this->bitmex->errorMessage);
            return false;
        }
        //记录买单量
        Redis::set($this->primaryKey . '_buy_order_quantity', $res['orderQty']);
        return $res;
    }

    public function _createLimitSellOrder()
    {
        $price = -$this->_getBuyOrderPrice(); //此处买卖同价,卖单需要价格为负数！！
        $quantity = Redis::get($this->primaryKey . '_buy_order_quantity');
        if (empty($quantity)) {
            $quantity = $this->_getOrderUSDQuantity();
        }
        Log::debug($price);
        $res = $this->bitmex->createLimitOrder($quantity, $price);
        if (!$res) {
            Log::error($this->bitmex->errorMessage);
            return false;
        }
        return $res;
    }

    private function _getStatus()
    {
        $buyOrderValue = Redis::get($this->_getBuyOrderKey());
        $sellOrderValue = Redis::get($this->_getSellOrderKey());
        $buyIsNull = empty($buyOrderValue) ? true : false;
        $sellIsNull = empty($sellOrderValue) ? true : false;
        if ($buyIsNull && $sellIsNull) {
            return self::STATUS_NOT_BUY_NOT_SELL;
        } elseif ($sellIsNull) { //有买单id
            $buyOrder = $this->bitmex->getOrder($buyOrderValue);
            if (!$buyOrder) {
                Log::error($this->bitmex->errorMessage);
                goto ERROR;
            }
            // 买单未完成
            if (strtolower($buyOrder['ordStatus']) != self::ORDER_STATUS_FILLED) {
                return self::STATUS_HAS_BUY_NOT_FINISHED;
            }
            // 买单已完成,记录买单价格,删除买单key
            $this->_setBuyOrderPrice($buyOrder['price']);
            Redis::del($this->_getBuyOrderKey());
            return self::STATUS_HAS_BUY_FINISHED;
        } else { // 有卖单id
            $sellOrder = $this->bitmex->getOrder($sellOrderValue);
            if (!$sellOrder) {
                Log::error($this->bitmex->errorMessage);
                goto ERROR;
            }
            dd($sellOrder);
            // 卖单未完成
            if (strtolower($sellOrder['ordStatus']) != self::ORDER_STATUS_FILLED) {
                return self::STATUS_HAS_SELL_NOT_FINISHED;
            }
            // 卖单已完成,删除卖单key
            Redis::del($this->_getSellOrderKey());
            return self::STATUS_NOT_BUY_NOT_SELL;
        }

        ERROR:
        return self::STATUS_INIT;
    }

    private function _getBuyOrderKey()
    {
        return $this->primaryKey . '_buy_order';
    }

    private function _getSellOrderKey()
    {
        return $this->primaryKey . '_sell_order';
    }

    private function _setBuyOrderPrice($price)
    {
        Redis::set($this->primaryKey . '_buy_order_price', $price);
    }

    private function _getBuyOrderPrice()
    {
        $price = Redis::get($this->primaryKey . '_buy_order_price');
        if (empty($price)) {
            $orderBook = $this->bitmex->getOrderBook(1);
            if (!$orderBook) {
                Log::error($this->bitmex->errorMessage);
                exit;
            }
            $price = $orderBook[0]['price']; //卖单最低价
            Log::error('出错,卖单价格来自于市场挂单');
        }

        return $price;
    }

    private function _getOrderUSDQuantity()
    {
        return self::ORDER_QUANTITY;
    }

    private function _getBuyOrderOverTimeKey()
    {
        return $this->primaryKey . '_buy_order_overtime';
    }

    private function _getSellOrderOverTimeKey()
    {
        return $this->primaryKey . '_sell_order_overtime';
    }

    private function _isOverBuyOrderTime()
    {
        $time = Redis::get($this->_getBuyOrderOverTimeKey());
        return time() > $time ? true : false;
    }

    private function _isOverSellOrderTime()
    {
        $time = Redis::get($this->_getSellOrderOverTimeKey());
        return time() > $time ? true : false;
    }

    private function _cancelBuyOrder()
    {
        $orderId = Redis::get($this->_getBuyOrderKey());
        $res = $this->bitmex->cancelOrder($orderId);
        if (!$res) {
            Log::error($this->bitmex->errorMessage);
            return false;
        }
        Redis::del($this->_getBuyOrderKey());
        return $res;
    }

    private function _cancelSellOrder()
    {
        $orderId = Redis::get($this->_getSellOrderKey());
        $res = $this->bitmex->cancelOrder($orderId);
        if (!$res) {
            Log::error($this->bitmex->errorMessage);
            return false;
        }
        Redis::del($this->_getSellOrderKey());
        return $res;
    }
}