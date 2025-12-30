<?php

namespace Abstract\Registry;


class Source {

  public ?string $path;

  public function __construct(string $path) {
    $this->path = $path;
  }

  

}