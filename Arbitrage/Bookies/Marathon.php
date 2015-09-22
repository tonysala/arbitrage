<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 22/09/15
 * Time: 17:55
 */

namespace Tronfo\Arbitrage\Bookies;


class Marathon extends BookieBase
{

    protected $name = 'Marathon';

    protected $endpoint = 'https://beta.marathonbet.co.uk/auth/login';

    protected $user = 'tonysala@live.co.uk';

    protected $pass = 'Coventry12';

    public function login()
    {
        $curl = $this->request($this->endpoint, [
            'login' => $this->user,
            'password' => $this->pass
        ]);

        dd($curl);
    }

    protected function isLoggedIn()
    {
        // check response code not 403
    }

}