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
use HTML_QuickForm2_Node as Node;
use HTML_QuickForm2_Rule as Rule;

// phpcs:disable PSR1.Methods.CamelCapsMethodName
/**
 * A decorator for Rules that returns rule parts for JSON serialization
 *
 * As with element decorators, it is intended to be used only inside Renderer when preparing client-side validation,
 * so it forwards getter-type methods, disallows setter-type methods and Rule chaining, and its validate() method
 * always returns false
 */
class RuleDecorator extends Rule
{
    private Rule $decorated;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(Rule $decorated)
    {
        // Cannot simply access owner property, as it is redefined in Each rule -> Fatal error
        $owner = new \ReflectionProperty($decorated, 'owner');
        $owner->setAccessible(true);

        $this->owner     = NodeDecoratorFactory::decorate($owner->getValue($decorated));
        $this->decorated = $decorated;
        $this->config    = $this->decorateConfig($decorated->config);
    }

    /**
     * Wraps instances of Node and Rule present in Rule configuration by respective decorators
     *
     * @param mixed $config
     * @return mixed
     */
    private function decorateConfig($config)
    {
        if ($config instanceof Rule) {
            return new self($config);
        } elseif ($config instanceof Node) {
            return NodeDecoratorFactory::decorate($config);
        } elseif (\is_array($config)) {
            return \array_map([$this, 'decorateConfig'], $config);
        } else {
            return $config;
        }
    }

    public function setConfig($config): self
    {
        throw new LogicException("Rule modification is disallowed when building client-side rules");
    }

    public function setMessage($message): self
    {
        throw new LogicException("Rule modification is disallowed when building client-side rules");
    }

    public function getMessage(): string
    {
        return $this->decorated->getMessage();
    }

    public function setOwner(Node $owner): void
    {
        throw new LogicException("Rule modification is disallowed when building client-side rules");
    }

    public function and_(Rule $next): self
    {
        throw new LogicException("Rule modification is disallowed when building client-side rules");
    }

    public function or_(Rule $next): self
    {
        throw new LogicException("Rule modification is disallowed when building client-side rules");
    }

    protected function validateOwner(): bool
    {
        return false;
    }

    public function validate(): bool
    {
        return false;
    }

    protected function getJavascriptTriggers(): array
    {
        return $this->decorated->getJavascriptTriggers();
    }

    public function getJavascript($outputTriggers = true): string
    {
        return $this->decorated->getJavascript($outputTriggers);
    }

    protected function getJavascriptCallback()
    {
        // We cannot bind a closure to decorated Rule as this only works within inheritance chain.
        // Let's use reflection instead:
        $reflection  = new \ReflectionClass($this->decorated);

        // No constructor as setOwner() implementations sometimes check for specific subclasses of Node
        $newInstance = $reflection->newInstanceWithoutConstructor();
        $newInstance->message = $this->decorated->message;
        $newInstance->config  = $this->config;

        $owner = $reflection->getProperty('owner');
        $owner->setAccessible(true);
        $owner->setValue($newInstance, $this->owner);

        return $newInstance->getJavascriptCallback();
    }

    /**
     * Returns parts that can be sent in JSON, rather than JS code to insert into page
     *
     * We do not override getJavascript() as it is defined as returning string
     *
     * @param bool $outputTriggers
     * @return array
     * @throws \HTML_QuickForm2_Exception
     */
    public function getJavascriptParts(bool $outputTriggers = true): array
    {
        $js = [
            'callback' => $this->getJavascriptCallback(),
            'owner'    => $this->owner->getId(),
            'message'  => $this->decorated->getMessage()
        ];
        if ($outputTriggers && [] !== ($triggers = $this->getJavascriptTriggers())) {
            $js['triggers'] = $triggers;
        }

        if (count($this->decorated->chainedRules) > 1 || count($this->decorated->chainedRules[0]) > 0) {
            $chained = [];
            foreach ($this->decorated->chainedRules as $item) {
                $multipliers = [];
                /** @var Rule $multiplier */
                foreach ($item as $multiplier) {
                    $multipliers[] = (new self($multiplier))->getJavascriptParts(false);
                }
                $chained[] = $multipliers;
            }
            $js['chained'] = $chained;
        }

        return $js;
    }
}
