<?php

/*
 * This file is part of sad-spirit/quickform2-json-renderer package
 *
 * (c) Alexey Borzov <avb@php.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace sad_spirit\html_quickform2\json_renderer\decorators;

use sad_spirit\html_quickform2\json_renderer\exceptions\LogicException;
use HTML_QuickForm2_Container as Container;
use HTML_QuickForm2_ContainerIterator as ContainerIterator;
use HTML_QuickForm2_Node as Node;

/**
 * A decorator for instances of Container used in Rules
 */
class ContainerDecorator extends Container
{
    /**
     * @template-use CommonNodeMethods<Container>
     */
    use CommonNodeMethods;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(Container $decorated)
    {
        $this->decorated = $decorated;
    }

    public function getElements(): array
    {
        return $this->decorated->getElements();
    }

    public function appendChild(Node $element): Node
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function addElement($elementOrType, $name = null, $attributes = null, array $data = []): Node
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function removeChild(Node $element): Node
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function getElementById($id): ?Node
    {
        return $this->decorated->getElementById($id);
    }

    public function getElementsByName($name): array
    {
        return $this->decorated->getElementsByName($name);
    }

    public function insertBefore(Node $element, Node $reference = null): Node
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function getIterator(): ContainerIterator
    {
        return $this->decorated->getIterator();
    }

    public function getRecursiveIterator($mode = \RecursiveIteratorIterator::SELF_FIRST): \RecursiveIteratorIterator
    {
        return $this->decorated->getRecursiveIterator($mode);
    }

    public function count(): int
    {
        return $this->decorated->count();
    }

    public function getJavascriptValue($inContainer = false): string
    {
        $args  = [];
        $array = [];
        foreach ($this->decorated as $child) {
            if ('' !== ($value = NodeDecoratorFactory::decorate($child)->getJavascriptValue(true))) {
                // Group of probable checkboxes named "foo[]", leave only one such name
                if ('[]"' === \substr($value, -3)) {
                    if (isset($array[$value])) {
                        continue;
                    } else {
                        $array[$value] = true;
                    }
                }
                $args[] = $value;
            }
        }
        return 'qf.$cv(' . \implode(', ', $args) . ')';
    }

    public function __call($method, $arguments)
    {
        if (\method_exists($this->decorated, $method)) {
            return \call_user_func_array([$this->decorated, $method], $arguments);
        } elseif (!\preg_match('/^(add)([a-zA-Z0-9_]+)$/', $method)) {
            return $this->decorated->__call($method, $arguments);
        }
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }
}
