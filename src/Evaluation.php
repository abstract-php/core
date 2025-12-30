<?php

namespace Abstract;

use Abstract\Basic\Evaluator;

class Evaluation {

  public Evaluator $evaluator;

  public mixed $value;

  public function __construct(?Evaluator $evaluator = null) {
    if ($evaluator) {
      $this->evaluator = $evaluator;
    } else {
      $this->evaluator = new Evaluator();
    }
  }

  public function evaluate(Reference $reference): mixed {
    $key = $reference->getKey();
    $value = $reference->getValue();
    if ($key && isset($this->functions[$key])) {
      return $this->evaluator->functions[$key]($value);
    } else {
      return $value;
    }
  }
  
}