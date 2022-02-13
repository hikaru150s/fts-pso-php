<?php
class FuzzyTriangleGate {
    private $min;
    private $mid;
    private $max;

    public function __construct($min, $mid, $max) {
        $this->min = $min;
        $this->mid = $mid;
        $this->max = $max;
    }
    public function getMedian() {
        return $this->mid;
    }
    public function degree($value) {
        return $value <= $this->min || $value >= $this->max ? 0 : ($this->min < $value && $value <= $this->mid ? (($value - $this->min) / ($this->mid - $this->min)) : (($this->max - $value) / ($this->max - $this->mid)));
    }
}
