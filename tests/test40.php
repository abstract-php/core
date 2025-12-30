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
use Abstract\Transformer\Factory;
use Abstract\Supports\Markup\Transformer\MarkupParser;
use Abstract\Supports\Dom\Transformer\DomParser;
use Abstract\Supports\Markup\Transformer\MarkupResolver;
use Abstract\Common\Convertor\Unicode;

use Abstract\Common\Taxonomy\Type;

$markup = file_get_contents('testdom.html');

$sourceLength = strlen($markup);

$normalizeAssociative = null;

$startAbstractionTime = microtime(true);

// Create a new DOMDocument
$document = new \DOMDocument('1.0', 'UTF-8');

// Preserve whitespace and format
$document->preserveWhiteSpace = true;
$document->formatOutput = true;
echo Type::of($document);

$result = $document->loadHTML($markup);

$parser = new DomParser();
$abstraction = $parser->document($document, true);

$endAbstractionTime = microtime(true);
$memoryAbstraction = memory_get_peak_usage();
memory_reset_peak_usage();

$startNormalizeTime = microtime(true);

$value = $abstraction->getValue($normalizeAssociative);

$endNormalizeTime = microtime(true);
$memoryNormalize = memory_get_peak_usage();

$json = $abstraction->toJson($normalizeAssociative);

$jsonLength = strlen($json);

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
// echo "\n";
// var_dump($abstraction);
echo "\n";
var_dump($abstraction->getValue($normalizeAssociative));
echo "\n";
echo $abstraction;
echo "\n";
// $resolvedMarkupAbstraction = (new MarkupResolver)->markup($abstraction, true);
// echo $resolvedMarkupAbstraction;
// echo "\n";
// $resolvedAbstraction = (new Resolver)->adaptive($abstraction);
// echo $resolvedAbstraction;