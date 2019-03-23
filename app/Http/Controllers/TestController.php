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
        return $this->testPing();
    }

    public function testPing()
    {
        $exchange = new \ccxt\binance (array (
            'verbose' => true,
//            'timeout' => 30000,
            'proxy' => "http://47.244.18.192:8888/"
        ));

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
}