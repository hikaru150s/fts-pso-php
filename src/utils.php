<?php
function baseLookup($interval) {
    return $interval <= 0.1 ? 0.1 : (10 ** ceil(log10($interval) - 1));
}
function averageInterval($dataset) {
    $range = array_filter(array_map(function($v, $i, $a) {
        return $i === 0 ? null : abs($v - $a[$i - 1]);
    }, $dataset, array_keys($dataset), array_fill(0, count($dataset), $dataset)), function($v) {
        return !is_null($v);
    });
    $rangeAverage = array_reduce($dataset, function($p, $c) {
        return $p + $c;
    }, 0) / count($range);
    $halfAverage = $rangeAverage / 2;
    return baseLookup($halfAverage);
}
function meanSquaredError($actual, $forecast) {
    if (count($actual) !== count($forecast)) {
        throw new Exception('Actual data and forecast data has different length');
    }
    return array_reduce(array_map(function($v, $i) {
        return ($v - $i) ** 2;
    }, $actual, $forecast), function($p, $c) {
        return $p + $c;
    }, 0) / count($actual);
}
function averageForecastingErrorRate($actual, $forecast) {
    if (count($actual) !== count($forecast)) {
        throw new Exception('Actual data and forecast data has different length');
    }
    return array_reduce(array_map(function($v, $i) {
        return abs($v - $i) / $v;
    }, $actual, $forecast), function($p, $c) {
        return $p + $c;
    }, 0) / count($actual);
}
