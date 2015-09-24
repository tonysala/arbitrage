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

    protected $endpoint = 'https://mobile.bet365.com/';

    /**
     * @var \DOMDocument $endpointDom
     */
    protected $endpointDom;

    protected $loginUri = 'lp/default.aspx';

    protected $matchListUri = 'default.aspx?apptype=&appversion=&ver=2.1.28.0#type=Splash;key=1;ip=0;lng=1';
    protected $matchListUri = 'default.aspx?lng=1&zn=1&apptype=&appversion=&rnd=47648#type=Coupon;key=1-1-56-981-140-0-0-0-1-0-0-4050-0-0-1-0-0-0-0-0-0;ip=0;lng=1;anim=1';

    protected $user = 'tonysala1994';

    protected $pass = 'coventry';

    protected $loggedIn = false;

    public function login()
    {
        $curl = $this->request($this->endpoint . $this->loginUri, [
            'txtUsername' => $this->user,
            'txtPassword' => $this->pass,
            'txtTKN' => $this->getTkn(),
            'txtType' => '47'
        ]);

        if ($this->cookie('usdi') !== null) {
            $this->loggedIn = true;
        }
    }

    public function isLoggedIn()
    {
        return $this->loggedIn;
    }

    public function getBalance()
    {
        $balance = $this->endpointDom->getElementById('NavUserBalance');
        dd($balance->nodeValue);
    }

    private function getTkn()
    {
        return $this->cookie('pstk');
    }

}