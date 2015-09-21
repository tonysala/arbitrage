<?php namespace Arbitrage;

class Market
{
    public $id;

    protected $html;

    public $rows;

    public $matches = [];

    public $stake;

    public $name;

    public $url;

    /**
     * @var \DateTime $date
     */
    protected $date;

    public function __construct($marketName, $marketUrl)
    {
        $stmt = Arbitrage::$pdo->prepare('SELECT id FROM markets WHERE name = :market_name AND url = :market_url');
        $stmt->bindParam(':market_name', $marketName);
        $stmt->bindParam(':market_url', $marketUrl);
        $stmt->execute();
        $id = $stmt->fetchColumn(0);

        if ($id === false) {
            $stmt = Arbitrage::$pdo->prepare('INSERT INTO markets (name, url) VALUES (:name, :url)');
            $stmt->bindParam(':name', $marketName);
            $stmt->bindParam(':url', $marketUrl);
            $stmt->execute();
            $id = Arbitrage::$pdo->lastInsertId();
        }

        $this->id = $id;
        $this->url = $marketUrl;
        $this->name = $marketName;
    }

    public function loadHtml($endpoint)
    {
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        $this->html = curl_exec($ch);
    }

    public function loadDOM()
    {
        if (!empty($this->html)) {
            $dom = new \DOMDocument;

            libxml_use_internal_errors(true);
            $dom->loadHTML($this->html);
            libxml_clear_errors();

            $element = $dom->getElementById('fixtures');
            $element = $element->getElementsByTagName('tbody')->item(0);

            $this->rows = $element->childNodes;
            return true;
        } else {
            return false;
        }
    }

    public function getMatchFromRow($row)
    {
        /**
         * @var \DOMNodeList $cols
         */
        $cols = $row->childNodes;
        if ($cols->length >= 5) {
            $time = $cols->item(0)->nodeValue;

            list($teamA, $teamB) = explode(' v ', $teams = $cols->item(1)->nodeValue);

            $oddsA = $cols->item(2)->nodeValue;
            $oddsB = $cols->item(3)->nodeValue;

            preg_match('|(.*?) \(([0-9]+)/([0-9]+)\)$|', $oddsA, $m);
            if (count($m) >= 4) {
                $oddsA = round(($m[2] / $m[3]) + 1.0, 2);
                $outcomeA = trim($m[1]);
            } else {
                return false;
            }

            preg_match('|(.*?) \(([0-9]+)/([0-9]+)\)$|', $oddsB, $m);
            if (count($m) >= 4) {
                $oddsB = round(($m[2] / $m[3]) + 1.0, 2);
                $outcomeB = trim($m[1]);
            } else {
                return false;
            }

            $link = $cols->item(4)->firstChild->attributes->getNamedItem('href')->nodeValue;

            $match = new Match($this->stake, $outcomeA, $outcomeB, $oddsA, $oddsB);
            $match->setTeams($teamA, $teamB);
            $match->setMarket($this->name);
            if (preg_match('/([0-9]{2}):([0-9]{2})/', $time, $timePieces)) {
                $hour = $timePieces[1];
                $min = $timePieces[2];
            } else {
                // Match is live, ignore
                return false;
            }
            $this->date->setTime((int)$hour, (int)$min, 0);
            $match->setDate($this->date);
            $match->setLink($link);
            if ($match->isArbitrable()) {
                return $match;
            }
        } elseif ($cols->length === 1) {
            $col = $cols->item(0);
            $dateString = $col->nodeValue;
            if (preg_match('/^[a-z]+ ([0-9]{1,2})[a-z]{2} ([a-z]+) ([0-9]{4})/i', $dateString, $datePieces)) {
                $date = new \DateTime;
                $date->setDate($datePieces[3], date('m', strtotime($datePieces[2])), $datePieces[1]);
                $this->changeDate($date);
            }
        }
        return false;
    }

    public function changeDate($date)
    {
        $this->date = $date;
    }
}