<?php namespace Tronfo\Arbitrage;

use \cURL\RequestsQueue;

class Arbitrage
{
    /**
     * @var \PDO $pdo
     */
    public static $pdo;

    protected $endpoints = [];

    public $matches = [];

    public $updated = [];

    public $loggedIn = false;

    public $markets = [];

    public static $callbackArgs = [];

    protected $bookmakers;

    /**
     * @var Notify\Notify $logger
     */
    public $logger;

    public function __construct($dbString = '', $dbUser = '', $dbPass = '')
    {
        self::$pdo = new \PDO($dbString, $dbUser, $dbPass);
        $this->logger = new Notify\Notify;
    }

    public function run($stake = 100)
    {

        Arbitrage::$callbackArgs = [];

        // Init queue of requests
        $queue = new RequestsQueue;

        // Set default options for all requests in queue
        $opts = $queue->getDefaultOptions();
        $opts->set(CURLOPT_TIMEOUT, 5);
        $opts->set(CURLOPT_RETURNTRANSFER, true);
        $opts->set(CURLOPT_SSL_VERIFYPEER, false);
        $opts->set(CURLOPT_VERBOSE, false);
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        $opts->set(CURLOPT_USERAGENT, $agent);

        $cookieFile = storage_path() . '/authCookie';
        if (!file_exists($cookieFile)) {
            die("doesn't exist");
        }
        $opts->set(CURLOPT_COOKIEJAR, $cookieFile);
        $opts->set(CURLOPT_COOKIEFILE, $cookieFile);

        // Set function to be executed when request will be completed
        $queue->addListener('complete', function (\cURL\Event $event) use ($stake) {
            $args = Arbitrage::$callbackArgs[$event->request->getUID()];
            $response = $event->response;
            $html = $response->getContent(); // Returns content of response

            $market = new Market($args['marketName'], $args['endpoint']);

            $market->stake = $stake;
            $market->setHtml($html);
            $market->loadDOM();

            Arbitrage::$markets[$args['marketName']] = $market;
            if (Arbitrage::$markets[$args['marketName']]->rows instanceof \DOMNodeList) {
                foreach (Arbitrage::$markets[$args['marketName']]->rows as $row) {
                    $match = $market->getMatchFromRow($row);
                    if ($match !== false) {
                        if ($match->checkPriceShift() === true) {
                            $this->updated[] = $match;
                        }
                        $this->matches[] = $match;
                    }
                }
            }
        });

        // foreach
        $request = new \cURL\Request('');
        Arbitrage::$callbackArgs[$request->getUID()] = [
            'marketName' => '',
            'endpoint' => ''
        ];
        // Add request to queue
        $queue->attach($request);
        // foreach

        foreach ($this->bookmakers as $bookmaker) {
            foreach ($this->markets as $market) {
                // Check if Bookmaker implements this market
                if (in_array($market, class_implements($bookmaker))) {
                    $this->matches[$market] = $bookmaker->{'get'.$market.'Matches'}();
                }
            }
        }

        // Execute queue
        while ($queue->socketPerform()) {
            $queue->socketSelect();
        }

        return $this->matches;
    }

    public function sortByPercentage()
    {
        if (count($this->matches)) {
            usort($this->matches, function (Match $a, Match $b) {
                if ($a->percent === $b->percent) {
                    return 0;
                } elseif ($a->percent < $b->percent) {
                    return 1;
                } else {
                    return -1;
                }
            });
        }
    }

    public function addBookmakers(array $bookmakers)
    {
        foreach ($bookmakers as $name) {
            $this->bookmakers[$name] = new $name;
        }
    }

    public function addMarkets(array $markets)
    {
        $this->markets = $markets;
    }
}