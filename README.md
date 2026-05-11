# Abstract PHP Core

A comprehensive PHP framework for data abstraction, transformation, and multi-format parsing. Provides a unified Abstraction layer that can parse, transform, and normalize data from various sources (HTML/XML markup, JSON, arrays, objects) into consistent structured representations.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Core Concepts](#core-concepts)
  - [Abstraction](#abstraction)
  - [Reference](#reference)
  - [Value](#value)
  - [Evaluation](#evaluation)
- [Transformer Module](#transformer-module)
  - [Factory](#factory)
  - [Resolver](#resolver)
  - [Observer](#observer)
- [Supports Modules](#supports-modules)
  - [Markup Parser](#markup-parser)
  - [DOM Parser](#dom-parser)
  - [Scalar Factory](#scalar-factory)
  - [JSON Logic](#json-logic)
  - [YAML Support](#yaml-support)
  - [CSV Support](#csv-support)
  - [SQL Support](#sql-support)
  - [Storage Support](#storage-support)
- [Common Utilities](#common-utilities)
- [Registry](#registry)
- [Exceptions](#exceptions)
- [Usage Examples](#usage-examples)
- [Project Structure](#project-structure)

## Features

- **Unified Abstraction Layer**: Parse and represent data from multiple formats (HTML, XML, JSON, arrays, objects) using a consistent `Abstraction` tree structure
- **Multi-Format Parsing**: Built-in parsers for Markup (HTML/XML), DOM, CSV, YAML, and SQL
- **Type Transformation**: Automatic type coercion and validation (string, integer, float, boolean, array, object, null)
- **Associative/Sequential Processing**: Smart handling of associative arrays vs indexed lists with configurable strategies
- **Unicode Support**: Full Unicode tag/attribute name encoding and decoding for international character support
- **Extensible Architecture**: Factory pattern with composable parsers, resolvers, and evaluators
- **JSON Output**: Native JSON serialization with pretty-print support

## Installation

### Composer (in progress)

```bash
composer require abstract/core
```

### Manual Installation

For now, include the autoload file in your project:

```php
require_once '/path/to/core/autoload.php';
```

Or use the vendor autoloader if composer install has been run:

```php
require_once '/path/to/core/vendor/autoload.php';
```

## Core Concepts

### Abstraction

The `Abstraction` class is the heart of this framework. It wraps data with metadata including:

- **Key/Name**: An identifier for the abstraction node
- **Argument**: The actual value being wrapped
- **Children**: Child abstraction nodes (tree structure)
- **Associative Flag**: Determines if children should be treated as key-value pairs or indexed list
- **Depth**: Nesting level in the tree

```php
use Abstract\Abstraction;
use Abstract\Reference;

// Create from a scalar value
$abstraction = new Abstraction(new Reference('string', 'hello world'));

// Create with children
$parent = new Abstraction();
$child1 = (new Abstraction())->withName('name')->withArgument('John');
$child2 = (new Abstraction())->withName('age')->withArgument(30);
$parent->attach(true, $child1, $child2);

// Convert to JSON
echo $parent->toJson(true, true);
```

#### Key Methods

| Method | Description |
|--------|-------------|
| `withName(string $name)` | Set the name/key of this abstraction |
| `withArgument(mixed $value)` | Set the argument/value |
| `attach(bool $associative, Abstraction ...$children)` | Attach child abstractions |
| `detach()` | Remove all children |
| `append/prepend(Abstraction ...$children)` | Add children to end/beginning |
| `list()` | Get all child abstractions |
| `get(string $name)` | Find child by name |
| `getName()/setName()` | Get/set the node name |
| `getArgument()/hasArgument()` | Get/check if has a value |
| `normalize(?bool $associative)` | Convert to native PHP structure |
| `toJson(?bool $associative, bool $prettyPrint)` | Convert to JSON string |
| `parse(mixed $source)` | Parse any source into abstraction tree |

### Reference

The `Reference` class holds the key-value pair with additional metadata:

- **Key**: The identifier (with optional indicator prefix)
- **Value**: The actual data
- **Associative**: Whether this reference represents key-value pairs
- **Depth**: Nesting level
- **Indicator**: Prefix character for named abstractions (e.g., `:`)

### Value

The `Value` class manages the resource representation of an abstraction and its children's values.

### Evaluation

The `Evaluation` class handles evaluation and processing of abstraction trees.

## Transformer Module

The Transformer module provides data transformation capabilities through factory, resolver, and observer patterns.

### Factory

The `Factory` class creates abstraction instances for different types. It maintains a registry of callable functions that produce abstractions from values.

```php
use Abstract\Transformer\Factory;

$factory = new Factory();
// Functions in factory can create typed abstractions
```

### Resolver

The `Resolver` class transforms abstraction trees into native PHP values by applying type-specific transformation functions:

```php
use Abstract\Transformer\Resolver;

$resolver = new Resolver();
$resolved = $resolver->adaptive($abstraction);
```

#### Built-in Type Methods

| Method | Description |
|--------|-------------|
| `null(Abstraction $abstraction)` | Convert to null |
| `integer(Abstraction $abstraction)` | Convert to integer with validation |
| `double/float(Abstraction $abstraction)` | Convert to float with validation |
| `string(Abstraction $abstraction)` | Convert to string |
| `boolean(Abstraction $abstraction)` | Convert to boolean with validation |
| `array(Abstraction $abstraction)` | Convert to indexed array |
| `object(Abstraction $abstraction)` | Convert to associative array/object |

### Observer

The `Observer` class provides adaptive transformation without modifying the original abstraction. It's a lighter-weight alternative to Resolver:

```php
use Abstract\Transformer\Observer;

$observer = new Observer();
$value = $observer->adaptive('string', $rawValue); // Returns typed value
```

## Supports Modules

### Markup Parser

The `MarkupParser` parses HTML/XML markup strings into Abstraction trees using PHP's DOM extension:

```php
use Abstract\Supports\Markup\Parser\MarkupParser;

$html = '<div class="container"><p>Hello</p></div>';
$abstraction = MarkupParser::string($html);

// Parse from file
$abstraction = MarkupParser::file('path/to/file.html');
```

#### Features

- **Unicode Tag Support**: Encodes international tag names and attributes using numeric entity encoding
- **Self-Closing Tags**: Automatic detection of HTML5 self-closing tags (`br`, `img`, `input`, etc.)
- **Comment Parsing**: Extracts HTML comments as named abstraction nodes
- **Attribute Handling**: Parses attributes including namespaced (`:`) and dotted (`.`) notation
- **Associative Detection**: Automatically determines if elements should produce associative arrays or lists

#### Special Tag Prefixes

| Prefix | Purpose |
|--------|---------|
| `:string` | Force string type interpretation |
| `:int/:integer` | Force integer type interpretation |
| `:bool/:boolean` | Force boolean type interpretation |
| `:comment` | Comment node marker |
| `:text` | Text node marker |
| `:root` | Root document marker |

### DOM Parser

The `DomParser` provides base DOM parsing functionality used by the MarkupParser:

```php
use Abstract\Supports\Dom\Parser\DomParser;

$parser = new DomParser();
$abstraction = $parser->domDocument($domDocument);
```

#### Key Methods

| Method | Description |
|--------|-------------|
| `domDocument(DOMDocument $source)` | Parse entire DOM document |
| `domNode(DOMDocument $doc, DOMNode $source)` | Parse individual DOM node |
| `domChildren(DOMDocument $doc, DOMNodeList $source)` | Parse child node list |
| `domAttributes(DOMDocument $doc, DOMNamedNodeMap $source)` | Parse attributes |
| `encapsulateComment()` | Wrap comment nodes for parsing |
| `encapsulateString()` | Wrap text nodes for parsing |

### Scalar Factory

The `ScalarFactory` provides static methods for creating typed scalar references:

```php
use Abstract\Supports\Scalar\ScalarFactory;

$ref = ScalarFactory::string('hello');
$ref = ScalarFactory::integer(42);
$ref = ScalarFactory::boolean(true);
```

### JSON Logic

The `JsonLogic` module provides JSON Logic support for rule-based evaluations:

```php
use Abstract\Supports\JsonLogic\Components\Logic;
use Abstract\Supports\JsonLogic\Mapper\LogicMapper;
```

### YAML Support

YAML parsing and generation capabilities located in `src/Supports/Yaml/`.

### CSV Support

CSV reading and writing with transformer components in `src/Supports/Csv/`.

### SQL Support

SQL query building and database interaction utilities in `src/Supports/Sql/`.

### Storage Support

Abstract storage layer with local file system implementation:

```php
use Abstract\Supports\Storage\Factory\StorageFactory;
```

## Common Utilities

### StringCase

String case conversion utilities:

```php
use Abstract\Common\Convertor\StringCase;

$camel = StringCase::toCamelCase('hello_world'); // helloWorld
```

### Unicode

Unicode encoding/decoding for tag names and attributes:

```php
use Abstract\Common\Convertor\Unicode;

$encoded = Unicode::fromString('tagName');
$decoded = Unicode::toString($encoded);
```

### Type

Type detection utility:

```php
use Abstract\Common\Taxonomy\Type;

$type = Type::of($value); // 'string', 'integer', 'object', 'null', etc.
```

## Registry

Configuration and source management:

- `Configuration` - Framework configuration settings
- `Source` - Source file/path management

## Exceptions

Custom exception classes:

| Exception | Purpose |
|-----------|---------|
| `ActivationException` | Activation-related errors |
| `DocumentException` | Document parsing errors |
| `ElementException` | Element manipulation errors |

## Usage Examples

### Example 1: Parse HTML to JSON

```php
<?php
require_once 'vendor/autoload.php';

use Abstract\Supports\Markup\Parser\MarkupParser;
use Abstract\Transformer\Resolver;

$html = file_get_contents('tests/test.html');

// Parse HTML into Abstraction tree
$abstraction = MarkupParser::string($html, true);

// Convert to JSON
$json = $abstraction->toJson(true, true);
echo $json;

// Or get native PHP structure
$data = $abstraction->getValue(true);
var_dump($data);
```

### Example 2: Parse and Transform Data

```php
<?php
require_once 'vendor/autoload.php';

use Abstract\Abstraction;
use Abstract\Transformer\Resolver;

// Create abstraction from array data
$abstraction = new Abstraction();
$data = [
    'name' => 'John Doe',
    'age' => 30,
    'active' => true,
    'scores' => [95, 87, 92]
];

$abstraction = $abstraction->parse($data);

// Resolve to typed values
$resolver = new Resolver();
$resolved = $resolver->adaptive($abstraction);

echo $resolved->toJson(true, true);
```

### Example 3: Custom Factory with Type Markers

```php
<?php
require_once 'vendor/autoload.php';

use Abstract\Supports\Markup\Parser\MarkupParser;
use Abstract\Transformer\Factory;
use Abstract\Supports\Scalar\ScalarFactory;

// Create custom factory with type markers
$factory = new Factory();
$factory->functions['string'] = fn() => ScalarFactory::string('default');
$factory->functions['int'] = fn() => ScalarFactory::integer(0);

// Parse with custom factory
$html = '<:string>hello</:string><:int>42</:int>';
$abstraction = MarkupParser::string($html, true, $factory);

echo $abstraction->toJson(true, true);
```

### Example 4: Query Abstraction Tree

```php
<?php
require_once 'vendor/autoload.php';

use Abstract\Supports\Markup\Parser\MarkupParser;

$html = '<div><p class="intro">Hello</p><p>World</p></div>';
$abstraction = MarkupParser::string($html);

// Find all paragraph elements
$paragraphs = $abstraction->get('p');

foreach ($paragraphs as $para) {
    echo $para->getArgument() . "\n";
}
```

## Project Structure

```
core/
├── src/
│   ├── Abstraction.php          # Core abstraction class
│   ├── Association.php          # Association handling
│   ├── Evaluation.php           # Evaluation engine
│   ├── Reference.php            # Reference class (key-value)
│   ├── Source.php               # Source abstraction
│   ├── Template.php             # Template base class
│   ├── Value.php                # Value/resource management
│   │
│   ├── Basic/
│   │   └── Evaluator.php        # Basic evaluator (type coercion)
│   │
│   ├── Common/
│   │   ├── Convertor/
│   │   │   ├── StringCase.php   # Case conversion utilities
│   │   │   └── Unicode.php      # Unicode encoding/decoding
│   │   └── Taxonomy/
│   │       └── Type.php         # Type detection
│   │
│   ├── Exceptions/
│   │   ├── ActivationException.php
│   │   ├── DocumentException.php
│   │   └── ElementException.php
│   │
│   ├── Handlers/
│   │   ├── ExceptionHandler.php
│   │   ├── Exceptions.php
│   │   └── NameHandler.php
│   │
│   ├── Registry/
│   │   └── Source.php           # Source configuration
│   │
│   ├── Supports/
│   │   ├── Csv/                 # CSV parsing support
│   │   │   └── Components/Csv.php
│   │   ├── Dom/                 # DOM parsing support
│   │   │   ├── DomAbstract.php
│   │   │   ├── DomAttributeAbstract.php
│   │   │   ├── DomAttributeListAbstract.php
│   │   │   ├── DomCommentAbstract.php
│   │   │   └── Parser/DomParser.php
│   │   ├── JsonLogic/           # JSON Logic support
│   │   │   ├── Components/Logic.php
│   │   │   └── Mapper/LogicMapper.php
│   │   ├── Markup/              # Markup (HTML/XML) support
│   │   │   ├── MarkupAbstract.php
│   │   │   ├── MarkupAttributeAbstract.php
│   │   │   ├── MarkupAttributeListAbstract.php
│   │   │   ├── MarkupCommentAbstract.php
│   │   │   ├── MarkupRootAbstract.php
│   │   │   ├── MarkupTextAbstract.php
│   │   │   └── Parser/MarkupParser.php
│   │   ├── Native/              # Native PHP factory
│   │   │   └── Factory/NativeFactory.php
│   │   ├── Scalar/              # Scalar type factory
│   │   │   ├── ScalarFactory.php
│   │   │   └── ScalarObsever.php
│   │   ├── Sql/                 # SQL support (in progress)
│   │   ├── Storage/             # Storage abstraction
│   │   │   └── Factory/StorageFactory.php
│   │   ├── Studio/              # Studio utilities (in progress)
│   │   ├── Uri/                 # URI handling (in progress)
│   │   └── Yaml/                # YAML support (in progress)
│   │
│   ├── Task/
│   │   └── Composer.php         # Composer task automation
│   │
│   └── Transformer/
│       ├── Factory.php          # Abstraction factory
│       ├── Observer.php         # Adaptive observer
│       ├── Parser.php           # Parser interface
│       └── Resolver.php         # Type resolver
│
├── tests/
│   ├── test.html                # Test HTML markup
│   ├── test.php                 # Basic tests
│   ├── test4.php                # Markup parsing benchmark
│   └── ...                      # Additional test files
│
├── composer.json                # Package configuration
└── README.md                    # This file
```

## Development Status

This project is **work in progress**. The following components are implemented:

- ✅ Core Abstraction layer
- ✅ Reference and Value classes
- ✅ Markup (HTML/XML) Parser with Unicode support
- ✅ DOM Parser
- ✅ Transformer (Factory, Resolver, Observer)
- ✅ Type coercion (string, integer, float, boolean, array, object, null)
- ✅ JSON serialization
- ✅ Common utilities (StringCase, Unicode, Type)
- ✅ Scalar Factory

The following components are **in progress**:

- 🔄 YAML Support
- 🔄 CSV Support  
- 🔄 SQL Support
- 🔄 Storage abstraction
- 🔄 JSON Logic
- 🔄 Composer package integration

## License

[To be determined]