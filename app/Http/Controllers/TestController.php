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
        return $this->testBitmexBalance();
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