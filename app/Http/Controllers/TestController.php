<?php
/**
 * Created by PhpStorm.
 * User: gundam
 * Date: 2019/3/23
 * Time: 3:29 PM
 */

namespace App\Http\Controllers;


use Exception;

class TestController extends Controller
{
    public function test()
    {
        return $this->testBitmexQuery();
    }

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

    public function testBitmexQuery()
    {
        $exchange = new \ccxt\bitmex(array (
            // 'verbose' => true, // for debugging
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

    public function testBitmexOrder()
    {
        $exchange = new \ccxt\bitmex (array (
            'apiKey' => 'YOUR_API_KEY', // ←------------ replace with your keys
            'secret' => 'YOUR_SECRET',
            'enableRateLimit' => true,
        ));

        $symbol = 'XBTM18'; // bitcoin contract according to bitmex futures coding
        $type = 'StopLimit'; // # or 'market', or 'Stop' or 'StopLimit'
        $side = 'sell'; // or 'buy'
        $amount = 1.0;
        $price = 6500.0; // or None

        // extra params and overrides
        $params = array (
            'stopPx' => 6000.0, // if needed
        );

        $order = $exchange->create_order ($symbol, $type, $side, $amount, $price, $params);

    }
}