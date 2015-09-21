<?php namespace Tronfo\Arbitrage;

class Arbitrage
{
    /**
     * @var PDO $pdo
     */
    public static $pdo;

    protected $endpoints = [];

    public $matches = [];

    public $updated = [];

    public static $markets = [];

    public function __construct($dbString = '', $dbUser = '', $dbPass = '')
    {
        self::$pdo = new \PDO($dbString, $dbUser, $dbPass);
    }

    public function run()
    {
        /**
        // Init queue of requests
        $queue = new \cURL\RequestsQueue;
// Set default options for all requests in queue
        $queue->getDefaultOptions()
            ->set(CURLOPT_TIMEOUT, 5)
            ->set(CURLOPT_RETURNTRANSFER, true);
// Set function to be executed when request will be completed
        $queue->addListener('complete', function (\cURL\Event $event) {
            $response = $event->response;
            $json = $response->getContent(); // Returns content of response
            $feed = json_decode($json, true);
            echo $feed['entry']['title']['$t'] . "\n";
        });

        $request = new \cURL\Request('http://gdata.youtube.com/feeds/api/videos/XmSdTa9kaiQ?v=2&alt=json');
// Add request to queue
        $queue->attach($request);

        $request = new \cURL\Request('http://gdata.youtube.com/feeds/api/videos/6dC-sm5SWiU?v=2&alt=json');
        $queue->attach($request);

// Execute queue
        $queue->send();
        */
        foreach ($this->endpoints as $marketName => $endpoint) {
            $market = new Market($marketName, $endpoint);

            $market->stake = 500;

            $market->loadHtml($endpoint);
            $market->loadDOM();

            self::$markets[$marketName] = $market;
            foreach (self::$markets[$marketName]->rows as $row) {

                $match = $market->getMatchFromRow($row);
                if ($match !== false) {
                    if ($match->checkPriceShift() === true) {
                        $this->updated[] = $match;
                    } else {
                        $this->matches[] = $match;
                    }
                }
            }
        }

        $this->sortByPercentage();
    }

    public function sortByPercentage()
    {
        if (count($this->updated)) {
            usort($this->updated, function (Match $a, Match $b) {
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

    public function sortByPositiveChange()
    {
        if (count($this->updated)) {
            usort($this->updated, function (Match $a, Match $b) {
                if ($a->percentChange === $b->percentChange) {
                    return 0;
                } elseif ($a->percentChange < $b->percentChange) {
                    return 1;
                } else {
                    return -1;
                }
            });
        }
    }

    public function sortByNegativeChange()
    {
        if (count($this->updated)) {
            usort($this->updated, function (Match $a, Match $b) {
                if ($a->percentChange === $b->percentChange) {
                    return 0;
                } elseif ($a->percentChange < $b->percentChange) {
                    return 1;
                } else {
                    return -1;
                }
            });
        }
    }

    public function notify()
    {
        $updatedCount = count($this->updated);

        $timeLength = $this->getMaxLength($this->updated, 'time', 4);
        $teamALength = $this->getMaxLength($this->updated, 'teamA', 4);
        $teamBLength = $this->getMaxLength($this->updated, 'teamB', 4);
        $marketLength = $this->getMaxLength($this->updated, 'market', 4);

        $callbackA = function ($item, $property, $args = []) {
            return $item->outcomeA->$property;
        };
        $callbackB = function ($item, $property, $args = []) {
            return $item->outcomeB->$property;
        };

        $stakeALength = $this->getMaxLength($this->updated, 'stake', 4, $callbackA);
        $oddsALength = $this->getMaxLength($this->updated, 'odds', 4, $callbackA);
        $stakeBLength = $this->getMaxLength($this->updated, 'stake', 4, $callbackB);
        $oddsBLength = $this->getMaxLength($this->updated, 'odds', 4, $callbackB);
        $lines = [];
        $head = true;

        foreach ($this->updated as $updated) {
            $cols = [];
            $cols[] = $this->padToLength($updated->date->format('d/m/Y H:i'), $timeLength);
            $cols[] = $this->padToLength($updated->teamA, $teamALength);
            $cols[] = $this->padToLength($updated->teamB, $teamBLength);
            $cols[] = $this->padToLength($updated->market, $marketLength);
            $cols[] = $this->padToLength($updated->outcomeA->stake, $stakeALength);
            $cols[] = $this->padToLength($updated->outcomeB->stake, $stakeBLength);
            $cols[] = $this->padToLength($updated->outcomeA->odds, $oddsALength);
            $cols[] = $this->padToLength($updated->outcomeB->odds, $oddsBLength);

            $line = '| ' . implode(' | ', $cols) . ' |';
            if ($head === true) {

                $head = false;
            }
            $lines[] = $line;
        }

        $updated = implode(PHP_EOL, $lines);

        if ($updatedCount > 0) {
            // To send HTML mail, the Content-type header must be set
            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            // Additional headers
            $headers .= 'From: localhost <localhost>' . "\r\n";

            $toEmail = 'tony@hallnet.co.uk';
            $subject = "$updatedCount Arbitrage opportunities ({$this->updated[0]->percent}%)";
            $message = "<span style='font-family: Monaco, \"Lucida Console\", monospace; font-size:12px'>";
            $message .= "Biggest margin   [{$this->updated[0]->percent}%] : {$this->updated[0]->teamA} v {$this->updated[0]->teamB} ({$this->updated[0]->market})";
            $message .= PHP_EOL;
            $this->sortByPositiveChange();
            $message .= "Biggest + change [{$this->updated[0]->percentChange}%] : {$this->updated[0]->teamA} v {$this->updated[0]->teamB} ({$this->updated[0]->market})";
            $message .= PHP_EOL . PHP_EOL;
            $message .= '------------------------' . PHP_EOL . PHP_EOL;
            $message .= 'UPDATED:' . PHP_EOL;
            $message .= $updated;
            $message .= PHP_EOL . PHP_EOL;
            $message .= '</span>';
            $message .= PHP_EOL;

            $message = preg_replace('/\n/', "<br>\n", $message);
            $message = preg_replace('/ /', '&nbsp;', $message);

            mail($toEmail, $subject, $message, $headers);
        }
    }

    public function addEndpoints(array $endpoints)
    {
        foreach ($endpoints as $name => $uri) {
            $this->endpoints[$name] = $uri;
        }
    }

    public function getMaxLength($array, $property, $min = 5, $callback = null, $callbackArgs = [])
    {
        $maxLength = $min;
        foreach ($array as $arrayKey => $arrayItem) {
            if (is_callable($callback)) {
                $length = strlen(call_user_func_array($callback, [$arrayItem, $property, $callbackArgs]));
            } else {
                $length = strlen($arrayItem->$property);
            }

            if ($length > $maxLength) {
                $maxLength = $length;
            }
        }
        return $maxLength;
    }

    public function padToLength($string, $min)
    {
        return str_pad($string, $min, ' ');
    }

    public function notifyError($errorMessage)
    {
        $toEmail = 'tony@hallnet.co.uk';
        $subject = 'Arbitrage query failed';
        $message = 'Error occurred' . PHP_EOL;
        $message .= '------------------------' . PHP_EOL . PHP_EOL;
        $message .= $errorMessage;

        mail($toEmail, $subject, $message);
    }
}