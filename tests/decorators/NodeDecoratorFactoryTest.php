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

namespace sad_spirit\html_quickform2\json_renderer\tests\decorators;

use PHPUnit\Framework\TestCase;
use sad_spirit\html_quickform2\json_renderer\decorators\{
    ContainerDecorator,
    ElementDecorator,
    NodeDecoratorFactory
};
use sad_spirit\html_quickform2\json_renderer\exceptions\InvalidArgumentException;
use HTML_QuickForm2_Container_Group as Group;
use HTML_QuickForm2_Element_InputText as InputText;
use HTML_QuickForm2_Node as Node;

/**
 * Test for Factory returning a decorated Node when building client-side rules
 */
class NodeDecoratorFactoryTest extends TestCase
{
    public function testCanDecorateElement(): void
    {
        $input     = new InputText('foo');
        $decorator = NodeDecoratorFactory::decorate($input);

        $this::assertInstanceOf(ElementDecorator::class, $decorator);
        $this::assertEquals('foo', $decorator->getName());
    }

    public function testCanDecorateContainer(): void
    {
        $group     = new Group('bar');
        $decorator = NodeDecoratorFactory::decorate($group);

        $this::assertInstanceOf(ContainerDecorator::class, $decorator);
        $this::assertEquals('bar', $decorator->getName());
    }

    public function testCannotDecorateCustomNodeSubclass(): void
    {
        $mockNode = $this->getMockBuilder(Node::class)
            ->getMockForAbstractClass();

        $this::expectException(InvalidArgumentException::class);
        $this::expectExceptionMessage('Cannot decorate');
        NodeDecoratorFactory::decorate($mockNode);
    }
}
