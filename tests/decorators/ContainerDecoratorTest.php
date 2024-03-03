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

use sad_spirit\html_quickform2\json_renderer\decorators\ContainerDecorator;
use sad_spirit\html_quickform2\json_renderer\exceptions\LogicException;
use HTML_QuickForm2_Container_Group as Group;
use HTML_QuickForm2_Element_InputText as InputText;
use PHPUnit\Framework\TestCase;

/**
 * Test for decorator used for Containers when building client-side rules
 */
class ContainerDecoratorTest extends TestCase
{
    public function testGetJavascriptValueUsesChildNames(): void
    {
        $group = new Group();
        $group->addText('foo', ['id' => 'foo-id']);
        $group->addText('bar', ['id' => 'bar-id']);
        $decorator = new ContainerDecorator($group);

        $this::assertEquals('qf.$cv("foo", "bar")', $decorator->getJavascriptValue());
    }

    public function testUniqueChildNames(): void
    {
        $group = new Group();
        $group->addCheckbox('foo[]', ['id' => 'foo-one']);
        $group->addCheckbox('foo[]', ['id' => 'foo-two']);
        $decorator = new ContainerDecorator($group);

        $this::assertEquals('qf.$cv("foo[]")', $decorator->getJavascriptValue());
    }

    public function testForwardsGetters(): void
    {
        $group     = new Group();
        $foo       = $group->addText('foo', ['id' => 'foo-id']);
        $bar       = $group->addText('bar', ['id' => 'bar-id']);
        $decorator = new ContainerDecorator($group);

        $this::assertSame([$foo, $bar], $decorator->getElements());
        $this::assertSame($foo, $decorator->getElementById('foo-id'));
        $this::assertSame([$bar], $decorator->getElementsByName('bar'));

        $this::assertCount(2, $decorator);
    }

    public function testForwardsIterators(): void
    {
        $outer     = new Group();
        $inner     = $outer->addGroup();
        $input     = $inner->addText('inner');
        $decorator = new ContainerDecorator($outer);

        $this::assertSame(
            [$inner],
            \iterator_to_array($decorator->getIterator())
        );
        $this::assertSame(
            [$input],
            \iterator_to_array($decorator->getRecursiveIterator(\RecursiveIteratorIterator::LEAVES_ONLY))
        );
    }

    public function testForwardsCustomMethods(): void
    {
        $group     = (new Group('custom'))
            ->setSeparator('<br />');
        $decorator = new ContainerDecorator($group);

        $this::assertEquals('<br />', $decorator->getSeparator());
    }

    /**
     * @dataProvider modificationMethods
     */
    public function testDisallowsModifyingChildren(string $method, array $arguments): void
    {
        $this::expectException(LogicException::class);
        $this::expectExceptionMessage('modification is disallowed');

        $group     = new Group('disallowed');
        $decorator = new ContainerDecorator($group);
        \call_user_func_array([$decorator, $method], $arguments);
    }

    public function modificationMethods(): array
    {
        $element = new InputText('element');

        return [
            ['appendChild',  [$element]],
            ['addElement',   [$element]],
            ['removeChild',  [$element]],
            ['insertBefore', [$element, $element]],
            ['addText',      ['name']]
        ];
    }
}
