<?php namespace Tronfo\Arbitrage;

class Match
{
    /**
     * @var Outcome $outcomeA
     */
    public $outcomeA;

    /**
     * @var Outcome $outcomeB
     */
    public $outcomeB;

    public $percent;

    public $percentChange = 0;

    public $teamA;

    public $teamB;

    /**
     * @var \DateTime $date
     */
    public $date;

    public $link;

    public $market;

    public function __construct($stake, $outcomeA, $outcomeB, $oddsA, $oddsB)
    {
        $stakeA = ($stake / ($oddsA + $oddsB)) * $oddsB;
        $stakeB = ($stake / ($oddsA + $oddsB)) * $oddsA;

        $this->outcomeA = new Outcome($outcomeA, $oddsA, $stakeA);
        $this->outcomeA->setBoundaries($oddsB, $stake);

        $this->outcomeB = new Outcome($outcomeB, $oddsB, $stakeB);
        $this->outcomeB->setBoundaries($oddsA, $stake);

        $this->percent = 1 - ($this->outcomeA->odds * $this->outcomeB->odds) / ($this->outcomeA->odds + $this->outcomeB->odds);
        $this->percent = round(($this->percent * 100), 3);
        // flip the sign
        $this->percent = -1 * $this->percent;
    }

    public function checkPriceShift()
    {
        $stmt = Arbitrage::$pdo->prepare('SELECT * FROM opportunities WHERE url = :link AND market = :market_name');
        $stmt->bindParam(':market_name', Arbitrage::$markets[$this->market]->id);
        $stmt->bindParam(':link', $this->link);
        $stmt->execute();
        $match = $stmt->fetch();

        if ($match !== false) {
            if ((float)$this->percent !== (float)$match['percentage']) {
                $this->percentChange = ($this->percent - $match['percentage']);
                return true;
            }
            return false;
        } else {
            $stmt = Arbitrage::$pdo->prepare(
                'INSERT INTO opportunities
                  (url, percentage, team_a, team_b, market, odds_a, odds_b)
                  VALUES (:url, :per, :team_a, :team_b, :market, :odds_a, :odds_b)'
            );
            $stmt->bindParam(':url', $this->link);
            $stmt->bindParam(':per', $this->percent);
            $stmt->bindParam(':team_a', $this->teamA);
            $stmt->bindParam(':team_b', $this->teamB);
            $stmt->bindParam(':market', Arbitrage::$markets[$this->market]->id);
            $stmt->bindParam(':odds_a', $this->outcomeA->odds);
            $stmt->bindParam(':odds_b', $this->outcomeB->odds);
            $stmt->execute();
            return true;
        }
    }

    public function setTeams($teamA, $teamB)
    {
        $this->teamA = $teamA;
        $this->teamB = $teamB;
    }

    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    public function setTime($hour, $min = 0, $sec = 0)
    {
        print $this->teamA . $hour . ':' . $min .PHP_EOL;
        $this->date->setTime($hour, $min, $sec);
    }

    public function setLink($link)
    {
        $this->link = "<a href='http://www.oddschecker.com{$link}'>{$link}</a>";
    }

    public function isPreMatch()
    {
        if ($this->date < new \DateTime('now', new \DateTimeZone('Europe/London'))) {
            return false;
        }
        return true;
    }

    public function setMarket($market)
    {
        $this->market = $market;
    }

    public function isArbitrable()
    {
        return $this->percent > 0;
    }
}