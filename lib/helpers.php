<?php

date_default_timezone_set('Asia/Kuala_Lumpur');

function getProgramStatusTimeBased(array $row): string
{
    if (($row['status'] ?? null) === 'Cancelled') {
        return 'Cancelled';
    }

    $startDate = $row['start_date'] ?? $row['tarikh'] ?? null;
    $endDate = $row['end_date'] ?? $row['tarikh'] ?? null;
    $startTime = $row['start_time'] ?? $row['masa'] ?? '00:00:00';
    $endTime = $row['end_time'] ?? $row['masa'] ?? '23:59:59';

    if (!$startDate) {
        return 'Upcoming';
    }
    if (!$endDate) {
        $endDate = $startDate;
    }

    $startDT = strtotime("$startDate $startTime");
    $endDT = strtotime("$endDate $endTime");
    $now = time();

    if ($now < $startDT) {
        return 'Upcoming';
    } elseif ($now >= $startDT && $now <= $endDT) {
        return 'Ongoing';
    } else {
        return 'Completed';
    }
}

function isProgramCompleted(array $row): bool
{
    return getProgramStatusTimeBased($row) === 'Completed';
}

function programStatusLabel(array|string|null $rowOrTarikh, ?string $dbStatus = null): string
{
    if (is_array($rowOrTarikh)) {
        return getProgramStatusTimeBased($rowOrTarikh);
    }
    return getProgramStatusTimeBased([
        'tarikh' => $rowOrTarikh,
        'status' => $dbStatus
    ]);
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

function getImagePath(?string $filename): string
{
    $fallback = 'UKM.png';

    if (!$filename) {
        return $fallback;
    }

    if (file_exists($filename)) {
        return $filename;
    }

    $paths = [
        "images/" . $filename,
        "images/events/" . $filename,
        $filename
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }

    if (str_starts_with($filename, 'uploads/')) {
        return $filename;
    }

    // Try a direct placeholder string if the user entered an unsplash URL
    if (str_starts_with($filename, 'http')) {
        return $filename;
    }

    return $fallback;
}

function formatProgramDates(?string $startDate, ?string $endDate, ?string $fallbackDate = null): string
{
    $start = $startDate ?: $fallbackDate;
    $end = $endDate ?: $fallbackDate;
    
    if (!$start) {
        return '';
    }
    
    $startStr = date('j M Y', strtotime($start));
    if (!$end || $start === $end) {
        return $startStr;
    }
    
    $endStr = date('j M Y', strtotime($end));
    return "$startStr - $endStr";
}

function formatProgramTimes(?string $startTime, ?string $endTime, ?string $fallbackTime = null): string
{
    $start = $startTime ?: $fallbackTime;
    $end = $endTime ?: $fallbackTime;
    
    if (!$start) {
        return '';
    }
    
    $startStr = date('g:i A', strtotime($start));
    if (!$end || $start === $end) {
        return $startStr;
    }
    
    $endStr = date('g:i A', strtotime($end));
    return "$startStr - $endStr";
}

