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

    public function run($stake = 100)
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

            $market->stake = $stake;

            $market->loadHtml($endpoint);
            $market->loadDOM();

            self::$markets[$marketName] = $market;
            foreach (self::$markets[$marketName]->rows as $row) {

                $match = $market->getMatchFromRow($row);
                if ($match !== false) {
                    if ($match->checkPriceShift() === true) {
                        $this->updated[] = $match;
                    }
                    $this->matches[] = $match;
                }
            }
        }

        $this->sortByPercentage();
        return $this->updated;
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
        $matchesCount = count($this->matches);

        $dateLength = $this->getMaxLength($this->matches, 'date', 11, function($item, $property, $args = []) {
            return $item->$property->format('d/m/Y H:i');
        });
        $teamALength = $this->getMaxLength($this->matches, 'teamA', 6);
        $teamBLength = $this->getMaxLength($this->matches, 'teamB', 6);
        $marketLength = $this->getMaxLength($this->matches, 'market', 6);

        $callbackA = function ($item, $property, $args = []) {
            return $item->outcomeA->$property;
        };
        $callbackB = function ($item, $property, $args = []) {
            return $item->outcomeB->$property;
        };

        $profitCallbackA = function ($item, $property, $args = []) {
            return $item->outcomeA->profit . ' -> ' . $item->outcomeA->maxProfit;
        };
        $profitCallbackB = function ($item, $property, $args = []) {
            return $item->outcomeB->profit . ' -> ' . $item->outcomeB->maxProfit;
        };

        $stakeCallbackA = function ($item, $property, $args = []) {
            return $item->outcomeA->minStake . ' - ' . $item->outcomeA->stake . ' - ' . $item->outcomeA->maxStake;
        };
        $stakeCallbackB = function ($item, $property, $args = []) {
            return $item->outcomeB->minStake . ' - ' . $item->outcomeB->stake . ' - ' . $item->outcomeB->maxStake;
        };

        $stakeALength = $this->getMaxLength($this->matches, 'stake', 29, $stakeCallbackA);
        $stakeBLength = $this->getMaxLength($this->matches, 'stake', 29, $stakeCallbackB);
        $profitALength = $this->getMaxLength($this->matches, 'profit', 24, $profitCallbackA);
        $profitBLength = $this->getMaxLength($this->matches, 'profit', 24, $profitCallbackB);
        $oddsALength = $this->getMaxLength($this->matches, 'odds', 11, $callbackA);
        $oddsBLength = $this->getMaxLength($this->matches, 'odds', 11, $callbackB);


        $lines = [];
        $head = true;

        $headSeparator = '';
        foreach ($this->matches as $matches) {
            $cols = [];
            $cols[] = $this->padToLength($matches->date->format('d/m/Y H:i'), $dateLength);
            $cols[] = $this->padToLength($matches->teamA, $teamALength);
            $cols[] = $this->padToLength($matches->teamB, $teamBLength);
            $cols[] = $this->padToLength($matches->market, $marketLength);

            $cols[] = $this->padToLength(
                $matches->outcomeA->minStake . ' - ' . $matches->outcomeA->stake . ' - ' . $matches->outcomeA->maxStake,
                $stakeALength);

            $cols[] = $this->padToLength(
                $matches->outcomeB->minStake . ' - ' . $matches->outcomeB->stake . ' - ' . $matches->outcomeB->maxStake,
                $stakeBLength);

            $cols[] = $this->padToLength(
                $matches->outcomeA->profit . ' -> ' . $matches->outcomeA->maxProfit,
                $profitALength);

            $cols[] = $this->padToLength(
                $matches->outcomeB->profit . ' -> ' . $matches->outcomeB->maxProfit,
                $profitBLength);

            $cols[] = $this->padToLength($matches->outcomeA->odds, $oddsALength);
            $cols[] = $this->padToLength($matches->outcomeB->odds, $oddsBLength);

            if ($head === true) {
                $headSeparator = '+';
                foreach ($cols as $col) {
                    $len = strlen($col);
                    $headSeparator .= str_repeat('-', $len + 2);
                    $headSeparator .= '+';
                }
                $lines[] = $headSeparator;
                $headCols[] = $this->padToLength('Date & Time', $dateLength);
                $headCols[] = $this->padToLength('Team A', $teamALength);
                $headCols[] = $this->padToLength('Team B', $teamBLength);
                $headCols[] = $this->padToLength('Market', $marketLength);
                $headCols[] = $this->padToLength('Team A stake (min / eq / max)', $stakeALength);
                $headCols[] = $this->padToLength('Team B stake (min / eq / max)', $stakeBLength);
                $headCols[] = $this->padToLength('Team A profit (eq / max)', $profitALength);
                $headCols[] = $this->padToLength('Team B profit (eq / max)', $profitBLength);
                $headCols[] = $this->padToLength('Team A odds', $oddsALength);
                $headCols[] = $this->padToLength('Team B odds', $oddsBLength);
                $headLine = '| ' . implode(' | ', $headCols) . ' |';
                $lines[] = $headLine;
                $lines[] = $headSeparator;

                $head = false;
            }
            $line = '| ' . implode(' | ', $cols) . ' |';
            $lines[] = $line;
        }
        $lines[] = $headSeparator;

        $matches = implode(PHP_EOL, $lines);

        if ($matchesCount > 0) {
            $toEmail = 'tony@hallnet.co.uk';
            $subject = "$matchesCount Arbitrage opportunities ({$this->matches[0]->percent}%)";
            $message = "<span style='font-family: Monaco, \"Lucida Console\", monospace; font-size:12px'>";
            $message .= "Biggest margin   [{$this->matches[0]->percent}%] : {$this->matches[0]->teamA} v {$this->matches[0]->teamB} ({$this->matches[0]->market})";
            $message .= PHP_EOL;
            $this->sortByPositiveChange();
            $message .= "Biggest + change [{$this->matches[0]->percentChange}%] : {$this->matches[0]->teamA} v {$this->matches[0]->teamB} ({$this->matches[0]->market})";
            $message .= PHP_EOL . PHP_EOL;
            $message .= '------------------------' . PHP_EOL . PHP_EOL;
            $message .= 'UPDATED:' . PHP_EOL;
            $message .= $matches;
            $message .= PHP_EOL . PHP_EOL;
            $message .= '</span>';
            $message .= PHP_EOL;

            $message = preg_replace('/\n/', "<br>\n", $message);
            $message = preg_replace('/ /', '&nbsp;', $message);

            $email = new \PHPMailer;
            $email->From = $toEmail;
            $email->FromName = 'arbs';
            $email->Subject = $subject;
            $email->Body = $message;
            $email->isHTML(true);
            $email->AddAddress($toEmail);
            file_put_contents(__DIR__ . '/output.txt', $matches);

            $email->AddAttachment(__DIR__ . '/output.txt', 'output.txt');

            return $email->Send();
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