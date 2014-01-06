<?php

namespace Sudlik\PolishNumeral;

use Exception\CaseDoesNotExists;
use Exception\UnsupportedNumber;
use Exception\WordDoesNotExists;

class Converter
{
    const MIN_SUPPORTED_VALUE   = 0;
    const MAX_SUPPORTED_VALUE   = 2147483647;
    const WORD_SEPARATOR        = ' ';

    private static $SUFFIXES = [
        'naście',
        'dziesiąt',
        'sta',
        'set',
    ];

    private static $WORDS = [
        'zero',
        'jeden',
        'dwa',
        'trzy',
        'cztery',
        'pięć',
        'sześć',
        'siedem',
        'osiem',
        'dziewięć',
        'dziesięć',
        'jedenaście',
        14 => 'czternaście',
        'piętnaście',
        'szesnaście',
        19 => 'dziewiętnaście',
        'dwadzieścia',
        30 => 'trzydzieści',
        40 => 'czterdzieści',
        100 => 'sto',
        200 => 'dwieście',
        1000 => 'tysiąc',
        1000000 => 'milion',
        1000000000 => 'miliard',
        1000000000000 => 'bilion',
        1000000000000000 => 'biliard',
        1000000000000000000 => 'trylion',
    ];

    private static $CASES = [
        1 => ['tysięcy', 'tysiące'],
    ];

    private static $CASE_SUFFIXES = [
        'ów',
        'y',
    ];

    private $number;
    private $words;

    /** Constructor
     *  @param    $number    int    required    Non-negative integer smaller than self::MAX_SUPPORTED_VALUE
     */
    public function __construct($number)
    {
        if ($this->isSupportedNumber($number)) {
            $this->setNumber($number);
            $this->setWords($this->asWords($this->number));
        } else {
            throw new UnsupportedNumber;
        }
    }

    public function __toString()
    {
        return $this->getWords();
    }

    private function isSupportedNumber($number)
    {
        return $this->isInteger($number) && $this->isInRange($number);
    }

    private function isInteger($number)
    {
        return is_int($number);
    }

    private function isInRange($number)
    {
        return $number >= self::MIN_SUPPORTED_VALUE && $number <= self::MAX_SUPPORTED_VALUE;
    }

    public function getNumber()
    {
        return $this->number;
    }

    private function setNumber($number)
    {
        $this->number = $number;
    }

	public function getWords()
    {
        return $this->words;
    }

    private function setWords($words)
    {
        $this->words = $words;
    }

    private function hasWord($number)
    {
        return isset(self::$WORDS[$number]);
    }

    private function getWord($number)
    {
        if ($this->hasWord($number)) {
            return self::$WORDS[$number];
        } else {
            throw new WordDoesNotExists;
        }
    }

    private function hasCase($magnitude, $type)
    {
        return isset(self::$CASES[$magnitude], self::$CASES[$magnitude][$type]);
    }

    private function hasCaseSuffix($type)
    {
        return isset(self::$CASE_SUFFIXES[$type]);
    }

    private function getCase($magnitude, $type)
    {
        if ($this->hasCase($magnitude, $type)) {
            return self::$CASES[$magnitude][$type];
        } elseif ($this->hasCaseSuffix($type)) {
            $number = pow(1000, $magnitude);
            if ($this->hasWord($number)) {
                return $this->getWord($number) . self::$CASE_SUFFIXES[$type];
            } else {
                throw new CaseDoesNotExists;
            }
        } else {
            throw new CaseDoesNotExists;
        }
    }

    private function asPhrase($words)
    {
        return implode(self::WORD_SEPARATOR, $words);
    }

    private function asWords($number)
    {
        $number = (string)$number;
        $words  = [];

        if ($this->hasWord($number)) {
            $words[] = $this->getWord($number);
        } else {
            for (
                $length = strlen($number),
                $incr   = 0,
                $decr   = $length - 1;
                $incr < $length;
                $incr++,
                $decr--
            ) {
                $digit      = (int)$number[$decr];
                $position   = $incr % 3;
                $order      = $digit * pow(10, $position);
                $magnitude  = floor($incr / 3);

                if ($position && $digit) {
                    if ($position === 1 && $digit === 1) {
                        $prev_digit = (int)$number[$decr + 1];
                        $ten        = $order + $prev_digit;
                        array_shift($words);
                        if ($magnitude && !$words) {
                            $words[] = $this->getCase($magnitude, 0);
                        }
                        if ($this->hasWord($ten)) {
                            $words[] = $this->getWord($ten);
                        } else {
                            $words[] = $this->getWord($prev_digit) . self::$SUFFIXES[0];
                        }
                    } elseif ($this->hasWord($order)) {
                        if ($magnitude && !$words) {
                            $words[] = $this->getCase($magnitude, 0);
                        }
                        $words[] = $this->getWord($order);

                    } elseif ($position === 1) {
                        if ($magnitude && !$words) {
                            $words[] = $this->getCase($magnitude, 0);
                        }
                        $words[] = $this->getWord($digit) . self::$SUFFIXES[1];

                    } elseif ($position === 2) {
                        if ($magnitude && !$words) {
                            $words[] = $this->getCase($magnitude, 0);
                        }
                        $words[] = $this->getWord($digit) . ($digit < 5 ? self::$SUFFIXES[2] : self::$SUFFIXES[3]);
                    }
                } elseif ($digit || ($position && $length === 1)) {
                    if ($magnitude) {
                        $words[] = $this->getCase($magnitude, (int)($digit < 5 && $digit !== 1));
                    }
                    $words[] = $this->getWord($digit);
                }
            }
        }

        return $this->asPhrase(array_reverse($words));
	}
}