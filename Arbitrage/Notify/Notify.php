<?php namespace Tronfo\Arbitrage\Notify;

class Notify
{
    protected $matches = [];

    public function notify()
    {
        $matchesCount = count($this->matches);

        $dateLength = $this->getMaxLength($this->matches, 'date', 11, function ($item, $property, $args = []) {
            return $item->$property->format('d/m/Y H:i');
        });
        $percLength = $this->getMaxLength($this->matches, 'percent', 7);
        $teamALength = $this->getMaxLength($this->matches, 'teamA', 6);
        $teamBLength = $this->getMaxLength($this->matches, 'teamB', 6);
        $marketLength = $this->getMaxLength($this->matches, 'market', 6);
        $stakeCallbackA = function ($item, $property, $args = []) {
            return $item->outcomeA->minStake . ' - ' . $item->outcomeA->stake . ' - ' . $item->outcomeA->maxStake;
        };
        $stakeCallbackB = function ($item, $property, $args = []) {
            return $item->outcomeB->minStake . ' - ' . $item->outcomeB->stake . ' - ' . $item->outcomeB->maxStake;
        };
        $stakeALength = $this->getMaxLength($this->matches, 'stake', 32, $stakeCallbackA);
        $stakeBLength = $this->getMaxLength($this->matches, 'stake', 32, $stakeCallbackB);
        $profitCallbackA = function ($item, $property, $args = []) {
            return $item->outcomeA->profit . ' -> ' . $item->outcomeA->maxProfit;
        };
        $profitCallbackB = function ($item, $property, $args = []) {
            return $item->outcomeB->profit . ' -> ' . $item->outcomeB->maxProfit;
        };
        $profitALength = $this->getMaxLength($this->matches, 'profit', 27, $profitCallbackA);
        $profitBLength = $this->getMaxLength($this->matches, 'profit', 27, $profitCallbackB);
        $callbackA = function ($item, $property, $args = []) {
            return $item->outcomeA->$property;
        };
        $callbackB = function ($item, $property, $args = []) {
            return $item->outcomeB->$property;
        };
        $oddsALength = $this->getMaxLength($this->matches, 'odds', 14, $callbackA);
        $oddsBLength = $this->getMaxLength($this->matches, 'odds', 14, $callbackB);

        $lines = [];
        $head = true;

        $headSeparator = '';
        foreach ($this->matches as $matches) {
            $cols = [];
            $cols[] = $this->padToLength($matches->date->format('d/m/Y H:i'), $dateLength);
            $cols[] = $this->padToLength($matches->percent, $percLength);
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
            $cols[] = '<a href="' . $matches->link . '">link</a>';

            if ($head === true) {

                $headCols[] = $this->padToLength('Date & Time', $dateLength);
                $headCols[] = $this->padToLength('Percent', $percLength);
                $headCols[] = $this->padToLength('Team A', $teamALength);
                $headCols[] = $this->padToLength('Team B', $teamBLength);
                $headCols[] = $this->padToLength('Market', $marketLength);
                $headCols[] = $this->padToLength('Outcome A stake (min / eq / max)', $stakeALength);
                $headCols[] = $this->padToLength('Outcome B stake (min / eq / max)', $stakeBLength);
                $headCols[] = $this->padToLength('Outcome A profit (eq / max)', $profitALength);
                $headCols[] = $this->padToLength('Outcome B profit (eq / max)', $profitBLength);
                $headCols[] = $this->padToLength('Outcome A odds', $oddsALength);
                $headCols[] = $this->padToLength('Outcome B odds', $oddsBLength);
                $headCols[] = 'Link';

                $headSeparator = '+';
                foreach ($headCols as $col) {
                    $len = strlen($col);
                    $headSeparator .= str_repeat('-', $len + 2);
                    $headSeparator .= '+';
                }
                $lines[] = $headSeparator;

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

            $message .= "Biggest + change [{$this->matches[0]->percentChange}%] : {$this->matches[0]->teamA} v {$this->matches[0]->teamB} ({$this->matches[0]->market})";
            $message .= PHP_EOL . PHP_EOL;
            $message .= '------------------------' . PHP_EOL . PHP_EOL;
            $message .= 'UPDATED:' . PHP_EOL;
            $message .= $matches;
            $message .= PHP_EOL . PHP_EOL;
            $message .= '</span>';
            $message .= PHP_EOL;

            $message = preg_replace('/\n/', "<br>\n", $message);
            $message = preg_replace('/  /', '&nbsp;&nbsp;', $message);

            $email = new \PHPMailer;
            $email->From = $toEmail;
            $email->FromName = 'arbs';
            $email->Subject = $subject;
            $email->Body = $message;
            $email->isHTML(true);
            $email->AddAddress($toEmail);

            $matches = preg_replace('/\n/', "<br>\n", $matches);
            $matches = preg_replace('/  /', '&nbsp;&nbsp;', $matches);
            file_put_contents(__DIR__ . '/output.html', $matches);

            $email->AddAttachment(__DIR__ . '/output.html', 'output.html');

            return $email->Send();
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