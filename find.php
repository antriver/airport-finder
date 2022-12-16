<?php

/**
 * Finds all airports within a 500km radius of Eindhoven airport.
 *
 * It uses a list of all airports in the world that have an IATA code, found from here:
 * The list of airports is taken from https://github.com/lxndrblz/Airports
 *
 * It filters the list to all those airports which are within a 500km radius of Eindhoven (EIN) and outputs only
 * those lines.
 */

/**
 * Find the distance in km between two points.
 *
 * Source:
 * https://itecnote.com/tecnote/php-how-to-check-if-a-certain-coordinates-fall-to-another-coordinates-radius-using-php-only/
 *
 * @param float $latitude1
 * @param float $longitude1
 * @param float $latitude2
 * @param float $longitude2
 *
 * @return float
 */
function getDistance(float $latitude1, float $longitude1, float $latitude2, float $longitude2): float
{
    $earth_radius = 6371;

    $dLat = deg2rad($latitude2 - $latitude1);
    $dLon = deg2rad($longitude2 - $longitude1);

    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon / 2) * sin(
            $dLon / 2
        );
    $c = 2 * asin(sqrt($a));
    $d = $earth_radius * $c;

    return $d;
}

function areCoordinatesWithinRadius(float $lat1, float $lng1, float $lat2, float $lng2, int $radius): bool
{
    $distance = getDistance($lat1, $lng1, $lat2, $lng2);

    return $distance < $radius;
}

// Center point for the search - Eindhoven Airport.
$einLat = 51.4571023;
$einLng = 5.3926936;
$radius = 500;

// Read the list of all airports.
$airportsCsv = fopen(__DIR__.'/airports.csv', 'r');

// An array to hold the results.
$results = [];

$headers = null;

// Go through each line in the input list...
$i = 0;
while ($line = fgetcsv($airportsCsv)) {
    ++$i;
    if ($i === 1) {
        // This is the first line with the heades.
        $headers = $line;
        continue;
    }

    // The 5th column of the input contains a piece of text like "POINT (-75.42394572094523 6.16640765)"
    // Parse that string into lat and lng.
    $pointParts = explode(' ', $line[5]);
    $lng = trim($pointParts[1], '()');
    $lat = trim($pointParts[2], '()');

    // Check if this point is within our search radius.
    if (areCoordinatesWithinRadius($einLat, $einLng, $lat, $lng, $radius)) {
        // It is - add it to the results.
        $results[] = $line;
    }
}

// Sort the results by country code.
usort($results, function ($a, $b)
{
    return strcmp($a[4], $b[4]);
});

$output = fopen('php://output', 'w');

// Output the headers.
fputcsv($output, $headers);

// Output all the results.
foreach ($results as $result) {
    fputcsv($output, $result);
}

// Display a total count of the results.
// $count = count($results);
// echo "{$count} airports found withing {$radius}km of {$einLat}, {$einLng}.".PHP_EOL;
