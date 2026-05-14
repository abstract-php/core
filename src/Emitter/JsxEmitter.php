<?php

declare(strict_types=1);

namespace Abstract\Emitter;

use Abstract\Mapper\TargetNode;

final class JsxEmitter implements EmitterInterface
{
    public function emit(TargetNode $node): string
    {
        return match ($node->kind) {
            TargetNode::FRAGMENT => implode('', array_map(fn (TargetNode $child): string => $this->emit($child), $node->children)),
            TargetNode::TEXT => $this->escapeText((string) $node->value),
            TargetNode::RAW_TEXT => (string) $node->value,
            TargetNode::COMMENT => '{/*' . $this->escapeComment((string) $node->value) . '*/}',
            TargetNode::DOCTYPE => '',
            TargetNode::ELEMENT => $this->emitElement($node),
            default => '',
        };
    }

    private function emitElement(TargetNode $node): string
    {
        $name = (string) $node->name;
        $props = $this->props($node->props);
        if ($node->children === []) {
            return '<' . $name . $props . ' />';
        }
        return '<' . $name . $props . '>'
            . implode('', array_map(fn (TargetNode $child): string => $this->emit($child), $node->children))
            . '</' . $name . '>';
    }

    /**
     * @param array<string, mixed> $props
     */
    private function props(array $props): string
    {
        $parts = [];
        foreach ($props as $key => $value) {
            if ($value === null) {
                continue;
            }
            if ($value === true) {
                $parts[] = $this->propName($key);
                continue;
            }
            if ($value === false) {
                $parts[] = $this->propName($key) . '={false}';
                continue;
            }
            if (is_int($value) || is_float($value)) {
                $parts[] = $this->propName($key) . '={' . $value . '}';
                continue;
            }
            if (is_array($value) || is_object($value)) {
                $parts[] = $this->propName($key) . '={' . (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null') . '}';
                continue;
            }
            $parts[] = $this->propName($key) . '="' . $this->escapeAttribute((string) $value) . '"';
        }
        return $parts === [] ? '' : ' ' . implode(' ', $parts);
    }

    private function escapeText(string $value): string
    {
        return str_replace(['&', '<', '>', '{', '}'], ['&amp;', '&lt;', '&gt;', '&#123;', '&#125;'], $value);
    }

    private function escapeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function propName(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_.$-]/', '', $value) ?: '';
    }

    private function escapeComment(string $value): string
    {
        return str_replace('*/', '* /', $value);
    }
}
