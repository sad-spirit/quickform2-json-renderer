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
use HTML_QuickForm2_Node as Node;
use HTML_QuickForm2_Renderer as Renderer;
use HTML_QuickForm2_Rule as Rule;

/**
 * Contains methods that are defined in \HTML_QuickForm2_Node and should be in both Element and Container decorators
 *
 * Forwards getters to decorated Node instance and disallows setters and modification methods, just in case.
 * This is implemented as a trait because we cannot create a base class for decorators: those need to extend
 * either Element or Container for somewhat proper instanceof and Iterator support.
 *
 * @psalm-require-extends Node
 * @template T of Node
 */
trait CommonNodeMethods
{
    /**
     * @var T
     */
    private Node $decorated;

    // Methods defined in HTML_Common2

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function setAttribute($name, $value = null): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function getAttribute($name): ?string
    {
        return $this->decorated->getAttribute($name);
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function setAttributes($attributes): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function getAttributes($asString = false)
    {
        return $this->decorated->getAttributes($asString);
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function mergeAttributes($attributes): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function removeAttribute($attribute): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function setIndentLevel($level): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function getIndentLevel(): int
    {
        return $this->decorated->getIndentLevel();
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function setComment($comment): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function getComment(): ?string
    {
        return $this->decorated->getComment();
    }

    public function hasClass($class): bool
    {
        return $this->decorated->hasClass($class);
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function addClass($class): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function removeClass($class): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function offsetExists($offset): bool
    {
        return $this->decorated->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->decorated->offsetGet($offset);
    }

    public function offsetSet($offset, $value): void
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function offsetUnset($offset): void
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function __toString()
    {
        return $this->decorated->__toString();
    }

    // Methods defined in HTML_QuickForm2_Node

    public function getData(): array
    {
        return $this->decorated->getData();
    }

    public function getType(): string
    {
        return $this->decorated->getType();
    }

    public function getName(): ?string
    {
        return $this->decorated->getName();
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function setName($name): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function getId(): ?string
    {
        return $this->decorated->getId();
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function setId($id = null): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function getRawValue()
    {
        return $this->decorated->getRawValue();
    }

    public function getValue()
    {
        return $this->decorated->getValue();
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function setValue($value): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function getLabel()
    {
        return $this->decorated->getLabel();
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function setLabel($label): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function toggleFrozen($freeze = null): bool
    {
        if (null !== $freeze) {
            throw new LogicException("Element modification is disallowed when building client-side rules");
        }
        return $this->decorated->toggleFrozen($freeze);
    }

    public function persistentFreeze($persistent = null): bool
    {
        if (null !== $persistent) {
            throw new LogicException("Element modification is disallowed when building client-side rules");
        }
        return $this->decorated->persistentFreeze($persistent);
    }

    protected function setContainer(Container $container = null): void
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function getContainer(): ?Container
    {
        return $this->decorated->getContainer();
    }

    protected function getDataSources(): array
    {
        return $this->decorated->getDataSources();
    }

    protected function updateValue(): void
    {
        $this->decorated->updateValue();
    }

    public function addRule($rule, $messageOrRunAt = '', $options = null, $runAt = Rule::SERVER): Rule
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function removeRule(Rule $rule): Rule
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function createRule($type, $message = '', $options = null): Rule
    {
        return $this->decorated->createRule($type, $message, $options);
    }

    public function isRequired(): bool
    {
        return $this->decorated->isRequired();
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function setError($error = null): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function getError(): string
    {
        return $this->decorated->getError();
    }

    public function getJavascriptTriggers(): array
    {
        return $this->decorated->getJavascriptTriggers();
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function addFilter($callback, $options = []): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function addRecursiveFilter($callback, $options = []): self
    {
        throw new LogicException("Element modification is disallowed when building client-side rules");
    }

    public function render(Renderer $renderer): Renderer
    {
        return $this->decorated->render($renderer);
    }
}
