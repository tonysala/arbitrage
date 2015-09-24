<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 24/09/15
 * Time: 12:05
 */

namespace Tronfo\Arbitrage\Bookmaker;


class Bet365 extends BookmakerBase
{
    protected $market = [];

    protected $matches = [];

    protected $name = 'Bet365';

    protected $endpoint = 'https://mobile.bet365.co.uk/';

    protected $loginUri = 'lp/default.aspx';

    protected $user = 'tonysala@live.co.uk';

    protected $pass = 'Coventry12';

    protected $loggedIn = false;

    public function login()
    {
        $curl = $this->request($this->endpoint . $this->loginUri, [
            'txtUsername' => $this->user,
            'txtPassword' => $this->pass,
            'txtTKN' => $this->getTKN(),
            'txtType' => '47'
        ]);

        if ($curl['info']['http_code'] === 200) {
            $this->loggedIn = true;
        }
    }

    public function isLoggedIn()
    {
        return $this->loggedIn;
    }

    private function getTKN()
    {
        $curl = $this->request($this->endpoint);
        $this->getCookies();
        return '';
    }

}