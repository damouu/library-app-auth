<?php
$cloverFile = 'storage/logs/clover.xml';
$threshold = 4;
$targetNamespace = 'app/Services';

if (!file_exists($cloverFile)) {
    echo "❌ Error: Clover report not found at $cloverFile" . PHP_EOL;
    exit(1);
}

$xml = simplexml_load_file($cloverFile);
$totalStatements = 0;
$totalCovered = 0;

// Search for the Services directory in the XML
foreach ($xml->xpath('//package') as $package) {
    if (str_contains((string)$package['name'], 'App\Services') || str_contains((string)$package['name'], 'app/Services')) {
        $totalStatements += (int)$package->metrics['statements'];
        $totalCovered += (int)$package->metrics['coveredstatements'];
    }
}

if ($totalStatements === 0) {
    foreach ($xml->xpath("//file[contains(@name, 'app/Services/')]") as $file) {
        $totalStatements += (int)$file->metrics['statements'];
        $totalCovered += (int)$file->metrics['coveredstatements'];
    }
}

$percentage = ($totalStatements > 0) ? ($totalCovered / $totalStatements) * 100 : 0;
$percentage = round($percentage, 2);

if ($percentage < $threshold) {
    echo PHP_EOL . "❌ BUILD FAILED: Service Layer coverage is {$percentage}%, below the {$threshold}% threshold." . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "✅ BUILD PASSED: Service Layer coverage is at {$percentage}%. , above the {$threshold}% threshold." . PHP_EOL;
exit(0);
