<?php

declare(strict_types=1);

namespace AbstractLang\Mapper;

use AbstractLang\Exception\MappingException;
use AbstractLang\Tree\Node;

final class ReactMapper implements MapperInterface
{
    public function __construct(private readonly bool $strict = true)
    {
    }

    public function map(Node $node): TargetNode
    {
        return match ($node->kind) {
            Node::FRAGMENT => TargetNode::fragment(array_map(fn (Node $child): TargetNode => $this->map($child), $node->children)),
            Node::ELEMENT => TargetNode::element((string) $node->name, $this->mapProps($node->props), array_map(fn (Node $child): TargetNode => $this->map($child), $node->children)),
            Node::VALUE => $this->mapValue($node),
            Node::RUNTIME => $this->handleRuntime($node),
            default => throw new MappingException(sprintf('React mapper cannot map node kind "%s".', $node->kind)),
        };
    }

    private function mapValue(Node $node): TargetNode
    {
        if ($node->type === 'comment') {
            return TargetNode::comment($this->stringify($node->value));
        }

        if ($node->type === 'doctype') {
            return TargetNode::fragment([]);
        }

        if ($node->type === 'raw') {
            return TargetNode::rawText($this->stringify($node->value));
        }

        return TargetNode::text($this->stringify($node->value));
    }

    /**
     * @param array<string, mixed> $props
     * @return array<string, mixed>
     */
    private function mapProps(array $props): array
    {
        if (array_key_exists('class', $props) && !array_key_exists('className', $props)) {
            $props['className'] = $props['class'];
            unset($props['class']);
        }
        return $props;
    }

    private function handleRuntime(Node $node): TargetNode
    {
        if ($this->strict) {
            throw new MappingException(sprintf('React mapper received unresolved runtime node ":%s".', $node->name));
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
