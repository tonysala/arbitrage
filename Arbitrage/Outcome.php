<?php namespace Arbitrage;

class Outcome
{
    public $name;

    public $odds;

    public $stake;

    public $maxStake;

    public $minStake;

    public $profit;

    public $maxProfit;

    public function __construct($name, $odds, $stake)
    {
        $this->name = $name;
        $this->odds = $odds;
        $this->stake = number_format($stake, 2);
    }

    public function setBoundaries($opponentOdds, $totalStake)
    {
        $this->profit = number_format(
            (($totalStake / ($this->odds + $opponentOdds) * $opponentOdds) * $this->odds) - $totalStake,
            2
        );
        $this->minStake = number_format(($totalStake / $this->odds), 2);
        $this->maxStake = number_format($totalStake - ($totalStake / $opponentOdds), 2);
        $this->maxProfit = number_format(($this->odds * $this->maxStake) - $totalStake, 2);
    }
}