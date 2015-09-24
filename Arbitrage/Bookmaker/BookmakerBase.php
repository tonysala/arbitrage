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

    protected $balance;

    protected $endpoint;

    protected $endpointDom;

    protected $loadedDoms = [];

    protected $loginFields = [];

    public function __construct()
    {
        $this->endpointDom = $this->getDom($this->endpoint);
        // Make alias in $loadedDoms
        $this->loadedDoms[$this->endpoint] = $this->endpointDom;
    }

    abstract public function login();

    abstract public function isLoggedIn();

    abstract public function getBalance();

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

    protected function getDom($endpoint)
    {
        $dom = new \DOMDocument;

        $curl = $this->request($endpoint);

        libxml_use_internal_errors(true);
        $dom->loadHTML($curl['data']);
        libxml_clear_errors();

        return $dom;
    }

    protected function parseCookies()
    {
        $cookieStr = file_get_contents(storage_path() . '/auth' . $this->name);
        $cookies = [];

        $lines = explode("\n", $cookieStr);

        foreach ($lines as $line) {
            if (isset($line[0]) && substr_count($line, "\t") == 6) {

                $tokens = explode("\t", $line);
                $tokens = array_map('trim', $tokens);

                $cookies[$tokens[5]] = [
                    'domain' => $tokens[0],
                    'flag' => $tokens[1],
                    'path' => $tokens[2],
                    'secure' => $tokens[3],
                    'expiration' => date('Y-m-d h:i:s', $tokens[4]),
                    'name' => $tokens[5],
                    'value' => $tokens[6]
                ];
            }
        }
        return $cookies;
    }

    public function cookie($name)
    {
        $cookies = $this->parseCookies();
        if (isset($cookies[$name])) {
            return $cookies[$name];
        }
        return null;
    }

}