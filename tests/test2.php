<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require("./autoload.php");
require("../vendor/autoload.php");

use Abstract\Common\Convertor\StringCase;
use Abstract\Abstraction;
use Abstract\MapperBundle;
use Abstract\Mapper;
use Abstract\Parser;
use Abstract\ParserBundle;
use Abstract\Supports\Markup\Parser\MarkupParser;


$mapper = new Mapper();
$element = $mapper->function('test');
if (!is_null($element)) {
  $instance = $element('test');
  var_dump('-----');
  var_dump($instance);
  var_dump('-----');
  var_dump($instance->name);
  var_dump('-----');
}

// $markupParser = (
//   new MarkupParser(
//     new MapperBundle(
//       new Mapper(),
//     )
//   )
// )->markupFromFile('test.xml');


$jsonString = file_get_contents('pattern.json');

$object = json_decode($jsonString);
// $abstraction = Abstraction::parse(
//   new ManifestorCollection(
//     new CoreManifestor(),
//     new HtmlManifestor()
//   ),
//   $object,
// );

$mapper = new MapperBundle(
        
);

// $test = Abstraction::parse(
//   'test', 
//   new ParserBundle(
//     new MarkupParser($mapper)
//   ),
// );

// (new LogicIf())->then()->else();

$rule = '{
  "if": [
    { "===": [ { "var": "order.userId" }, { "var": "user.id" } ] },
    { "var": "user.name" },
    "User ID does not match"
  ]
}';
var_dump($rule);

$data = '{
  "user": {
    "id": 1,
    "name": "John Doe",
    "age": 30,
    "address": {
      "city": "New York",
      "zipcode": "10001"
    }
  },
  "order": {
    "id": 101,
    "item": "Laptop",
    "price": 1200,
    "userId": 1
  }
}';

$ast = '{
  "user": {
    "id": 1,
    "name": "John Doe",
    "age": 30,
    "address": {
      "city": "New York",
      "zipcode": "10001"
    }
    // children
    ":": {

    }
  },
  "order": {
    "id": 101,
    "item": "Laptop",
    "price": 1200,
    "userId": 1
  }
}';

$xxx = '{
  "user": {
    "id": 1,
    "name": "John Doe"
  }
}';

$xxx_with_props = '{
  "user": {
    "id": 1,
    "name": {
      ":value": "John Doe",
      ":properties": {
        "language": "english"
      }
    }
  }
}';

//Decode the JSON string to an object, and evaluate it.
$test = JWadhams\JsonLogic::apply(json_decode($rule), json_decode($data));


var_dump($rule);
var_dump($test);
// true

// ["==" => ["apples", "apples"]]

// var_dump($object);

// var_dump($abstraction);

// print_r($markup);
// $element = Element::fromDocument($markup);
// var_dump($element);


// $abstract = AbstractComponent;

// $dom = new DOMDocument('1.0', 'UTF-8');
// $dom->loadAML($markup);

// $markup = file_get_contents('test.html');


// print_r($markup);
// $element = Element::fromDocument($markup);
// var_dump($element);

// $abstraction = Abstraction()::fromObject();
// $abstraction = new Abstraction($abstract);

// $abstraction = new Abstraction();

// $abstraction = Abstraction::fromMarkup($markup);
// var_dump($abstraction);
// $abstraction->loadDOMDocument();

// $condition = new Condition('test');
// var_dump($condition->is('test')->not());

// $coreOptimizer = new CoreOptimizer();

// $abstraction = new Abstraction::fromJson($json, $optimizers = [new CoreManifestor(), new CoreManifestor()]);
// $abstraction = new Abstraction::fromMarkup($markup, $optimizers = [new CoreManifestor(), new CoreManifestor()]);

// $abstraction = new Abstraction(
//   new AbstractElement('test'),
//   new AbstractElement('test2'),
// );


// $abstraction = new Abstraction();
// $abstraction->Mapper->append();
// $abstraction::load(new MarkupSupport('test'));

// $abstraction = new Abstraction($json, $optimizers = [new CoreOptimizer(), new CoreOptimizer()]);

// if name is 'Patiparnne', surname is 'Vongchompue'
// if name is 'Patiparnne' / surname is 'Vongchompue': 'Yes!'
// :if(name is 'Patiparnne', surname is 'Vongchompue'): 'Yes!'
// :if(name is 'Patiparnne', surname is 'Vongchompue'): 'Yes!'
/* 
:if(name: :is('Patiparnne'), surname: :is('Vongchompue')): 'Yes!'
:if((name: :is('Patiparnne'), surname: :is('Vongchompue')) are true): 'Yes!'
:if: [{name: [:is('Patiparnne')], surname: [:is('Vongchompue')]}, ['Yes!']]
{
  ':if': {
    'name': {
      ':is': 'Patiparnne'
    }
  }
}
:if(:control(): [ { name: :is('Patiparnne') }, { surname: :is('Vongchompue') } ])
*/
// 
// 
// Activation
// Detector
// Compiler