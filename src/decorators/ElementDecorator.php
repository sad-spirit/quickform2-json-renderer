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

use HTML_QuickForm2_Element as Element;
use HTML_QuickForm2_JavascriptBuilder as JavascriptBuilder;

/**
 * A decorator for instances of Element used in Rules
 *
 * This basically replaces element's id by its name in JS code used for getting element value. With frameworks,
 * we are unlikely to use document.getElementById(), relying instead on 'values' field
 * returned in JSON and keyed by element names
 */
class ElementDecorator extends Element
{
    /**
     * @template-use CommonNodeMethods<Element>
     */
    use CommonNodeMethods;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(Element $decorated)
    {
        $this->decorated = $decorated;
    }

    public function getJavascriptValue($inContainer = false): string
    {
        $name = JavascriptBuilder::encode($this->decorated->getName());
        return $inContainer ? $name : "qf.\$v($name)";
    }

    public function __call($method, $arguments)
    {
        if (\method_exists($this->decorated, $method)) {
            return \call_user_func_array([$this->decorated, $method], $arguments);
        } elseif (\method_exists($this->decorated, '__call')) {
            return $this->decorated->__call($method, $arguments);
        }
        \trigger_error(
            "Fatal error: Call to undefined method " . \get_class($this->decorated) . "::" . $method . "()",
            \E_USER_ERROR
        );
    }
}
