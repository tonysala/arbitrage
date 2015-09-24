<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 22/09/15
 * Time: 17:55
 */

namespace Tronfo\Arbitrage\Bookmaker;


class Marathon extends BookmakerBase
{
    protected $market = [];

    protected $matches = [];

    protected $name = 'Marathon';

    protected $endpoint = 'https://beta.marathonbet.co.uk/';

    protected $loginUri = 'auth/login';

    protected $user = 'tonysala@live.co.uk';

    protected $pass = 'Coventry12';

    protected $loggedIn = false;

    public function login()
    {
        $curl = $this->request($this->endpoint . $this->loginUri, [
            'login' => $this->user,
            'password' => $this->pass
        ]);

        if ($curl['info']['http_code'] === 200) {
            $this->loggedIn = true;
        }
    }

    public function isLoggedIn()
    {
        return $this->loggedIn;
    }

}