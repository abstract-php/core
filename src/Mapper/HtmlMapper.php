<?php

declare(strict_types=1);

namespace AbstractLang\Mapper;

use AbstractLang\Exception\MappingException;
use AbstractLang\Tree\Node;

final class HtmlMapper implements MapperInterface
{
    /** @var array<string, true> */
    private const RAW_TEXT_ELEMENTS = [
        'script' => true,
        'style' => true,
    ];

    public function __construct(private readonly bool $strict = true)
    {
    }

    public function map(Node $node): TargetNode
    {
        return $this->mapNode($node, null);
    }

    private function mapNode(Node $node, ?string $parentElement): TargetNode
    {
        return match ($node->kind) {
            Node::FRAGMENT => TargetNode::fragment(array_map(fn (Node $child): TargetNode => $this->mapNode($child, null), $node->children)),
            Node::ELEMENT => $this->mapElement($node),
            Node::VALUE => $this->mapValue($node, $parentElement),
            Node::RUNTIME => $this->handleRuntime($node),
            default => throw new MappingException(sprintf('HTML mapper cannot map node kind "%s".', $node->kind)),
        };
    }

    private function mapElement(Node $node): TargetNode
    {
        $name = (string) $node->name;
        $parentName = strtolower($name);

        return TargetNode::element(
            $name,
            $node->props,
            array_map(fn (Node $child): TargetNode => $this->mapNode($child, $parentName), $node->children),
        );
    }

    private function mapValue(Node $node, ?string $parentElement): TargetNode
    {
        if ($node->type === 'comment') {
            return TargetNode::comment($this->stringify($node->value));
        }

        if ($node->type === 'doctype') {
            return TargetNode::doctype($this->stringify($node->value));
        }

        if ($node->type === 'raw' || ($parentElement !== null && isset(self::RAW_TEXT_ELEMENTS[$parentElement]))) {
            return TargetNode::rawText($this->stringify($node->value));
        }

        return TargetNode::text($this->stringify($node->value));
    }

    private function handleRuntime(Node $node): TargetNode
    {
        if ($this->strict) {
            throw new MappingException(sprintf('HTML mapper received unresolved runtime node ":%s".', $node->name));
        }
        return TargetNode::fragment([]);
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }
        return (string) $value;
    }
}
