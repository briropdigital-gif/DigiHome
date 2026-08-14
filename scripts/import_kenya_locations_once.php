<?php
require_once dirname(__DIR__) . '/includes/db.config.php';

$datasetPath = dirname(__DIR__) . '/data/kenya_counties_subcounties_wards_official.json';
if (!is_file($datasetPath) || !is_readable($datasetPath)) {
    fwrite(STDERR, "Dataset file not found or unreadable: {$datasetPath}\n");
    exit(1);
}

$db = connect_db();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$raw = file_get_contents($datasetPath);
$data = json_decode((string) $raw, true);
if (!is_array($data) || !isset($data['counties']) || !is_array($data['counties'])) {
    fwrite(STDERR, "Invalid dataset format.\n");
    exit(1);
}

function normalize_cli_location_name($value) {
    $name = trim((string) $value);
    if ($name === '') {
        return '';
    }

    $name = preg_replace('/\s+/', ' ', $name);
    $name = strtolower($name);
    return preg_replace_callback('/(^|[\s\/\-\(])([a-z])/', static function ($m) {
        return $m[1] . strtoupper($m[2]);
    }, $name);
}

$countryId = find_or_create_country_id('Kenya');
if ((int) $countryId <= 0) {
    fwrite(STDERR, "Could not resolve country Kenya.\n");
    exit(1);
}

$countyCount = 0;
$subCountyCount = 0;
$wardCount = 0;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db->begin_transaction();

    foreach ($data['counties'] as $countyEntry) {
        if (!is_array($countyEntry)) {
            continue;
        }

        $countyName = normalize_cli_location_name($countyEntry['county_name'] ?? '');
        if ($countyName === '') {
            continue;
        }

        $countyId = find_or_create_county_id((int) $countryId, $countyName);
        if ((int) $countyId <= 0) {
            throw new RuntimeException('County resolve failed: ' . $countyName);
        }
        $countyCount++;

        $constituencies = isset($countyEntry['constituencies']) && is_array($countyEntry['constituencies'])
            ? $countyEntry['constituencies']
            : [];

        foreach ($constituencies as $constituencyEntry) {
            if (!is_array($constituencyEntry)) {
                continue;
            }

            $subCountyName = normalize_cli_location_name($constituencyEntry['constituency_name'] ?? '');
            if ($subCountyName === '') {
                continue;
            }

            $subCountyId = find_or_create_sub_county_id((int) $countyId, $subCountyName);
            if ((int) $subCountyId <= 0) {
                throw new RuntimeException('Sub-county resolve failed: ' . $countyName . ' > ' . $subCountyName);
            }
            $subCountyCount++;

            $wards = isset($constituencyEntry['wards']) && is_array($constituencyEntry['wards'])
                ? $constituencyEntry['wards']
                : [];

            foreach ($wards as $wardEntry) {
                $wardName = '';
                if (is_array($wardEntry)) {
                    $wardName = normalize_cli_location_name($wardEntry['ward_name'] ?? '');
                } elseif (is_string($wardEntry)) {
                    $wardName = normalize_cli_location_name($wardEntry);
                }

                if ($wardName === '') {
                    continue;
                }

                $wardId = find_or_create_ward_id((int) $subCountyId, $wardName);
                if ((int) $wardId <= 0) {
                    throw new RuntimeException('Ward resolve failed: ' . $countyName . ' > ' . $subCountyName . ' > ' . $wardName);
                }
                $wardCount++;
            }
        }
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    fwrite(STDERR, 'Import failed: ' . $e->getMessage() . "\n");
    exit(1);
}

$totals = [
    'counties' => 0,
    'sub_counties' => 0,
    'wards' => 0,
];

$countiesResult = $db->query('SELECT COUNT(*) AS total FROM counties');
if ($countiesResult) {
    $totals['counties'] = (int) (($countiesResult->fetch_assoc()['total'] ?? 0));
}

$subCountiesResult = $db->query('SELECT COUNT(*) AS total FROM sub_counties');
if ($subCountiesResult) {
    $totals['sub_counties'] = (int) (($subCountiesResult->fetch_assoc()['total'] ?? 0));
}

$wardsResult = $db->query('SELECT COUNT(*) AS total FROM wards');
if ($wardsResult) {
    $totals['wards'] = (int) (($wardsResult->fetch_assoc()['total'] ?? 0));
}

$backfilled = (int) backfill_location_hierarchy_from_properties();

printf("Processed dataset: counties=%d, sub_counties=%d, wards=%d\n", $countyCount, $subCountyCount, $wardCount);
printf("Database totals: counties=%d, sub_counties=%d, wards=%d\n", $totals['counties'], $totals['sub_counties'], $totals['wards']);
printf("Backfilled properties: %d\n", $backfilled);
