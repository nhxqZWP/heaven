<?php
/**
 * Created by PhpStorm.
 * User: gundam
 * Date: 2019/3/23
 * Time: 3:29 PM
 */

namespace App\Http\Controllers;


use App\PlatformApi\BitMexApi;
use App\Services\BitMexStrategyService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TestController extends Controller
{
    private $client = null;
    public function __construct()
    {
        $this->client = new BitMexApi(
            env('BM1_KEY'),
            env('BM1_SECRET')
        );
    }

    public function test(BitMexStrategyService $bitmexService, Request $request)
    {
        $this->getTicker();
        $switch = $request->get('s', 0);
        Redis::set('close', $switch);
//        $bitmexService->similarBuySellPrice();
        $close = Redis::get('close');
        $switch = '运行中';
        if ($close === 1) {
            $switch = '已关闭';
        }

        return $switch;
    }

    // 获取现价
    public function getTicker()
    {
        $data = $this->client->getTicker();
        dd($data);
    }

    // 获取K线历史数据
    public function getCandles()
    {
        $data = $this->client->getCandles('1h', 2);
        dd(json_encode($data));
    }

    // 获取某订单信息
    public function getOrder()
    {
        $data = $this->client->getOrder('');
        dd(json_encode($data));
    }

    // 获取所有New或PartiallyFilled成交单信息
    public function getOpenOrders()
    {
        $data = $this->client->getOpenOrders();
        dd(json_encode($data));
    }

    // 获取btcusd的isOpen
    public function getOpenPositions()
    {
        $data = $this->client->getOpenPositions();
        dd($data);
    }

    // 获取btcusd的
    public function getPositions()
    {
        $data = $this->client->getPositions();
        dd($data);
    }

    public function closePosition()
    {
        $data = $this->client->closePosition('');
        dd($data);
    }

    public function getOrderBook()
    {
        $data = $this->client->getOrderBook();
        dd($data);
    }

    public function createLimitOrder()
    {
        $res = $this->client->createLimitOrder(1, 4000);
        if (!$res) {
            dd($this->client->errorMessage);
        }
        dd($res);
    }

    public function getWallet()
    {
        $res = $this->client->getWallet();
        if (!$res) {
            dd($this->client->errorMessage);
        }
        dd($res);
    }

















    /** ------------------------------------------------------------ */

    public function testPing()
    {
        $exchange = new \ccxt\binance();

        try {

            $symbol = 'BTC/USDT';
            $result = $exchange->fetch_ticker ($symbol);

            dd($result);

        } catch (\ccxt\NetworkError $e) {
            echo '[Network Error] ' . $e->getMessage () . "\n";
        } catch (\ccxt\ExchangeError $e) {
            echo '[Exchange Error] ' . $e->getMessage () . "\n";
        } catch (Exception $e) {
            echo '[Error] ' . $e->getMessage () . "\n";
        }
    }

    public function testBitmexOrderList()
    {
        $exchange = new \ccxt\bitmex(array (
             'verbose' => true, // for debugging
            'timeout' => 30000,
        ));

        try {

            // 交易对配置
//            $result = $exchange->fetchMarkets ();
//            $data = [];
//            foreach ($result as $item) {
//                $data[$item['id']] = $item;
//            }
//            print_r($data);
//            exit;

            // 市场订单深度
            $symbol = 'BTC/USD';
            $result = $exchange->fetchOrderBook($symbol);
            $sumAsk = 0; $sumBuy = 0;
            foreach ($result['asks'] as &$ask) {
                $sumAsk += $ask[1];
                $ask[2] = $sumAsk;
            }
            foreach ($result['bids'] as &$buy) {
                $sumBuy += $buy[1];
                $buy[2] = $sumBuy;
            }
            rsort($result['asks']);

            return view('test.orderBookList', ['data' => $result]);
//            dd ($result);

        } catch (\ccxt\NetworkError $e) {
            echo '[Network Error] ' . $e->getMessage () . "\n";
        } catch (\ccxt\ExchangeError $e) {
            echo '[Exchange Error] ' . $e->getMessage () . "\n";
        } catch (Exception $e) {
            echo '[Error] ' . $e->getMessage () . "\n";
        }
    }

    public function testBitmexPrice()
    {
        $exchange = new \ccxt\bitmex(array (
            // 'verbose' => true, // for debugging
            'timeout' => 30000,
        ));

        try {

            // 市场订单深度
            $symbol = 'BTC/USD';
            $result = $exchange->fetch_ohlcv($symbol);
            dd($result);

        } catch (\ccxt\NetworkError $e) {
            echo '[Network Error] ' . $e->getMessage () . "\n";
        } catch (\ccxt\ExchangeError $e) {
            echo '[Exchange Error] ' . $e->getMessage () . "\n";
        } catch (Exception $e) {
            echo '[Error] ' . $e->getMessage () . "\n";
        }
    }

    public function testBitmexOrder()
    {
        $exchange = new \ccxt\bitmex (array (
            'apiKey' => 'DQxJ-9ShAv9Ev3kJwX_1afAj',
            'secret' => 'IUyx0qUYn7B27Qyqn9-T6HGOfKuZuu9LJ7nSQbKKs52fdmFp',
            'enableRateLimit' => true,
            'urls' => [
                'api' => 'https://testnet.bitmex.com'
            ],
        ));

        $symbol = 'BTC/USD'; // bitcoin contract according to bitmex futures coding
        $type = 'StopLimit'; // # or 'market', or 'Stop' or 'StopLimit'
        $side = 'buy'; // or 'buy'
        $amount = 1.0;
        $price = 4000.0; // or None

        // extra params and overrides
        $params = array (
            'stopPx' => 4200.0, // if needed
        );

        $order = $exchange->create_order ($symbol, $type, $side, $amount, $price, $params);
        dd($order);

    }

    public function testBitmexBalance()
    {
        $exchange = new \ccxt\bitmex (array (
            'apiKey' => 'DQxJ-9ShAv9Ev3kJwX_1afAj',
            'secret' => 'IUyx0qUYn7B27Qyqn9-T6HGOfKuZuu9LJ7nSQbKKs52fdmFp',
            'enableRateLimit' => true,
//            'urls' => [
//                'api' => 'https://testnet.bitmex.com'
//            ],
        ));

        $balance = $exchange->fetch_balance();
        dd($balance);
    }
}