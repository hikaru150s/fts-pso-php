<?php
require_once './FTS';
require_once './swarm';
require_once './utils';

function main() {
    $dataset = array(
        array('key' => '2016-Januari', 'value' => 1525945),
        array('key' => '2016-Februari', 'value' => 1526174),
        array('key' => '2016-Maret', 'value' => 1528229),
        array('key' => '2016-April', 'value' => 1527213),
        array('key' => '2016-Mei', 'value' => 1530175),
        array('key' => '2016-Juni', 'value' => 1529990),
        array('key' => '2016-Juli', 'value' => 1531731),
        array('key' => '2016-Agustus', 'value' => 1532102),
        array('key' => '2016-September', 'value' => 1532000),
        array('key' => '2016-Oktober', 'value' => 1534673),
        array('key' => '2016-November', 'value' => 1535980),
        array('key' => '2016-Desember', 'value' => 1555980),
        array('key' => '2017-Januari', 'value' => 1559132),
        array('key' => '2017-Februari', 'value' => 1562540),
        array('key' => '2017-Maret', 'value' => 1567909),
        array('key' => '2017-April', 'value' => 1570187),
        array('key' => '2017-Mei', 'value' => 1585009),
        array('key' => '2017-Juni', 'value' => 1593840),
        array('key' => '2017-Juli', 'value' => 1594601),
        array('key' => '2017-Agustus', 'value' => 1590321),
        array('key' => '2017-September', 'value' => 1585582),
        array('key' => '2017-Oktober', 'value' => 1587119),
        array('key' => '2017-November', 'value' => 1579000),
        array('key' => '2017-Desember', 'value' => 1573898),
        array('key' => '2018-Januari', 'value' => 1578689),
        array('key' => '2018-Februari', 'value' => 1575221),
        array('key' => '2018-Maret', 'value' => 1579991),
        array('key' => '2018-April', 'value' => 1574185),
        array('key' => '2018-Mei', 'value' => 1577540),
        array('key' => '2018-Juni', 'value' => 1580312),
        array('key' => '2018-Juli', 'value' => 1583221),
        array('key' => '2018-Agustus', 'value' => 1588139),
        array('key' => '2018-September', 'value' => 1589003),
        array('key' => '2018-Oktober', 'value' => 1587841),
        array('key' => '2018-November', 'value' => 1589187),
        array('key' => '2018-Desember', 'value' => 1592248),
        array('key' => '2019-Januari', 'value' => 1621641),
        array('key' => '2019-Februari', 'value' => 1618967),
        array('key' => '2019-Maret', 'value' => 1620082),
        array('key' => '2019-April', 'value' => 1622219),
        array('key' => '2019-Mei', 'value' => 1617298),
        array('key' => '2019-Juni', 'value' => 1615658),
        array('key' => '2019-Juli', 'value' => 1613935),
        array('key' => '2019-Agustus', 'value' => 1611112),
        array('key' => '2019-September', 'value' => 1613923),
        array('key' => '2019-Oktober', 'value' => 1616256),
        array('key' => '2019-November', 'value' => 1616990),
        array('key' => '2019-Desember', 'value' => 1619533),
        array('key' => '2020-Januari', 'value' => 1623146),
        array('key' => '2020-Februari', 'value' => 1628435),
        array('key' => '2020-Maret', 'value' => 1626213),
        array('key' => '2020-April', 'value' => 1632645),
        array('key' => '2020-Mei', 'value' => 1637908),
        array('key' => '2020-Juni', 'value' => 1641668),
        array('key' => '2020-Juli', 'value' => 1645758),
        array('key' => '2020-Agustus', 'value' => 1642932),
        array('key' => '2020-September', 'value' => 1659174),
        array('key' => '2020-Oktober', 'value' => 1667214),
        array('key' => '2020-November', 'value' => 1665546),
        array('key' => '2020-Desember', 'value' => 1668164),
    );
    $min = min(array_map(function($v) {
        return $v['value'];
    }, $dataset));
    $max = max(array_map(function($v) {
        return $v['value'];
    }, $dataset));
    $minBorder = $min * 0.1;
    $maxBorder = $max * 0.1;
    $engine = new FTS($dataset, array(
        'minMargin' => $minBorder,
        'maxMargin' => $maxBorder,
        'interval' => (($max * 1.1) - ($min * 0.9)) / 10,
    ));
    $engine->train();
    $singleResult = $engine->test();
    $forecasted = array_map(function($v) {
        return $v['predicted'];
    }, array_slice($singleResult, 0, count($singleResult) - 1));
    $actual = array_map(function($v) {
        return $v['value'];
    }, array_slice($singleResult, 1, count($singleResult) - 1));
    $mse = meanSquaredError($actual, $forecasted);
    $afer = averageForecastingErrorRate($actual, $forecasted);
    print_r($singleResult);
    echo "MSE: $mse";
    echo "AFER: $afer";

    $swarm = new Swarm(array(
        'spaces' => array('min' => 10, 'max' => 1000),
        'weight' => 0.1,
        'maxIteration' => 10000,
        'particleCount' => 100,
        'swarmConfidence' => 2,
        'selfConfidence' => 2,
        'fitnessFunction' => function ($n) {
            $subFts = new FTS($dataset, array(
                'minMargin' => $minBorder,
                'maxMargin' => $maxBorder,
                'interval' => (($max * 1.1) - ($min * 0.9)) / abs($n),
            ));
            $subFts->train();
            $subResult = $engine->test();
            $subForecasted = array_map(function($v) {
                return $v['predicted'];
            }, array_slice($subResult, 0, count($subResult) - 1));
            $subActual = array_map(function($v) {
                return $v['value'];
            }, array_slice($subResult, 1, count($subResult) - 1));
            return array(
                'mse' => meanSquaredError($subActual, $subForecasted),
                'afer' => averageForecastingErrorRate($subActual, $subForecasted),
            );
        },
        'selectorFunction' => function ($results) {
            $bestIndex = 0;
            for ($i = 1; $i < count($results); $i++) {
                if ($results[$i]['mse'] < $results[$bestIndex]['mse'] || $results[$i]['afer'] < $results[$bestIndex]['afer']) {
                    $bestIndex = $i;
                }
            }
            return $bestIndex;
        },
        'stopCriteria' => function ($n) {
            return $n['mse'] <= 10 || abs($n['afer']) < 0.000001;
        },
    ));
    $lastIteration = $swarm->optimize();
    echo 'Best interval count: ' + $swarm->getBestPosition();
    echo 'Iteration passed: ' + $lastIteration;
    $optimizedEngine = new FTS($dataset, array(
        'minMargin' => $minBorder,
        'maxMargin' => $maxBorder,
        'interval' => (($max * 1.1) - ($min * 0.9)) / $swarm->getBestPosition(),
    ));
    $optimizedEngine->train();
    $optimizedResult = $optimizedEngine->test();
    $optimizedForecast = array_map(function($v) {
        return $v['predicted'];
    }, array_slice($optimizedResult, 0, count($optimizedResult) - 1));
    $optimizedActual = array_map(function($v) {
        return $v['value'];
    }, array_slice($optimizedResult, 1, count($optimizedResult) - 1));
    $optimizedMse = meanSquaredError($optimizedActual, $optimizedForecast);
    $optimizedAfer = averageForecastingErrorRate($optimizedActual, $optimizedForecast);
    print_r($optimizedResult);
    echo "MSE: $optimizedMse";
    echo "AFER: $optimizedAfer";
}

try {
    main();
} catch (Exception $e) {
    echo 'Error:', $e->getMessage(), '\n';
}
