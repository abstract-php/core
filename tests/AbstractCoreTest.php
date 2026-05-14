<?php

declare(strict_types=1);

namespace AbstractLang\Tests;

use AbstractLang\AbstractCore;
use AbstractLang\Emitter\HtmlEmitter;
use AbstractLang\Emitter\JsonEmitter;
use AbstractLang\Emitter\JsxEmitter;
use AbstractLang\Exception\ImportException;
use AbstractLang\Exception\MappingException;
use AbstractLang\Exception\ParseException;
use AbstractLang\Exception\RuntimeResolutionException;
use AbstractLang\Mapper\HtmlMapper;
use AbstractLang\Mapper\ReactMapper;
use AbstractLang\Parser\Json\JsonTagParser;
use AbstractLang\Parser\Markup\DomMarkupParser;
use AbstractLang\Parser\Markup\MarkupParseOptions;
use AbstractLang\Runtime\RuntimeResolver;
use AbstractLang\Tree\Node;
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
