<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 22/09/15
 * Time: 17:52
 */

namespace Tronfo\Arbitrage\Bookmaker;

abstract class BookmakerBase
{
    protected $name;

    protected $loginFields = [];

    abstract public function login();

    abstract public function isLoggedIn();

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

    protected function getCookies()
    {
        $cookieStr = file_get_contents(storage_path() . 'auth' . $this->name);
        $cookies = array();

        $lines = explode("\n", $cookieStr);

        // iterate over lines
        foreach ($lines as $line) {
            // we only care for valid cookie def lines
            if (isset($line[0]) && substr_count($line, "\t") == 6) {

                // get tokens in an array
                $tokens = explode("\t", $line);

                // trim the tokens
                $tokens = array_map('trim', $tokens);

                $cookie = array();

                // Extract the data
                $cookie['domain'] = $tokens[0];
                $cookie['flag'] = $tokens[1];
                $cookie['path'] = $tokens[2];
                $cookie['secure'] = $tokens[3];

                // Convert date to a readable format
                $cookie['expiration'] = date('Y-m-d h:i:s', $tokens[4]);

                $cookie['name'] = $tokens[5];
                $cookie['value'] = $tokens[6];

                // Record the cookie.
                $cookies[] = $cookie;
            }
        }
        dd($cookies);
        return $cookies;
    }

}