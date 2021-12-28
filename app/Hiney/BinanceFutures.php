<?php

namespace App\Hiney;

use Illuminate\Support\Facades\Http;

class BinanceFutures
{

    private string $base_url;
    private string $public_api;
    private string $private_api;

    public function __construct()
    {

        $this->public_api = config('api.public_api');
        $this->private_api = config('api.private_api');
        $this->base_url = 'https://fapi.binance.com';

    }

    public function getContracts(): array
    {

        return Http::get($this->base_url . '/fapi/v1/exchangeInfo')->collect()->toArray();

    }

    public function getBalances(): array
    {

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->public_api
        ])->get(
            $this->base_url . '/fapi/v1/account',
            [
                'timestamp' => time() * 1000,
                'signature' => $this->generateSignature()
            ]
        )->collect()->toArray();

    }

    public function createOrder($symbol, $side, $quantity, $order_type, $price = null)/*: array*/
    {
/*        $url = 'https://api.binance.com/api/v3/order';
        $method = 'POST';
        $headers = [
            'X-MBX-APIKEY' => 'XFJbRCeV19v7kyNo7SuvJPkZaC9npEsrmcGabq5Z5fiY2Mu5ACDsukjl5JraApvH',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        list($msec, $sec) = explode(' ', microtime());

        $query = "timestamp=" . $sec . substr($msec, 2, 3) . "&symbol=WAVESUSDT&type=MARKET&side=BUY&quantity=20";

        $signature = \hash_hmac('sha256', $query, $this->private_api);

        $body = $query . '&signature=' . $signature;

        if (!$headers) {
            $headers = array();
        } elseif (is_array($headers)) {
            $tmp = $headers;
            $headers = array();
            foreach ($tmp as $key => $value) {
                $headers[] = $key . ': ' . $value;
            }
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_ENCODING, '');

        if ($method == 'GET') {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        } elseif ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        } elseif ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            $headers[] = 'X-HTTP-Method-Override: PUT';
        } elseif ($method == 'PATCH') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        } elseif ($method === 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);

            $headers[] = 'X-HTTP-Method-Override: DELETE';
        }

        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);


        $result = mb_substr(curl_exec($curl), curl_getinfo($curl, CURLINFO_HEADER_SIZE));

        debug($result, true);*/

        list($msec, $sec) = explode(' ', microtime());

        $query = "timestamp=" . $sec . substr($msec, 2, 3) . "&symbol=WAVESUSDT&type=MARKET&side=BUY&quantity=20";

        $body = $query . '&signature=' . \hash_hmac('sha256', $query, $this->private_api);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->public_api,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->withBody($body, 'application/json')->post(
            'https://api.binance.com/api/v3/order'
        )->collect()->toArray();

        //$side = BUY || SELL

        if (!empty($price))
            $options['price'] = $price;

        $options = [
            'symbol' => $symbol,
            'side' => $side,
            'quantity' => $quantity,
            'type' => $order_type,
            'timestamp' => time() * 1000,
            'signature' => $this->generateSignature()
        ];

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->public_api
        ])->post(
            $this->base_url . '/fapi/v1/order',
            [
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'type' => $order_type,
                'timestamp' => time() * 1000,
                'signature' => $this->generateSignature()
            ]
        )->collect()->toArray();

    }

    public function cancelOrder($order_id, $symbol): array
    {

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->public_api
        ])->delete(
            $this->base_url . '/fapi/v1/order',
            [
                'orderId' => $order_id,
                'symbol' => $symbol,
                'timestamp' => time() * 1000,
                'signature' => $this->generateSignature()
            ]
        )->collect()->toArray();

    }

    public function getOrderStatus($order_id, $symbol): array
    {

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->public_api
        ])->get(
            $this->base_url . '/fapi/v1/order',
            [
                'orderId' => $order_id,
                'symbol' => $symbol,
                'timestamp' => time() * 1000,
                'signature' => $this->generateSignature()
            ]
        )->collect()->toArray();

    }

    private function generateSignature(): bool|string
    {

        return hash_hmac('sha256', 'timestamp=' . time() * 1000, $this->private_api);

    }

}
