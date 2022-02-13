<?php
require_once './fuzzy.php';

class FTS {
    private $_dataset;
    private $_minMargin;
    private $_maxMargin;
    private $_marginMultiplier;
    private $_partitionInterval;
    private $_partitionCount;
    private $_partitionRef;
    private $_ruleset;

    public function getMaxValue() {
        return max(array_map(function($v) { return $v['value']; }, $this->_dataset));
    }
    public function getMinValue() {
        return min(array_map(function($v) { return $v['value']; }, $this->_dataset));
    }
    public function getLowerBound() {
        return $this->minValue - $this->_minMargin;
    }
    public function getUpperBound() {
        return $this->maxValue + $this->_maxMargin;
    }
    public function getPartitionCount() {
        return $this->_partitionCount;
    }
    public function getPartitionLength() {
        return $this->_partitionInterval;
    }

    public function __construct($dataset, $options = null) {
        $this->_dataset = $dataset;
        $this->_marginMultiplier = isset($options) && isset($options['marginMultiplier']) ? $options['marginMultiplier'] : 0.1;
        $this->_minMargin = isset($options) && isset($options['minMargin']) ? $options['minMargin'] : ($this->minValue * $this->_marginMultiplier);
        $this->_maxMargin = isset($options) && isset($options['maxMargin']) ? $options['maxMargin'] : ($this->maxValue * $this->_marginMultiplier);
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
        $degrees = array_map(function($x) {
            return $x->degree($value);
        }, $this->_partitionRef);
        $highestDegree = max($degrees);
        return array_flip($degrees)[$highestDegree];
    }

    public function train() {
        $generatedPattern = array_map(function($v) {
            return $this->nearestPartition($v['value']);
        }, $this->_dataset);
        for ($i = 1; $i < count($generatedPattern); $i++) {
            $precedent = $generatedPattern[$i - 1];
            $consequent = $generatedPattern[$i];
            $this->_ruleset[$precedent][] = $consequent;
            $this->_ruleset[$precedent] = array_unique($this->_ruleset[$precedent]);
        }
    }

    public function test($options = null) {
        $baseData = isset($options) && isset($options['dataset']) ? $options['dataset'] : $this->_dataset;
        $predicted = array_map(function ($a) {
            $key = $a['key'];
            $value = $a['value'];
            $partitionIndex = $this->nearestPartition($value);
            $partitionConsequent = iseet($this->_ruleset[$partitionIndex]) ? $this->_ruleset[$partitionIndex] : array();
            $predictedValue = count($partitionConsequent) === 0 ?
                ($this->_partitionRef[$partitionIndex]->getMedian()) :
                (array_reduce(array_map(function ($x) {
                    return $this->_partitionRef[$x]->getMedian();
                }, $partitionConsequent), function($p, $c) {
                    return $p + $c;
                }, 0) / count($partitionConsequent));
            return array(
                'key' => $key,
                'value' => $value,
                'predicted' => $predictedValue,
            );
        }, $baseData);
        return $predicted;
    }
}
