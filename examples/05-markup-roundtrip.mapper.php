<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use AbstractLang\AbstractCore;
use AbstractLang\Emitter\JsonEmitter;
use AbstractLang\Parser\Markup\MarkupParseOptions;

$core = new AbstractCore();
$tree = $core->parseHtmlFile(example_path('05-markup-roundtrip.source.html'), new MarkupParseOptions(includeMeta: false));
$compactJson = $core->treeJson($tree, pretty: true, mode: JsonEmitter::MODE_COMPACT);

file_put_contents(example_output_path('05-markup-roundtrip.compact.json'), $compactJson);

$roundtripHtml = $core->renderHtml($core->parseJson(
    $compactJson,
    example_output_path('05-markup-roundtrip.compact.json'),
));

file_put_contents(example_output_path('05-markup-roundtrip.html'), $roundtripHtml);

example_print('Compact JSON', $compactJson);
example_print('Roundtrip HTML', $roundtripHtml);
