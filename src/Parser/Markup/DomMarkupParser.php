<?php

declare(strict_types=1);

namespace AbstractLang\Parser\Markup;

use AbstractLang\Exception\ParseException;
use AbstractLang\Tree\Node;
use DOMCdataSection;
use DOMComment;
use DOMDocument;
use DOMDocumentType;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;
use DOMText;

final class DomMarkupParser
{
    /** @var array<string, true> */
    private const VOID_ELEMENTS = [
        'area' => true,
        'base' => true,
        'br' => true,
        'col' => true,
        'embed' => true,
        'hr' => true,
        'img' => true,
        'input' => true,
        'link' => true,
        'meta' => true,
        'param' => true,
        'source' => true,
        'track' => true,
        'wbr' => true,
    ];

    /** @var array<string, true> */
    private const RAW_TEXT_ELEMENTS = [
        'script' => true,
        'style' => true,
    ];

    /** @var array<string, true> */
    private const BOOLEAN_ATTRIBUTES = [
        'allowfullscreen' => true,
        'async' => true,
        'autofocus' => true,
        'autoplay' => true,
        'checked' => true,
        'controls' => true,
        'default' => true,
        'defer' => true,
        'disabled' => true,
        'formnovalidate' => true,
        'hidden' => true,
        'ismap' => true,
        'itemscope' => true,
        'loop' => true,
        'multiple' => true,
        'muted' => true,
        'nomodule' => true,
        'novalidate' => true,
        'open' => true,
        'readonly' => true,
        'required' => true,
        'reversed' => true,
        'selected' => true,
    ];

    public function parseHtmlFile(string $path, ?MarkupParseOptions $options = null): Node
    {
        if (!is_file($path)) {
            throw new ParseException(sprintf('HTML source "%s" does not exist.', $path));
        }

        $source = file_get_contents($path);
        if ($source === false) {
            throw new ParseException(sprintf('Unable to read HTML source "%s".', $path));
        }

        return $this->parseHtmlString($source, $path, $options);
    }

    public function parseHtmlString(string $source, ?string $sourceName = null, ?MarkupParseOptions $options = null): Node
    {
        $options ??= new MarkupParseOptions();
        $sourceHadDoctype = $this->sourceHasDoctype($source);
        $html = $options->fragment
            ? '<abstract-fragment-root>' . $source . '</abstract-fragment-root>'
            : $source;

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($this->withUtf8Hint($html), $options->htmlLibxmlOptions());
        $errors = $this->collectLibxmlErrors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            throw new ParseException('Unable to parse HTML source: ' . implode('; ', $errors));
        }

        if ($options->fragment) {
            $wrapper = $document->getElementsByTagName('abstract-fragment-root')->item(0);
            if (!$wrapper instanceof DOMElement) {
                throw new ParseException('Unable to locate internal HTML fragment wrapper.');
            }

            return Node::fragment(
                $this->convertChildren($wrapper, $options, $sourceName, '/'),
                $this->meta($options, $sourceName, '/'),
            );
        }

        $children = [];
        $index = 0;
        foreach ($document->childNodes as $child) {
            $node = $this->convertNode($child, $options, $sourceName, '/' . $index, null, $sourceHadDoctype);
            if ($node !== null) {
                $children[] = $node;
            }
            $index++;
        }

        return count($children) === 1
            ? $children[0]
            : Node::fragment($children, $this->meta($options, $sourceName, '/'));
    }

    public function parseXmlFile(string $path, ?MarkupParseOptions $options = null): Node
    {
        if (!is_file($path)) {
            throw new ParseException(sprintf('XML source "%s" does not exist.', $path));
        }

        $source = file_get_contents($path);
        if ($source === false) {
            throw new ParseException(sprintf('Unable to read XML source "%s".', $path));
        }

        return $this->parseXmlString($source, $path, $options);
    }

    public function parseXmlString(string $source, ?string $sourceName = null, ?MarkupParseOptions $options = null): Node
    {
        $options ??= new MarkupParseOptions(mode: MarkupParseOptions::MODE_XML);

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($source, $options->xmlLibxmlOptions());
        $errors = $this->collectLibxmlErrors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            throw new ParseException('Unable to parse XML source: ' . implode('; ', $errors));
        }

        $children = [];
        $index = 0;
        foreach ($document->childNodes as $child) {
            $node = $this->convertNode($child, $options, $sourceName, '/' . $index, null, true);
            if ($node !== null) {
                $children[] = $node;
            }
            $index++;
        }

        return count($children) === 1
            ? $children[0]
            : Node::fragment($children, $this->meta($options, $sourceName, '/'));
    }

    /**
     * @return list<Node>
     */
    private function convertChildren(DOMNode $node, MarkupParseOptions $options, ?string $sourceName, string $pointer): array
    {
        $children = [];
        $index = 0;
        $parentName = $node instanceof DOMElement ? strtolower($node->tagName) : null;
        foreach ($node->childNodes as $child) {
            $converted = $this->convertNode($child, $options, $sourceName, $pointer . '/' . $index, $parentName, true);
            if ($converted !== null) {
                $children[] = $converted;
            }
            if ($child instanceof DOMElement && isset(self::VOID_ELEMENTS[strtolower($child->tagName)])) {
                array_push($children, ...$this->convertChildren($child, $options, $sourceName, $pointer . '/' . $index));
            }
            $index++;
        }
        return $children;
    }

    private function convertNode(
        DOMNode $node,
        MarkupParseOptions $options,
        ?string $sourceName,
        string $pointer,
        ?string $parentName,
        bool $sourceHadDoctype,
    ): ?Node {
        if ($node instanceof DOMProcessingInstruction) {
            return null;
        }

        if ($node instanceof DOMDocumentType) {
            if (!$options->preserveDoctype || !$sourceHadDoctype) {
                return null;
            }

            return Node::value('doctype', $this->doctypeValue($node), $this->meta($options, $sourceName, $pointer));
        }

        if ($node instanceof DOMElement) {
            $name = $node->tagName;
            $props = $this->attributes($node);
            $children = isset(self::VOID_ELEMENTS[strtolower($name)])
                ? []
                : $this->convertChildren($node, $options, $sourceName, $pointer);

            if (str_starts_with($name, ':')) {
                $runtimeName = substr($name, 1);
                if ($runtimeName === '') {
                    throw new ParseException($this->formatSourceError(
                        'Runtime markup node names must not be empty. DOMDocument parsed this tag as ":"; the original tag name is likely unsupported by the HTML parser.',
                        $sourceName,
                        $pointer,
                        $node,
                    ));
                }

                return Node::runtime($runtimeName, $props, $children, null, $this->meta($options, $sourceName, $pointer, $node));
            }

            return Node::element($name, $props, $children, $this->meta($options, $sourceName, $pointer, $node));
        }

        if ($node instanceof DOMComment) {
            if (!$options->preserveComments) {
                return null;
            }

            return Node::value('comment', $node->nodeValue ?? '', $this->meta($options, $sourceName, $pointer, $node));
        }

        if ($node instanceof DOMCdataSection) {
            $type = $parentName !== null && isset(self::RAW_TEXT_ELEMENTS[$parentName]) ? 'raw' : 'cdata';
            return Node::value($type, $node->nodeValue ?? '', $this->meta($options, $sourceName, $pointer, $node));
        }

        if ($node instanceof DOMText) {
            $value = $node->nodeValue ?? '';
            if (!$options->preserveWhitespace && trim($value) === '') {
                return null;
            }

            $type = $parentName !== null && isset(self::RAW_TEXT_ELEMENTS[$parentName]) ? 'raw' : 'string';
            return Node::value($type, $value, $this->meta($options, $sourceName, $pointer, $node));
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(DOMElement $element): array
    {
        $props = [];
        foreach ($element->attributes as $attribute) {
            $name = $attribute->nodeName;
            $value = $attribute->nodeValue ?? '';
            $lowerName = strtolower($name);
            $isBooleanAttribute = isset(self::BOOLEAN_ATTRIBUTES[$lowerName])
                && ($value === '' || strcasecmp($value, $name) === 0);
            $props[$name] = $isBooleanAttribute ? true : $value;
        }
        return $props;
    }

    private function doctypeValue(DOMDocumentType $doctype): string
    {
        $value = $doctype->name;
        if ($doctype->publicId !== '') {
            $value .= ' PUBLIC "' . $doctype->publicId . '"';
        }
        if ($doctype->systemId !== '') {
            $value .= ' "' . $doctype->systemId . '"';
        }
        if (is_string($doctype->internalSubset) && $doctype->internalSubset !== '') {
            $value .= ' [' . $doctype->internalSubset . ']';
        }
        return $value;
    }

    private function withUtf8Hint(string $source): string
    {
        return '<?xml encoding="UTF-8">' . $source;
    }

    private function sourceHasDoctype(string $source): bool
    {
        return preg_match('/^\s*<!doctype\b/i', $source) === 1;
    }

    /**
     * @return list<string>
     */
    private function collectLibxmlErrors(): array
    {
        $messages = [];
        foreach (libxml_get_errors() as $error) {
            $message = trim($error->message);
            if ($message !== '') {
                $messages[] = $message;
            }
        }
        libxml_clear_errors();
        return $messages;
    }

    /**
     * @return array<string, mixed>
     */
    private function meta(MarkupParseOptions $options, ?string $sourceName, string $pointer, ?DOMNode $node = null): array
    {
        if (!$options->includeMeta) {
            return [];
        }

        $meta = array_filter([
            'source' => $sourceName,
            'pointer' => $pointer,
        ], static fn (?string $value): bool => $value !== null);

        $line = $node?->getLineNo() ?? 0;
        if ($line > 0) {
            $meta['line'] = $line;
        }

        return $meta;
    }

    private function formatSourceError(string $message, ?string $sourceName, string $pointer, DOMNode $node): string
    {
        $line = $node->getLineNo();
        $location = $sourceName !== null ? $sourceName : 'markup source';
        if ($line > 0) {
            $location .= ':' . $line;
        }
        $location .= ' at ' . $pointer;

        $sourceLine = $this->sourceLine($sourceName, $line);
        if ($sourceLine !== null) {
            $location .= ' near "' . trim($sourceLine) . '"';
        }

        return $message . ' Source: ' . $location . '.';
    }

    private function sourceLine(?string $sourceName, int $line): ?string
    {
        if ($sourceName === null || $line < 1 || !is_file($sourceName)) {
            return null;
        }

        $file = new \SplFileObject($sourceName);
        $file->seek($line - 1);
        if ($file->eof()) {
            return null;
        }

        return rtrim((string) $file->current());
    }
}
