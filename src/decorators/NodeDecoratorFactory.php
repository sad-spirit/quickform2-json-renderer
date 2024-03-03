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

use sad_spirit\html_quickform2\json_renderer\exceptions\InvalidArgumentException;
use HTML_QuickForm2_Container as Container;
use HTML_QuickForm2_Element as Element;
use HTML_QuickForm2_Node as Node;

/**
 * Wraps an instance of Node in a proper decorator
 */
final class NodeDecoratorFactory
{
    public static function decorate(Node $decorated): Node
    {
        if ($decorated instanceof Container) {
            return new ContainerDecorator($decorated);
        } elseif ($decorated instanceof Element) {
            return new ElementDecorator($decorated);
        } else {
            throw new InvalidArgumentException(
                "Cannot decorate an instance of \\HTML_QuickForm2_Node that is not an instance of "
                . "either \\HTML_QuickForm2_Element or \\HTML_QuickForm2_Container"
            );
        }
    }
}
