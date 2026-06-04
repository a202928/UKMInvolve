<?php

function programStatusLabel(?string $tarikh): string
{
    if (!$tarikh) {
        return 'Akan Datang';
    }

    $today = date('Y-m-d');
    if ($tarikh > $today) {
        return 'Akan Datang';
    }
    if ($tarikh < $today) {
        return 'Selesai';
    }
    return 'Aktif';
}

function programAvailability(int $participants, int $capacity): string
{
    return $participants >= $capacity ? 'full' : 'available';
}

function formatMalayDate(?string $isoDate): string
{
    if (!$isoDate) {
        return '';
    }

    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Mac', 4 => 'April',
        5 => 'Mei', 6 => 'Jun', 7 => 'Julai', 8 => 'Ogos',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Disember',
    ];

    $ts = strtotime($isoDate);
    if ($ts === false) {
        return $isoDate;
    }

    $day = (int) date('j', $ts);
    $month = $months[(int) date('n', $ts)] ?? date('F', $ts);
    $year = date('Y', $ts);

    return "$day $month $year";
}

function formatTimeRange(?string $masa): string
{
    if (!$masa) {
        return '';
    }

    $ts = strtotime($masa);
    if ($ts === false) {
        return $masa;
    }

    return date('g:i A', $ts);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? $text;
    return trim($text, '-');
}
