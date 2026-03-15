#!/usr/bin/env php
<?php
/**
 * Compare two benchmark result files produced by bench_runner.php --save.
 *
 * Usage:
 *   php bench_compare.php master.json io_copy.json
 *   php bench_compare.php master.json io_copy.json --threshold=5
 *   php bench_compare.php master.json io_copy.json --csv
 *
 * The first file is the baseline, the second is the candidate.
 * Speedups are shown as positive percentages, regressions as negative.
 */

require __DIR__ . '/bench_common.php';

/* ---- parse args ---- */

$positional = [];
$threshold  = 2.0;  /* minimum % change to highlight */
$csvMode    = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--threshold=')) {
        $threshold = (float) substr($arg, 12);
    } elseif ($arg === '--csv') {
        $csvMode = true;
    } elseif ($arg === '--help') {
        fwrite(STDOUT, <<<'HELP'
        Compare two stream_copy_to_stream benchmark runs.

        Usage:
          php bench_compare.php <baseline.json> <candidate.json> [options]

        Options:
          --threshold=N   Minimum % difference to highlight (default: 2)
          --csv           Output as CSV for import into a spreadsheet
          --help          Show this help

        The first file is the baseline, the second is the candidate.
        A positive "Change" means the candidate is faster (improvement).
        A negative "Change" means the candidate is slower (regression).

        HELP);
        exit(0);
    } elseif (!str_starts_with($arg, '-')) {
        $positional[] = $arg;
    }
}

if (count($positional) < 2) {
    fwrite(STDERR, "Usage: php bench_compare.php <baseline.json> <candidate.json>\n");
    exit(1);
}

/* ---- load data ---- */

$baseline  = bench_load($positional[0]);
$candidate = bench_load($positional[1]);

$bMeta = $baseline['meta'];
$cMeta = $candidate['meta'];
$bLabel = $bMeta['label'] ?: basename($positional[0], '.json');
$cLabel = $cMeta['label'] ?: basename($positional[1], '.json');

/* ---- header ---- */

if (!$csvMode) {
    echo "stream_copy_to_stream benchmark comparison\n";
    echo str_repeat('=', 90) . "\n";
    printf("%-12s  %-40s  %-40s\n", '', "BASELINE ($bLabel)", "CANDIDATE ($cLabel)");
    echo str_repeat('-', 90) . "\n";
    printf("%-12s  %-40s  %-40s\n", 'PHP',     $bMeta['php_version'], $cMeta['php_version']);
    printf("%-12s  %-40s  %-40s\n", 'Platform', $bMeta['platform'],   $cMeta['platform']);
    printf("%-12s  %-40s  %-40s\n", 'Date',     $bMeta['date'],       $cMeta['date']);
    printf("%-12s  %-40s  %-40s\n", 'Iters',    $bMeta['iterations'], $cMeta['iterations']);
    echo str_repeat('=', 90) . "\n\n";
}

/* ---- collect all variants ---- */

$allVariants = array_unique(array_merge(
    array_keys($baseline['results']),
    array_keys($candidate['results'])
));
sort($allVariants);

$variantLabels = [
    'file_to_file'     => 'file → file',
    'file_to_socket'   => 'file → socket',
    'socket_to_file'   => 'socket → file',
    'socket_to_socket' => 'socket → socket',
];

/* ---- CSV mode ---- */

if ($csvMode) {
    $cols = ['variant', 'size', 'baseline_avg_ms', 'baseline_mbps',
             'candidate_avg_ms', 'candidate_mbps', 'change_pct', 'verdict'];
    echo implode(',', $cols) . "\n";

    foreach ($allVariants as $variant) {
        $bSizes = $baseline['results'][$variant] ?? [];
        $cSizes = $candidate['results'][$variant] ?? [];
        $allSizes = array_unique(array_merge(array_keys($bSizes), array_keys($cSizes)));

        /* Sort sizes by byte count. */
        usort($allSizes, fn($a, $b) => ($bSizes[$a]['bytes'] ?? $cSizes[$a]['bytes'] ?? 0)
                                     <=> ($bSizes[$b]['bytes'] ?? $cSizes[$b]['bytes'] ?? 0));

        foreach ($allSizes as $size) {
            $b = $bSizes[$size] ?? null;
            $c = $cSizes[$size] ?? null;

            $bAvg  = $b ? $b['avg_s'] * 1000 : '';
            $bMbps = $b ? $b['mbps'] : '';
            $cAvg  = $c ? $c['avg_s'] * 1000 : '';
            $cMbps = $c ? $c['mbps'] : '';

            $changePct = '';
            $verdict   = '';
            if ($b && $c && $b['avg_s'] > 1e-12) {
                $ratio = (($b['avg_s'] - $c['avg_s']) / $b['avg_s']) * 100;
                $changePct = sprintf('%.1f', $ratio);
                if (abs($ratio) < $threshold)  $verdict = 'same';
                elseif ($ratio > 0)            $verdict = 'faster';
                else                           $verdict = 'slower';
            }

            printf("%s,%s,%.4f,%.2f,%.4f,%.2f,%s,%s\n",
                $variant, $size,
                $bAvg, $bMbps, $cAvg, $cMbps,
                $changePct, $verdict
            );
        }
    }
    exit(0);
}

/* ---- table mode ---- */

$summaryByVariant = [];

foreach ($allVariants as $variant) {
    $nice = $variantLabels[$variant] ?? $variant;
    echo "  $nice\n";
    printf(
        "  %-6s  │ %12s  %10s  │ %12s  %10s  │ %8s  %s\n",
        'Size',
        "$bLabel avg", 'MB/s',
        "$cLabel avg", 'MB/s',
        'Change', ''
    );
    echo '  ' . str_repeat('─', 84) . "\n";

    $bSizes = $baseline['results'][$variant] ?? [];
    $cSizes = $candidate['results'][$variant] ?? [];
    $allSizes = array_unique(array_merge(array_keys($bSizes), array_keys($cSizes)));

    usort($allSizes, fn($a, $b) => ($bSizes[$a]['bytes'] ?? $cSizes[$a]['bytes'] ?? 0)
                                 <=> ($bSizes[$b]['bytes'] ?? $cSizes[$b]['bytes'] ?? 0));

    $changes = [];

    foreach ($allSizes as $size) {
        $b = $bSizes[$size] ?? null;
        $c = $cSizes[$size] ?? null;

        $bTime = $b ? bench_format_time($b['avg_s']) : '      n/a';
        $bMbps = $b ? sprintf('%8.1f', $b['mbps']) : '     n/a';
        $cTime = $c ? bench_format_time($c['avg_s']) : '      n/a';
        $cMbps = $c ? sprintf('%8.1f', $c['mbps']) : '     n/a';

        $changeStr = '';
        $marker    = '';
        if ($b && $c && $b['avg_s'] > 1e-12) {
            /* Positive ratio = candidate is faster. */
            $ratio = (($b['avg_s'] - $c['avg_s']) / $b['avg_s']) * 100;
            $changes[] = $ratio;

            if (abs($ratio) < $threshold) {
                $changeStr = sprintf('%+6.1f%%', $ratio);
                $marker    = '';
            } elseif ($ratio > 0) {
                $changeStr = sprintf('%+6.1f%%', $ratio);
                $marker    = ' ▲';
            } else {
                $changeStr = sprintf('%+6.1f%%', $ratio);
                $marker    = ' ▼';
            }
        }

        printf(
            "  %-6s  │ %12s  %10s  │ %12s  %10s  │ %8s%s\n",
            $size,
            $bTime, $bMbps,
            $cTime, $cMbps,
            $changeStr, $marker
        );
    }

    /* Per-variant geometric mean of speedups. */
    if (!empty($changes)) {
        /* Use geometric mean of ratios: convert % to factor, geomean, back to %. */
        $factors = array_map(fn($pct) => 1.0 + ($pct / 100.0), $changes);
        $product = 1.0;
        foreach ($factors as $f) {
            $product *= max($f, 0.001); /* guard against zero/negative */
        }
        $geomean = pow($product, 1.0 / count($factors));
        $geomeanPct = ($geomean - 1.0) * 100;

        $summaryByVariant[$variant] = $geomeanPct;

        $gMarker = '';
        if (abs($geomeanPct) >= $threshold) {
            $gMarker = $geomeanPct > 0 ? ' ▲' : ' ▼';
        }
        echo '  ' . str_repeat('─', 84) . "\n";
        printf("  %-6s  │ %12s  %10s  │ %12s  %10s  │ %+7.1f%%%s  (geomean)\n",
            '', '', '', '', '',
            $geomeanPct, $gMarker
        );
    }

    echo "\n";
}

/* ---- overall summary ---- */

if (count($summaryByVariant) > 1) {
    echo str_repeat('=', 90) . "\n";
    echo "SUMMARY (geometric mean of speedup across all sizes)\n";
    echo str_repeat('-', 90) . "\n";
    printf("  %-20s  %10s  %s\n", 'Variant', 'Change', '');
    echo '  ' . str_repeat('─', 40) . "\n";

    $allFactors = [];
    foreach ($summaryByVariant as $variant => $geomeanPct) {
        $nice = $variantLabels[$variant] ?? $variant;
        $marker = '';
        if (abs($geomeanPct) >= $threshold) {
            $marker = $geomeanPct > 0 ? ' ▲' : ' ▼';
        }
        printf("  %-20s  %+9.1f%%%s\n", $nice, $geomeanPct, $marker);
        $allFactors[] = 1.0 + ($geomeanPct / 100.0);
    }

    $overallProduct = 1.0;
    foreach ($allFactors as $f) {
        $overallProduct *= max($f, 0.001);
    }
    $overallGeomean = pow($overallProduct, 1.0 / count($allFactors));
    $overallPct = ($overallGeomean - 1.0) * 100;

    echo '  ' . str_repeat('─', 40) . "\n";
    $oMarker = abs($overallPct) >= $threshold ? ($overallPct > 0 ? ' ▲' : ' ▼') : '';
    printf("  %-20s  %+9.1f%%%s\n", 'OVERALL', $overallPct, $oMarker);
    echo "\n";

    echo "Legend: ▲ = candidate is faster, ▼ = candidate is slower\n";
    echo "        threshold for highlighting: ±{$threshold}%\n";
}
