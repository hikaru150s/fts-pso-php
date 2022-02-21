<?php
require_once 'particle.php';

class Swarm {
    private $_maxIteration;
    private $_particles;
    private $_fitFn;
    private $_selectFn;
    private $_stopCriteria;
    private $_bestFit;
    private $_bestPosition;
    private $_swarmConfidence;
    private $_w;

    public function getBestPosition() {
        return $this->_bestPosition;
    }

    public function getBestFit() {
        return $this->_bestFit;
    }

    public function __construct($options) {
        $this->_w = isset($options['weight']) && $options['weight'] > 0 && $options['weight'] < 1 ? $options['weight'] : rand(0, 1);
        $this->_maxIteration = $options['maxIteration'];
        $this->_fitFn = $options['fitnessFunction'];
        $this->_selectFn = $options['selectorFunction'];
        $this->_stopCriteria = $options['stopCriteria'];
        $this->_swarmConfidence = isset($options['swarmConfidence']) ? $options['swarmConfidence'] : rand(0, 1);
        $this->_particles = array();
        for ($i = 0; $i < $options['particleCount']; $i++) {
            $this->_particles[] = new Particle(array(
                'min' => $options['spaces']['min'],
                'max' => $options['spaces']['max'],
                'fitFn' => $this->_fitFn,
                'selectorFn' => $this->_selectFn,
                'selfConfidence' => $options['selfConfidence'],
                'weight' => $this->_w,
            ));
        }
        $bfr = [];
        foreach ($this->_particles as $v) {
            $bfr[] = $v->getBestFitResult();
        }
        $bestParticle = $this->_selectFn->call($this, $bfr);
        $this->_bestFit = $this->_particles[$bestParticle]->getBestFitResult();
        $this->_bestPosition = $this->_particles[$bestParticle]->getBestPosition();
    }

    public function optimize() {
        $stop = $this->_stopCriteria->call($this, $this->_bestFit);
        $currentIteration = 0;
        while (!$stop && $currentIteration < $this->_maxIteration) {
            echo "Current Iteration: $currentIteration\n";
            foreach ($this->_particles as $particle) {
                $particle->update($this->_swarmConfidence, $this->_bestPosition);
            }
            $bestParticle = $this->_selectFn->call($this, array_map(function($v) {
                return $v->getBestFitResult();
            }, $this->_particles));
            $this->_bestFit = $this->_particles[$bestParticle]->getBestFitResult();
            $this->_bestPosition = $this->_particles[$bestParticle]->getBestPosition();
            $stop = $this->_stopCriteria->call($this, $this->_bestFit);
            $currentIteration += 1;
        }
        return $currentIteration;
    }
}
