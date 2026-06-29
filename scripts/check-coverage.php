<?php
$cloverFile = 'storage/logs/clover.xml';
$threshold = 80;

if (!file_exists($cloverFile)) {
    echo "❌ Error: Clover report not found at $cloverFile" . PHP_EOL;
    exit(1);
}

$xml = simplexml_load_file($cloverFile);

$totalStatements = (int)$xml->project->metrics['statements'];
$totalCovered = (int)$xml->project->metrics['coveredstatements'];

$percentage = ($totalStatements > 0) ? ($totalCovered / $totalStatements) * 100 : 0;
$percentage = round($percentage, 2);

echo "--- Coverage Report ---" . PHP_EOL;

$layers = ['Services', 'Models', 'Http/Controllers'];
foreach ($layers as $layer) {
    $layerStatements = 0;
    $layerCovered = 0;
    foreach ($xml->xpath("//file[contains(@name, 'app/$layer/')]") as $file) {
        $layerStatements += (int)$file->metrics['statements'];
        $layerCovered += (int)$file->metrics['coveredstatements'];
    }
    $layerPercent = ($layerStatements > 0) ? round(($layerCovered / $layerStatements) * 100, 2) : 0;
    echo "📍 $layer: $layerPercent%" . PHP_EOL;
}

echo "-----------------------" . PHP_EOL;

if ($percentage < $threshold) {
    echo "❌ BUILD FAILED: Total Project coverage is {$percentage}%, below {$threshold}%." . PHP_EOL;
    exit(1);
}

echo "✅ BUILD PASSED: Total Project coverage is {$percentage}%." . PHP_EOL;
exit(0);
