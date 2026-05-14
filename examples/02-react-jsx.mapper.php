<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use AbstractLang\AbstractCore;
use AbstractLang\Emitter\JsxEmitter;
use AbstractLang\Mapper\ReactMapper;

$core = new AbstractCore();
$tree = $core->parseJsonFile(example_path('02-react-jsx.source.json'));
$resolved = $core->resolve($tree);

$jsx = (new JsxEmitter())->emit((new ReactMapper())->map($resolved));

example_print('React/JSX-like Output', $jsx);
