<?php
require_once 'fuzzy.php';

class FTS {
    private $_dataset;
    private $_minMargin;
    private $_maxMargin;
    private $_marginMultiplier;
    private $_partitionInterval;
    private $_partitionCount;
    private $_partitionRef;
    private $_ruleset;

    private $_maxVal;
    private $_minVal;
    private $_lb;
    private $_ub;

    private $_memo;

    public function getMaxValue() {
        return $this->_maxVal;
    }
    public function getMinValue() {
        return $this->_minVal;
    }
    public function getLowerBound() {
        return $this->_lb;
    }
    public function getUpperBound() {
        return $this->_ub;
    }
    public function getPartitionCount() {
        return $this->_partitionCount;
    }
    public function getPartitionLength() {
        return $this->_partitionInterval;
    }

    public function __construct($dataset, $options = null) {
        $this->_memo = [];
        $this->_dataset = $dataset;
        $this->_maxVal = isset($options) && isset($options['_maxVal']) ? $options['_maxVal'] : max(array_map(function($v) { return $v['value']; }, $this->_dataset));
        $this->_minVal = isset($options) && isset($options['_minVal']) ? $options['_minVal'] : min(array_map(function($v) { return $v['value']; }, $this->_dataset));
        $this->_marginMultiplier = isset($options) && isset($options['marginMultiplier']) ? $options['marginMultiplier'] : 0.1;
        $this->_minMargin = isset($options) && isset($options['minMargin']) ? $options['minMargin'] : ($this->minValue * $this->_marginMultiplier);
        $this->_maxMargin = isset($options) && isset($options['maxMargin']) ? $options['maxMargin'] : ($this->maxValue * $this->_marginMultiplier);
        $this->_lb = $this->_minVal - $this->_minMargin;
        $this->_ub = $this->_maxVal + $this->_maxMargin;
        if (isset($options) && isset($options['interval'])) {
            $this->_partitionInterval = $options['interval'];
            $this->_partitionCount = ceil(($this->getUpperBound() - $this->getLowerBound()) / $options['interval']);
        } else {
            $this->_partitionCount = isset($options) && isset($options['partitionCount']) ? $options['partitionCount'] : 10;
            $this->_partitionInterval = ($this->getUpperBound() - $this->getLowerBound()) / $this->_partitionCount;
        }
        $this->_partitionRef = array();
        $this->_ruleset = array();
        for ($i = 0; $i < $this->_partitionCount; $i++) {
            $prevPoint = $i === 0 ? 0 : ($this->getLowerBound() + ($this->_partitionInterval * ($i - 1)));
            $maxPoint = $this->getLowerBound() + ($this->_partitionInterval * $i);
            $nextPoint = $this->getLowerBound() + ($this->_partitionInterval * ($i + 1));
            $this->_partitionRef[] = new FuzzyTriangleGate($prevPoint, $maxPoint, $nextPoint);
            $this->_ruleset[$i] = array();
        }
    }

    private function nearestPartition($value) {
        if (!isset($this->_memo[$value])) {
            $_h = 0;
            $_hi = 0;
            $len = count($this->_partitionRef);
            for ($i = 0; $i < $len; $i++) {
                $d = $this->_partitionRef[$i]->degree($value);
                if ($d > $_h) {
                    $_h = $d;
                    $_hi = $i;
                }
            }
            $this->_memo[$value] = $_hi;
        }
        return $this->_memo[$value];
    }

    public function train() {
        $generatedPattern = [];
        $len = count($this->_dataset);
        for ($i = 0; $i < $len; $i++) {
            $generatedPattern[] = $this->nearestPartition($this->_dataset[$i]['value']);
            if ($i > 0) {
                $precedent = $generatedPattern[$i - 1];
                $consequent = $generatedPattern[$i];
                $this->_ruleset[$precedent][] = $consequent;
                $this->_ruleset[$precedent] = array_unique($this->_ruleset[$precedent]);
            }
        }
    }

    public function test($options = null) {
        $baseData = isset($options) && isset($options['dataset']) ? $options['dataset'] : $this->_dataset;
        $predicted = array_map(function ($a) {
            $key = $a['key'];
            $value = $a['value'];
            $partitionIndex = $this->nearestPartition($value);
            $partitionConsequent = isset($this->_ruleset[$partitionIndex]) ? $this->_ruleset[$partitionIndex] : array();
            $partitionConsequentLen = count($partitionConsequent);
            $predictedValue = $partitionConsequentLen === 0 ?
                ($this->_partitionRef[$partitionIndex]->getMedian()) :
                (array_reduce(array_map(function ($x) {
                    return $this->_partitionRef[$x]->getMedian();
                }, $partitionConsequent), function($p, $c) {
                    return $p + $c;
                }, 0) / $partitionConsequentLen);
            return array(
                'key' => $key,
                'value' => $value,
                'predicted' => $predictedValue,
            );
        }, $baseData);
        return $predicted;
    }
}
