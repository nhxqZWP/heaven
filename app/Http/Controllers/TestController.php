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

            $result = $exchange->fetch_tickers ();

            dd ($result);

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