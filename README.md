# Abstract

Abstract is a powerful PHP library that introduces a componential markup language with an XML-based syntax to access PHP classes and methods. This library includes a Processor, which is responsible for compiling AML (Abstract Markup Language) code.

## Features

- Componential markup language with XML-based syntax
- Access PHP classes and methods
- AML (Abstract Markup Language) compilation using the provided Processor

## Installation

To use Abstract in your PHP project, you need to have `Composer` installed. Then, run the following command:

```bash
composer require abstract/core
```

This library also requires symfony/polyfill to work correctly. If you haven't installed it already, you can add it to your project using Composer:

```bash
composer require symfony/polyfill
```

## Usage

To use Abstract in your PHP project, you need to import the library's namespace, which is "X". You can do this by adding the following line at the beginning of your PHP file:

```php
<?php
require 'vendor/autoload.php';

use X\Processor;

// Get AML content from resource
$amlContent = Resource::get('src/resources/abstract/app/index.html');

// Load AML content into Processor
$processor = new Processor($amlContent, false);
```

## Contributing

Contributions are welcome! If you would like to contribute to Abstract, please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Make your changes and commit them.
4. Push your changes to your forked repository.
5. Submit a pull request to the main branch of the original repository.
6. Please ensure that your code follows the existing coding style and includes appropriate tests.

## Credits

Abstract is developed and maintained by Patiparnne Vongchompue (Armes).