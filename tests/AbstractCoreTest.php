<?php

declare(strict_types=1);

namespace Abstract\Tests;

use Abstract\AbstractCore;
use Abstract\Emitter\HtmlEmitter;
use Abstract\Emitter\JsonEmitter;
use Abstract\Emitter\JsxEmitter;
use Abstract\Exception\ImportException;
use Abstract\Exception\MappingException;
use Abstract\Exception\ParseException;
use Abstract\Exception\RuntimeResolutionException;
use Abstract\Mapper\HtmlElementMapping;
use Abstract\Mapper\HtmlMapper;
use Abstract\Mapper\ReactComponent;
use Abstract\Mapper\ReactMapper;
use Abstract\Parser\Json\JsonTagParser;
use Abstract\Parser\Markup\DomMarkupParser;
use Abstract\Parser\Markup\MarkupParseOptions;
use Abstract\Parser\Pkl\PklTagParser;
use Abstract\Render\RenderTarget;
use Abstract\Runtime\RuntimeResolver;
use Abstract\Tree\Node;
use PHPUnit\Framework\TestCase;

if (PHP_SAPI !== 'cli') {
    // ini_set('display_errors', '1');
    // ini_set('display_startup_errors', '1');
    // error_reporting(E_ALL);

    require_once __DIR__ . '/../vendor/autoload.php';
}

final class AbstractCoreTest extends TestCase
{
    private JsonTagParser $parser;
    private AbstractCore $core;

    protected function setUp(): void
    {
        $this->parser = new JsonTagParser();
        $this->core = new AbstractCore($this->parser);
    }

    public function testSimpleElementFixture(): void
    {
        $input = file_get_contents(__DIR__ . '/../fixtures/json/simple-element.input.json');
        self::assertIsString($input);

        $tree = $this->parser->parseString($input);
        self::assertSame(
            json_decode((string) file_get_contents(__DIR__ . '/../fixtures/json/simple-element.tree.json'), true),
            $tree->toArray(),
        );
        self::assertSame(
            trim((string) file_get_contents(__DIR__ . '/../fixtures/json/simple-element.html')),
            $this->core->renderHtml($tree),
        );
    }

    public function testPropsAndChildrenFixture(): void
    {
        $tree = $this->parser->parseFile(__DIR__ . '/../fixtures/json/props-and-children.input.json');
        self::assertSame(
            trim((string) file_get_contents(__DIR__ . '/../fixtures/json/props-and-children.html')),
            $this->core->renderHtml($tree),
        );
    }

    public function testNestedElementAndShorthandChildObject(): void
    {
        $tree = $this->parser->parseString('{"div":{"h1":"Title","p":"Body"}}');

        self::assertSame('<div><h1>Title</h1><p>Body</p></div>', $this->core->renderHtml($tree));
    }

    public function testShorthandArrayChildrenAndRepeatedElements(): void
    {
        $tree = $this->parser->parseString('{"ul":[{"li":"One"},{"li":"Two"}]}');

        self::assertSame('<ul><li>One</li><li>Two</li></ul>', $this->core->renderHtml($tree));
    }

    public function testPrimitiveTypeInference(): void
    {
        $tree = $this->parser->parseString('{"div":["hello",42,1.5,true,null]}');

        self::assertSame(['string', 'int', 'float', 'bool', 'null'], array_map(
            static fn (Node $node): ?string => $node->type,
            $tree->children,
        ));
    }

    public function testExplicitTypedNodesOverrideInference(): void
    {
        $tree = $this->parser->parseString('{"div":[{":string":42},{":int":"42"},{":float":"1.5"},{":bool":"true"},{":null":"ignored"},{":array":{"a":1}},{":object":{"a":1}}]}');

        self::assertSame(['string', 'int', 'float', 'bool', 'null', 'array', 'object'], array_map(
            static fn (Node $node): ?string => $node->type,
            $tree->children,
        ));
        self::assertSame(['42', 42, 1.5, true, null, [1], ['a' => 1]], array_map(
            static fn (Node $node): mixed => $node->value,
            $tree->children,
        ));
    }

    public function testAttributesModifierPatchesParentProps(): void
    {
        $tree = $this->parser->parseString('{"div":[{":attributes":{"class":"card"}},{"span":"Hello"}]}');

        self::assertSame('<div class="card"><span>Hello</span></div>', $this->core->renderHtml($tree));
    }

    public function testPropsModifierPatchesComponentProps(): void
    {
        $tree = $this->parser->parseString('{"Button":[{":props":{"variant":"primary","disabled":false}},"Save"]}');

        self::assertSame('<Button variant="primary">Save</Button>', $this->core->renderHtml($tree));
        self::assertSame('<Button variant="primary" disabled={false}>Save</Button>', $this->core->renderJsx($tree));
    }

    public function testPropsModifierCanUseRuntimeExpressions(): void
    {
        $tree = $this->parser->parseString('{"User":[{":props":{"name":{":expr":{"var":"user.name"}}}}]}');

        self::assertSame('<User name="Ada"></User>', $this->core->renderHtml($tree, ['user' => ['name' => 'Ada']]));
    }

    public function testMultiplePropModifiersUseDeterministicMergeOrder(): void
    {
        $tree = $this->parser->parseString('{"div":{"@":{"class":"first","id":"a"},"#":[{":attributes":{"class":"second"}},{":props":{"id":"b"}}]}}');

        self::assertSame('<div class="second" id="b"></div>', $this->core->renderHtml($tree));
    }

    public function testAttributesWithoutParentIsInvalidInStrictMode(): void
    {
        $this->expectException(RuntimeResolutionException::class);

        $this->core->resolve($this->parser->parseString('{":attributes":{"class":"card"}}'));
    }

    public function testExprVarLookupAsChildAndProp(): void
    {
        $tree = $this->parser->parseString('{"User":{"@":{"name":{":expr":{"var":"user.name"}}},"#":[{":expr":{"var":"user.name"}}]}}');

        self::assertSame('<User name="Ada">Ada</User>', $this->core->renderHtml($tree, ['user' => ['name' => 'Ada']]));
    }

    public function testExprComparison(): void
    {
        $tree = $this->parser->parseString('{":expr":{"==":[{"var":"user.role"},"admin"]}}');
        $resolved = $this->core->resolve($tree, ['user' => ['role' => 'admin']]);

        self::assertSame(Node::VALUE, $resolved->kind);
        self::assertSame('bool', $resolved->type);
        self::assertTrue($resolved->value);
    }

    public function testIfTrueBranchFixture(): void
    {
        $tree = $this->parser->parseFile(__DIR__ . '/../fixtures/runtime/logic-if.input.json');
        $context = json_decode((string) file_get_contents(__DIR__ . '/../fixtures/runtime/logic-if.context.json'), true);

        self::assertSame(
            trim((string) file_get_contents(__DIR__ . '/../fixtures/runtime/logic-if.html')),
            $this->core->renderHtml($tree, $context),
        );
    }

    public function testIfElseBranch(): void
    {
        $tree = $this->parser->parseFile(__DIR__ . '/../fixtures/runtime/logic-if.input.json');

        self::assertSame('<Login></Login>', $this->core->renderHtml($tree, ['user' => ['role' => 'guest']]));
    }

    public function testEachLoop(): void
    {
        $tree = $this->parser->parseString('{"ul":[{":each":{"@":{"items":{":expr":{"var":"users"}},"as":"user"},"#":[{"li":{":expr":{"var":"user.name"}}}]}}]}');

        self::assertSame('<ul><li>Ada</li><li>Grace</li></ul>', $this->core->renderHtml($tree, [
            'users' => [
                ['name' => 'Ada'],
                ['name' => 'Grace'],
            ],
        ]));
    }

    public function testSimpleImportAndImportWithPropsAndSlotChildren(): void
    {
        $tree = $this->parser->parseFile(__DIR__ . '/../fixtures/import/page.input.json');

        self::assertSame(
            trim((string) file_get_contents(__DIR__ . '/../fixtures/import/expected.html')),
            $this->core->renderHtml($tree),
        );
    }

    public function testCircularImportError(): void
    {
        $this->expectException(ImportException::class);

        $tree = $this->parser->parseFile(__DIR__ . '/../fixtures/import/components/CycleA.abstract.json');
        $this->core->resolve($tree);
    }

    public function testMissingImportError(): void
    {
        $this->expectException(ImportException::class);

        $tree = $this->parser->parseString('{":import":"./missing.abstract.json"}', __DIR__ . '/../fixtures/import/page.input.json');
        $this->core->resolve($tree);
    }

    public function testUnknownRuntimeStrictModeError(): void
    {
        $this->expectException(RuntimeResolutionException::class);

        $this->core->resolve($this->parser->parseString('{":vendor.unknown":"x"}'));
    }

    public function testUnknownRuntimeLooseModeWarnsAndDropsSafely(): void
    {
        $resolver = new RuntimeResolver(false, $this->parser);
        $resolved = $resolver->resolve($this->parser->parseString('{"div":[{":vendor.unknown":"x"},"safe"]}'));

        self::assertSame('<div>safe</div>', (new HtmlEmitter())->emit((new HtmlMapper(false))->map($resolved)));
        self::assertCount(1, $resolver->diagnostics());
    }

    public function testHtmlEscaping(): void
    {
        $tree = $this->parser->parseString('{"div":{"@":{"title":"<unsafe> & \"quoted\""},"#":["<script>"]}}');

        self::assertSame('<div title="&lt;unsafe&gt; &amp; &quot;quoted&quot;">&lt;script&gt;</div>', $this->core->renderHtml($tree));
    }

    public function testReactJsxOutputEscapesTextAndMapsClassName(): void
    {
        $tree = $this->parser->parseString('{"div":{"@":{"class":"card","count":2},"#":["{hello}<"]}}');
        $resolved = $this->core->resolve($tree);

        self::assertSame('<div count={2} className="card">&#123;hello&#125;&lt;</div>', (new JsxEmitter())->emit((new ReactMapper())->map($resolved)));
    }

    public function testMapperRejectsUnresolvedRuntimeInStrictMode(): void
    {
        $this->expectException(MappingException::class);

        (new HtmlMapper())->map($this->parser->parseString('{":expr":{"var":"x"}}'));
    }

    public function testPayloadNodesAreRejectedByStrictDefaultRuntime(): void
    {
        $this->expectException(RuntimeResolutionException::class);

        $this->core->resolve($this->parser->parseString('{":php":"<?php echo $user->name; ?>"}'));
    }

    public function testMarkupParserParsesFullHtmlDocumentFixture(): void
    {
        $parser = new DomMarkupParser();
        $tree = $parser->parseHtmlFile(__DIR__ . '/../fixtures/markup/simple-document.input.html', new MarkupParseOptions(includeMeta: false));

        self::assertSame(
            trim((string) file_get_contents(__DIR__ . '/../fixtures/markup/simple-document.html')),
            (new HtmlEmitter())->emit((new HtmlMapper())->map($tree)),
        );
        self::assertSame(
            trim((string) file_get_contents(__DIR__ . '/../fixtures/markup/simple-document.compact.json')),
            (new JsonEmitter())->emitCompactTree($tree),
        );
    }

    public function testTextOnlyMarkupDoesNotBecomeParagraph(): void
    {
        $tree = $this->core->parseHtmlFile(__DIR__ . '/../examples/00-text-markup-roundtrip.source.html', new MarkupParseOptions(includeMeta: false));
        $compactJson = $this->core->treeJson($tree, pretty: false, mode: JsonEmitter::MODE_COMPACT);

        self::assertSame(Node::VALUE, $tree->kind);
        self::assertSame('Hello Test', $tree->value);
        self::assertSame('"Hello Test"', $compactJson);
        self::assertSame('Hello Test', $this->core->renderHtml($this->core->parseJson($compactJson)));
    }

    public function testTextOnlyMarkupPreservesWhitespaceAndPunctuation(): void
    {
        $source = "  Hello, friend! Are you ready?\n";
        $tree = $this->core->parseHtml($source, options: new MarkupParseOptions(includeMeta: false));

        self::assertSame(Node::VALUE, $tree->kind);
        self::assertSame($source, $tree->value);
        self::assertSame($source, $this->core->renderHtml($tree));
    }

    public function testMarkupCompactJsonCanReparseAndRender(): void
    {
        $parser = new DomMarkupParser();
        $tree = $parser->parseHtmlString(
            '<div class="card">Hi<!--saved--><br><script>if (a < b) alert("&");</script></div>',
            options: new MarkupParseOptions(fragment: true, includeMeta: false),
        );

        $json = (new JsonEmitter())->emitCompactTree($tree);
        $reparsed = $this->parser->parseString($json);

        self::assertSame(
            '<div class="card">Hi<!--saved--><br><script>if (a < b) alert("&");</script></div>',
            (new HtmlEmitter())->emit((new HtmlMapper())->map($reparsed)),
        );
    }

    public function testMarkupParserPreservesUtf8AndRawScriptText(): void
    {
        $parser = new DomMarkupParser();
        $tree = $parser->parseHtmlString(
            '<section><p>สวัสดี</p><style>.a > .b { content: "&"; }</style></section>',
            options: new MarkupParseOptions(fragment: true, includeMeta: false),
        );

        self::assertSame(
            '<section><p>สวัสดี</p><style>.a > .b { content: "&"; }</style></section>',
            (new HtmlEmitter())->emit((new HtmlMapper())->map($tree)),
        );
    }

    public function testMarkupParserPreservesNonEnglishAndLongNames(): void
    {
        $longName = str_repeat('hello', 45);
        $source = '<root><กรรม ทดสอบ="20">ทดสอบ</กรรม><Timothée>ok</Timothée><xsl:if>GOF</xsl:if><' . $longName . '>123</' . $longName . '></root>';

        $tree = (new DomMarkupParser())->parseHtmlString(
            $source,
            options: new MarkupParseOptions(fragment: true, includeMeta: false),
        );

        self::assertSame(
            $source,
            (new HtmlEmitter())->emit((new HtmlMapper())->map($tree)),
        );
        self::assertStringContainsString('"กรรม"', (new JsonEmitter())->emitCompactTree($tree, true));
        self::assertStringContainsString('"ทดสอบ"', (new JsonEmitter())->emitCompactTree($tree, true));
        self::assertStringContainsString('"Timothée"', (new JsonEmitter())->emitCompactTree($tree, true));
        self::assertStringContainsString('"xsl:if"', (new JsonEmitter())->emitCompactTree($tree, true));
    }

    public function testMarkupStyleTypedRuntimeBodiesParseAsExplicitValues(): void
    {
        set_error_handler(static function (int $severity, string $message): never {
            throw new \ErrorException($message, 0, $severity);
        });

        try {
            $string = $this->parser->parseString('{":string":{"#":["456"]}}');
            $int = $this->parser->parseString('{":int":{"#":["900"]}}');
        } finally {
            restore_error_handler();
        }

        self::assertSame(Node::VALUE, $string->kind);
        self::assertSame('456', $string->value);
        self::assertSame(Node::VALUE, $int->kind);
        self::assertSame(900, $int->value);
    }

    public function testMarkupParserLiftsDomDocumentVoidElementChildren(): void
    {
        $parser = new DomMarkupParser();
        $tree = $parser->parseHtmlString(
            '<picture><source srcset="large.png"><source srcset="small.png"><img src="fallback.png"></picture>',
            options: new MarkupParseOptions(fragment: true, includeMeta: false),
        );

        self::assertSame(
            '<picture><source srcset="large.png"><source srcset="small.png"><img src="fallback.png"></picture>',
            (new HtmlEmitter())->emit((new HtmlMapper())->map($tree)),
        );
    }

    public function testMarkupParserCanCreateRuntimeNodesWithoutRenderingThemLiterally(): void
    {
        $parser = new DomMarkupParser();
        $tree = $parser->parseHtmlString(
            '<:if><span>ok</span></:if>',
            options: new MarkupParseOptions(fragment: true, includeMeta: false),
        );

        self::assertSame(Node::FRAGMENT, $tree->kind);
        self::assertSame(Node::RUNTIME, $tree->children[0]->kind);
        self::assertSame('if', $tree->children[0]->name);

        $this->expectException(MappingException::class);
        (new HtmlMapper())->map($tree);
    }

    public function testMarkupParserReportsSourceLineForUnsupportedRuntimeTagName(): void
    {
        $path = __DIR__ . '/../fixtures/markup/invalid-runtime-name.input.html';

        try {
            (new DomMarkupParser())->parseHtmlFile($path, new MarkupParseOptions(includeMeta: false));
            self::fail('Expected unsupported runtime tag name parse error.');
        } catch (ParseException $exception) {
            self::assertStringContainsString($path . ':3', $exception->getMessage());
            self::assertStringContainsString('<:กรรม>bad</:กรรม>', $exception->getMessage());
        }
    }

    public function testXmlParseAndRenderRoundtrip(): void
    {
        $tree = $this->core->parseXml(
            '<root><item id="1">Hi &amp; Bye</item><empty></empty></root>',
            options: new MarkupParseOptions(mode: MarkupParseOptions::MODE_XML, includeMeta: false),
        );

        self::assertSame('<root><item id="1">Hi &amp; Bye</item><empty></empty></root>', $this->core->renderXml($tree));
    }

    public function testYamlParsesTagKeySyntaxAndRendersYaml(): void
    {
        $tree = $this->core->parseYaml(<<<'YAML'
div:
  '@':
    class: card
  '#':
    - span: Hello
    - ':if':
        '@':
          test:
            ':expr':
              var: show
        '#':
          - strong: Yes
        ':else':
          - em: No
YAML);

        self::assertSame('<div class="card"><span>Hello</span><strong>Yes</strong></div>', $this->core->renderHtml($tree, ['show' => true]));
        self::assertStringContainsString("class: card", $this->core->renderYaml($tree, ['show' => true]));
    }

    public function testYamlScalarRootParsesAsValue(): void
    {
        $tree = $this->core->parseYaml('Hello Test');

        self::assertSame(Node::VALUE, $tree->kind);
        self::assertSame('Hello Test', $tree->value);
    }

    public function testTomlParsesObjectRootsAndRendersToml(): void
    {
        $tree = $this->core->parseToml(<<<'TOML'
[div]
"#" = ["Hello"]

[div."@"]
class = "card"
TOML);

        self::assertSame('<div class="card">Hello</div>', $this->core->renderHtml($tree));
        $toml = $this->core->renderToml($tree);
        self::assertStringContainsString('[div]', $toml);
        self::assertSame('<div class="card">Hello</div>', $this->core->renderHtml($this->core->parseToml($toml)));
    }

    public function testTomlRejectsScalarDocumentAndScalarOutput(): void
    {
        $this->expectException(ParseException::class);
        $this->core->parseToml('"Hello"');
    }

    public function testTomlScalarTreeOutputIsInvalid(): void
    {
        $this->expectException(MappingException::class);
        $this->core->renderToml(Node::value('string', 'Hello'));
    }

    public function testPklParsesThroughCli(): void
    {
        $this->skipWhenPklIsMissing();

        $tree = $this->core->parsePkl(<<<'PKL'
div = new Mapping {
  ["@"] = new Mapping { ["class"] = "card" }
  ["#"] = List("Hello")
}
PKL);

        self::assertSame('<div class="card">Hello</div>', $this->core->renderHtml($tree));
    }

    public function testPklReportsUnavailableCliClearly(): void
    {
        $this->expectException(ParseException::class);
        (new PklTagParser(binary: '/definitely/missing/abstract-pkl'))->parseString('div = "Hello"');
    }

    public function testPklRenderSyntaxValidatesThroughCli(): void
    {
        $this->skipWhenPklIsMissing();

        $tree = $this->parser->parseString('{"div":{"@":{"class":"card"},"#":["Hello",{"span":"World"}]}}');
        $pkl = $this->core->renderPkl($tree);
        $reparsed = $this->core->parsePkl($pkl);

        self::assertSame('<div class="card">Hello<span>World</span></div>', $this->core->renderHtml($reparsed));
    }

    public function testDataFormatParsersNormalizeEquivalentSources(): void
    {
        $this->skipWhenPklIsMissing();

        $json = $this->parser->parseFile(__DIR__ . '/../fixtures/formats/equivalent.input.json');
        $yaml = $this->core->parseYamlFile(__DIR__ . '/../fixtures/formats/equivalent.input.yaml');
        $toml = $this->core->parseTomlFile(__DIR__ . '/../fixtures/formats/equivalent.input.toml');
        $pkl = $this->core->parsePklFile(__DIR__ . '/../fixtures/formats/equivalent.input.pkl');
        $emitter = new JsonEmitter();
        $expected = json_decode((string) file_get_contents(__DIR__ . '/../fixtures/formats/equivalent.compact.json'), true);

        self::assertSame($expected, $emitter->toData($json, JsonEmitter::MODE_COMPACT));
        self::assertSame($emitter->toData($json, JsonEmitter::MODE_COMPACT), $emitter->toData($yaml, JsonEmitter::MODE_COMPACT));
        self::assertSame($emitter->toData($json, JsonEmitter::MODE_COMPACT), $emitter->toData($toml, JsonEmitter::MODE_COMPACT));
        self::assertSame($emitter->toData($json, JsonEmitter::MODE_COMPACT), $emitter->toData($pkl, JsonEmitter::MODE_COMPACT));
    }

    public function testGenericRenderUsesDefaultHtmlTarget(): void
    {
        $tree = $this->parser->parseString('{"div":"Hello"}');

        self::assertSame('<div>Hello</div>', $this->core->render('html', $tree));
    }

    public function testDefaultJsxInputRemainsNativeWithoutCustomMapping(): void
    {
        $tree = $this->parser->parseString('{"input":[{":props":{"type":"text","name":"email"}}]}');

        self::assertSame('<input type="text" name="email" />', $this->core->renderJsx($tree));
    }

    public function testAbstractCoreCanUseCustomHtmlMapper(): void
    {
        $core = AbstractCore::default()->withRenderTarget('html', RenderTarget::make(
            HtmlMapper::make()->element('input', HtmlElementMapping::tag('x-input')),
            new HtmlEmitter(),
        ));
        $tree = $core->parseJson('{"input":[{":props":{"type":"text","name":"email"}}, "Child"]}');

        self::assertSame('<x-input type="text" name="email">Child</x-input>', $core->renderHtml($tree));
    }

    public function testAbstractCoreCanUseCustomReactMapperWithImports(): void
    {
        $core = AbstractCore::default()->withRenderTarget('jsx', RenderTarget::make(
            ReactMapper::make()->component('input', ReactComponent::imported(
                source: '@headlessui/react',
                export: 'Input',
                as: 'HeadlessInput',
            )),
            new JsxEmitter(),
        ));
        $tree = $core->parseJson('{"input":[{":props":{"type":"text","name":"email","className":"border"}}]}');

        self::assertSame(
            'import { Input as HeadlessInput } from "@headlessui/react";' . "\n\n" . '<HeadlessInput type="text" name="email" className="border" />',
            $core->renderJsx($tree),
        );
    }

    public function testCustomReactNamespacedMappingAndImportDeduplication(): void
    {
        $component = ReactComponent::imported(
            source: '@headlessui/react',
            export: 'Input',
            as: 'HeadlessInput',
        );
        $core = AbstractCore::default()->withRenderTarget('jsx', RenderTarget::make(
            ReactMapper::make()
                ->component('input', $component)
                ->component('ui.input', $component),
            new JsxEmitter(),
        ));
        $tree = $core->parseJson('[{"input":[{":props":{"name":"email"}}]},{"ui.input":[{":props":{"name":"phone"}}]}]');

        self::assertSame(
            'import { Input as HeadlessInput } from "@headlessui/react";' . "\n\n" . '<HeadlessInput name="email" /><HeadlessInput name="phone" />',
            $core->renderJsx($tree),
        );
    }

    public function testConfigDrivenTargetCustomization(): void
    {
        $core = AbstractCore::fromConfig([
            'targets' => [
                'html' => [
                    'elements' => [
                        'input' => ['tag' => 'x-input'],
                    ],
                ],
                'jsx' => [
                    'components' => [
                        'input' => [
                            'source' => '@headlessui/react',
                            'export' => 'Input',
                            'as' => 'HeadlessInput',
                            'importKind' => 'named',
                        ],
                    ],
                ],
            ],
        ]);
        $tree = $core->parseJson('{"input":[{":props":{"name":"email"}}]}');

        self::assertSame('<x-input name="email"></x-input>', $core->renderHtml($tree));
        self::assertSame(
            'import { Input as HeadlessInput } from "@headlessui/react";' . "\n\n" . '<HeadlessInput name="email" />',
            $core->renderJsx($tree),
        );
    }

    private function skipWhenPklIsMissing(): void
    {
        $path = trim((string) shell_exec('command -v pkl 2>/dev/null'));
        if ($path === '') {
            self::markTestSkipped('Pkl CLI is not installed or not on PATH.');
        }
    }

    public function testJsonParserReportsSourcePointerForInvalidRuntimeKey(): void
    {
        try {
            $this->parser->parseString('{":":{}}', 'generated.compact.json');
            self::fail('Expected invalid runtime key parse error.');
        } catch (ParseException $exception) {
            self::assertStringContainsString('generated.compact.json at /:', $exception->getMessage());
        }
    }

    public function testCanonicalAndTaggedJsonModesRemainAvailable(): void
    {
        $tree = Node::element('div', [], [Node::value('string', 'Hello')]);
        $emitter = new JsonEmitter();

        self::assertStringContainsString('"kind": "element"', $emitter->emitTree($tree));
        self::assertSame('{"div":"Hello"}', $emitter->emitTree($tree, false, JsonEmitter::MODE_COMPACT));
        self::assertSame('{"div":{"#":[{":string":"Hello"}]}}', $emitter->emitTree($tree, false, JsonEmitter::MODE_TAGGED));
    }
}
