<?php
// Reusable server-side gold chart utilities

function gold_valid_month(string $m): bool {
    if (!preg_match('/^\d{4}-\d{2}$/', $m)) return false;
    [$y, $mo] = array_map('intval', explode('-', $m));
    return $y >= 1970 && $mo >= 1 && $mo <= 12;
}

function gold_valid_karat(string $k): bool {
    return in_array($k, ['24','22','21','18','14','10'], true);
}

function gold_get_karat_factor(string $karat): float {
    static $factors = [
        '24' => 1.0,
        '22' => 0.916667,
        '21' => 0.875,
        '18' => 0.75,
        '14' => 0.583333,
        '10' => 0.416667
    ];
    return $factors[$karat] ?? 1.0;
}

/**
 * Returns normalized chart parameters
 * [
 *   month       => 'YYYY-MM',
 *   karat       => '24',
 *   karatFactor => float,
 *   firstDay    => 'YYYY-MM-DD',
 *   lastDay     => 'YYYY-MM-DD'
 * ]
 */
function gold_build_chart_params(array $src): array {
    $month = isset($src['month']) && gold_valid_month($src['month'])
        ? $src['month']
        : date('Y-m');

    $karat = isset($src['karat']) && gold_valid_karat($src['karat'])
        ? $src['karat']
        : '24';

    $firstDay = (new DateTimeImmutable($month . '-01'))->format('Y-m-d');
    $lastDay  = (new DateTimeImmutable($month . '-01'))
        ->modify('last day of this month')
        ->format('Y-m-d');

    return [
        'month'       => $month,
        'karat'       => $karat,
        'karatFactor' => gold_get_karat_factor($karat),
        'firstDay'    => $firstDay,
        'lastDay'     => $lastDay
    ];
}