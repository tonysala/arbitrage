<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 22/09/15
 * Time: 17:52
 */

namespace Tronfo\Arbitrage\Bookies;


class BookieBase {

    protected $name;

    protected $loginFields = [];

    abstract protected function login();

    abstract protected function isLoggedIn();

    protected function request($endpoint, $data = [])
    {
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        $cookieFile = storage_path() . '/auth' . $this->name;
        if (!file_exists($cookieFile)) {
            touch($cookieFile);
        }
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        if (!empty($data)) {
            $dataString = http_build_query($data);

            curl_setopt($ch, CURLOPT_POST, count($data));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        }
        return [
            'data' => curl_exec($ch),
            'info' => curl_getinfo($ch),
            'error' => curl_error($ch)
        ];
    }

}