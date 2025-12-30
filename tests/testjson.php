<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// header('Content-Type: application/json; charset=utf-8');

require("./autoload.php");
require("../vendor/autoload.php");

use Abstract\Transformer\Mapper;
use Abstract\Abstraction;
use Abstract\Transformer\Resolver;
use Abstract\Transformer\Factory;
use Abstract\Supports\Scalar\ScalarFactory;
use Abstract\Supports\Markup\Transformer\MarkupParser;
use Abstract\Supports\Markup\Transformer\MarkupResolver;
use Abstract\Common\Convertor\Unicode;

$json = '{
    "name": "John Doe",
    "age": {
      ":integer": "30"
    },
    "cars": {
      "car1": "Ford",
      "car2": "BMW",
      "car3": "Fiat"
    },
    "colds": [
      { "id": 20 },
      { "idx": 21 }
    ]
  }';
$jsonObject = json_decode($json);
echo "Source:";
echo "\n";
echo json_encode($jsonObject);
echo "\n";

$sourceLength = strlen($json);

$normalizeAssociative = true;

$startAbstractionTime = microtime(true);

$abstraction = (new Abstraction)->parse($jsonObject, new ScalarFactory);

$endAbstractionTime = microtime(true);
$memoryAbstraction = memory_get_peak_usage();
memory_reset_peak_usage();

$startNormalizeTime = microtime(true);

// $value = $abstraction->getValue($normalizeAssociative);
$value = $abstraction->observe($normalizeAssociative);

$endNormalizeTime = microtime(true);
$memoryNormalize = memory_get_peak_usage();

$json = $abstraction->normalize($normalizeAssociative);

$jsonLength = strlen(json_encode($json));

$abstractionTime = $endAbstractionTime - $startAbstractionTime;
$normalizeTime = $endNormalizeTime - $startNormalizeTime;

echo "Source Strings:           " . str_pad($sourceLength, 8, " ", STR_PAD_LEFT) . " characters";
echo "\n";
echo "Abstract Strings:         " . str_pad($jsonLength, 8, " ", STR_PAD_LEFT) . " characters";
echo "\n";
echo "Abstraction Import Time:        " . str_pad(round($abstractionTime, 2), 8, " ", STR_PAD_LEFT) . " seconds";
echo "\n";
echo "Abstraction Export Time:        " . str_pad(round($normalizeTime, 2), 8, " ", STR_PAD_LEFT) . " seconds";
echo "\n";
echo "Total Time:               " . str_pad(round(($abstractionTime + $normalizeTime), 2), 8, " ", STR_PAD_LEFT) . " seconds";
echo "\n";
echo "Abstraction Import Memory Used: " . str_pad(round((($memoryAbstraction / 1024) / 1024), 2), 8, " ", STR_PAD_LEFT) . " MB";
echo "\n";
echo "Abstraction Export Memory Used: " . str_pad(round(($memoryNormalize / 1024) / 1024, 2), 8, " ", STR_PAD_LEFT) . " MB";
echo "\n";
echo "Total Memory Used:        " . str_pad(round((($memoryAbstraction + $memoryNormalize) / 1024) / 1024, 2), 8, " ", STR_PAD_LEFT) . " MB";
echo "\n";
echo "Result:";
echo "\n";
echo "\n";
echo $abstraction;
echo "\n";
