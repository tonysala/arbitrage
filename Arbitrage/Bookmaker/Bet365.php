<?php namespace Tronfo\Arbitrage\Bookmaker;

use Tronfo\Arbitrage\Market\OverUnderTwoPointFive;

class Bet365 extends BookmakerBase implements OverUnderTwoPointFive
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

    protected $marketEndpoints = [
        'OverUnderOnePointFive'   => 'default.aspx?rnd=#type=Coupon;key=1-1-56-981-140;',
        'OverUnderTwoPointFive'   => 'default.aspx?rnd=#type=Coupon;key=1-1-56-981-140;',
        'OverUnderThreePointFive' => 'default.aspx?rnd=#type=Coupon;key=1-1-56-981-140;',
        'OverUnderFourPointFive'  => 'default.aspx?rnd=#type=Coupon;key=1-1-56-981-140;',
    ];

    protected $marketDoms = [];

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

    public function getOverUnderTwoPointFiveMatches()
    {
        $marketName = 'OverUnderTwoPointFive';
        $endpoint = $this->marketEndpoints[$marketName];

        if (!$this->isDomLoaded($marketName)) {
            $this->loadedDoms[$endpoint] = $this->getDom($endpoint);
        }

        $coupon = $this->loadedDoms[$endpoint]->getElementById('Coupon');
        /**
         * @var \DOMElement $coupon
         */
        foreach ($coupon->childNodes as $row) {
            dd($row);
        }
    }

    private function getTkn()
    {
        return $this->cookie('pstk');
    }

    private function isDomLoaded($marketName)
    {
        if (array_key_exists($this->marketEndpoints[$marketName], $this->loadedDoms)) {
            return true;
        }
        return false;
    }
}