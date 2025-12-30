<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// header('Content-Type: application/json; charset=utf-8');

require("./autoload.php");
require("../vendor/autoload.php");

use Abstract\Transformer\Mapper;
use Abstract\Abstraction;
use Abstract\Transformer\Parser;
use Abstract\Transformer\Resolver;
use Abstract\Supports\JsonLogic\Mapper\LogicMapper;

// optimized
$object = [
  'test' => [
    'xer' => [
      [
        'equal' => [99, 99]
      ],
      1,
      2
    ],
    'op' => 'linkedin',
  ],
  'test2' => [
    [
      'test



  กดหกหฟกกฟหหฟกส
  okay', ['sds' => 90], 'close'
    ],
    [
      'x:integer' => 9
    ]
  ],
  'if' => [
    [50, 59, [13, 23]],
    [
      'equal' => [1, 'g', 99.5],
      'etual' => ['a', 'b', 3],
    ],
    'facebook',
    'google'
  ],
  'asdsdsdsd' => [
    'fi' => [
      [
        'equal' => [99, 99]
      ],
      1,
      2
    ]
  ],
  'x:integer' => '9',
  'x:string' => 'Hello',
  'xxxx' => 1,
  'xxxxx' => [
    'facebook',
    'google',
    98999,
    [9998, 56565]
  ],
];

// $object = 55;

// $object = [
//   'test' => '55'
// ];
// $object2 = [];
// $object = [
//   'test' => [
//     'xer' => [
//       78,
//       []
//     ]
//   ],
// ];
// $object = [
//   'test' => [
//     'x:integer' => 9
//   ],
//   'test3' => [
//     'x:integer' => 9
//   ]
// ];
// $object2 = [
//   'test' => [
//     'x:integer' => 9
//   ],
// ];
// var_dump($object === $object2);

// var_dump([...$object2, ...$object]);

// $testtt= (array)$abstraction;



// array_reduce(
//   $array,
//   fn ($acc, $item) => var_dump(key($item)),
//   []
// );

// array_map(
//   fn ($item) => var_dump(key($item)),
//   $array
// );

// $object = ['echo' => ['x:string' => 'asdasdasd']];

$parser = new Parser;
$abstraction = $parser->adaptive($object);
// var_dump($abstraction);

// echo json_encode($object);
echo "\n";
echo $abstraction;
echo "\n";
// var_dump($abstraction);
// var_dump($abstraction);
// var_dump($abstraction);
// var_dump((new Resolver)->adaptive($abstraction));
$resolvedAbstraction = (new Resolver)->adaptive($abstraction);
// echo $abstraction;
echo $resolvedAbstraction;
