<?php

namespace Oliverde8\PhpEtlBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TimeIntervalExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('timeSpent', [$this, 'formatTime']),
        ];
    }

    public function formatTime($time)
    {

        $time = abs($time);
        $sec = str_pad($time % 60, 2, '0', STR_PAD_LEFT);
        $min = str_pad(floor($time / 60), 2, '0', STR_PAD_LEFT);
        $hour = str_pad(floor($time / 60 / 60), 1, '0');

        $formattedPieces = [];
        if ($time > (60*60)) {
            $formattedPieces[] = $hour . "h";
        }
        if ($time > (60)) {
            $formattedPieces[] = $min . "m";
        }
        $formattedPieces[] = $sec . "s";

        return implode(" ", $formattedPieces);
    }
}