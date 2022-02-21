<?php
class Particle {
    private $_positions;
    private $_velocities;
    private $_bestPositionIndex;
    private $_fittingResults;
    private $_fitFn;
    private $_selectFn;
    private $_selfConfidence;
    private $_weight;

    public function getBestPosition() {
        return $this->_positions[$this->_bestPositionIndex];
    }

    public function getBestFitResult() {
        return $this->_fittingResults[$this->_bestPositionIndex];
    }

    public function __construct($options) {
        $this->_fitFn = $options['fitFn'];
        $this->_selectFn = $options['selectorFn'];
        $this->_velocities = array($options['min'] + (rand(0, 1) * ($options['max'] - $options['min'])));
        $this->_positions = array($options['min'] + (rand(0, 1) * ($options['max'] - $options['min'])));
        $this->_selfConfidence = isset($options['selfConfidence']) ? $options['selfConfidence'] : rand(0, 1);
        $this->_bestPositionIndex = 0;
        $firstFit = $this->fit($this->_positions[0]);
        $this->_fittingResults = array($firstFit);
        $this->_weight = $options['weight'];
    }

    private function fit($position) {
        return $this->_fitFn->call($this, $position);
    }

    private function select() {
        return $this->_selectFn->call($this, $this->_fittingResults);
    }

    public function update($swarmConfidence, $globalBest) {
        $latestVelocity = $this->_velocities[count($this->_velocities) - 1];
        $latestPosition = $this->_positions[count($this->_positions) - 1];
        $localBest = $this->_positions[$this->_bestPositionIndex];
        $next = ($this->_weight * $latestVelocity) + ($this->_selfConfidence * rand(0, 1) * ($localBest - $latestPosition)) + ($swarmConfidence * rand(0, 1) * ($globalBest - $latestPosition));
        $this->_velocities[] = $next;
        $nextPosition = $latestPosition + $next;
        $this->_positions[] = $nextPosition;
        $nextFit = $this->fit($nextPosition);
        $this->_fittingResults[] = $nextFit;
        $this->_bestPositionIndex = $this->select();
    }
}
