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
use sad_spirit\html_quickform2\json_renderer\decorators\ElementDecorator;
use sad_spirit\html_quickform2\json_renderer\exceptions\LogicException;
use HTML_QuickForm2_Container_Group as Group;
use HTML_QuickForm2_Element_InputCheckbox as InputCheckbox;
use HTML_QuickForm2_Element_InputText as InputText;
use HTML_QuickForm2_Element_Select as Select;
use HTML_QuickForm2_Rule_Empty as EmptyRule;

/**
 * Test for decorator used for "scalar" Elements when building client-side rules
 */
class ElementDecoratorTest extends TestCase
{
    public function testGetJavascriptValueUsesElementName(): void
    {
        $input     = new InputText('foo', ['id' => 'bar']);
        $decorator = new ElementDecorator($input);

        $this::assertEquals('qf.$v("foo")', $decorator->getJavascriptValue(false));
        $this::assertEquals('"foo"', $decorator->getJavascriptValue(true));
    }

    public function testGetJavascriptValueForMultipleSelect(): void
    {
        $select    = new Select('foo', ['id' => 'bar']);
        $decorator = new ElementDecorator($select);

        $this::assertEquals('"foo"', $decorator->getJavascriptValue(true));

        $select->setAttribute('multiple');
        $this::assertEquals('"foo[]"', $decorator->getJavascriptValue(true));
    }

    public function testForwardsAttributes(): void
    {
        $input     = new InputText('foo', ['id' => 'foo-id', 'class' => 'bar baz']);
        $decorator = new ElementDecorator($input);

        $this::assertEquals('foo', $decorator->getAttribute('name'));
        $this::assertTrue(isset($decorator['type']));
        $this::assertFalse(isset($decorator['multiple']));
        $this::assertEquals('foo-id', $decorator['id']);
        $this::assertTrue($decorator->hasClass('bar'));
        $this::assertFalse($decorator->hasClass('quux'));
        $this::assertEquals('foo-id', $decorator->getAttributes()['id']);
    }

    public function testForwardsGetters(): void
    {
        $input = (new InputText('bar', ['id' => 'bar-id', 'class' => 'baz quux']))
            ->setComment('a comment')
            ->setIndentLevel(10)
            ->setLabel('Test:')
            ->setValue(' a value ')
            ->addFilter('trim')
            ->setError('Error!');

        $group = new Group();
        $group->appendChild($input);

        $decorator = new ElementDecorator($input);
        $this::assertEquals('text', $decorator->getType());
        $this::assertEquals('bar', $decorator->getName());
        $this::assertEquals('bar-id', $decorator->getId());
        $this::assertEquals('a comment', $decorator->getComment());
        $this::assertEquals(10, $decorator->getIndentLevel());
        $this::assertEquals('Test:', $decorator->getLabel());
        $this::assertEquals('Test:', $decorator->getData()['label']);
        $this::assertEquals(' a value ', $decorator->getRawValue());
        $this::assertEquals('a value', $decorator->getValue());
        $this::assertEquals('Error!', $decorator->getError());
        $this::assertSame($group, $decorator->getContainer());
        $this::assertFalse($decorator->isRequired());
        $this::assertEquals(['bar-id'], $decorator->getJavascriptTriggers());
    }

    public function testForwardsToggleFrozenReadOnly(): void
    {
        $input     = new InputText('frozen');
        $decorator = new ElementDecorator($input);

        $this::assertFalse($decorator->toggleFrozen());

        $input->toggleFrozen(true);
        $this::assertTrue($decorator->toggleFrozen());

        $this::expectException(LogicException::class);
        $this::expectExceptionMessage('modification is disallowed');
        $decorator->toggleFrozen(false);
    }

    public function testForwardsPersistentFreezeReadOnly(): void
    {
        $input     = new InputText('persistent');
        $decorator = new ElementDecorator($input);

        $this::assertTrue($decorator->persistentFreeze());

        $input->persistentFreeze(false);
        $this::assertFalse($decorator->persistentFreeze());

        $this::expectException(LogicException::class);
        $this::expectExceptionMessage('modification is disallowed');
        $decorator->persistentFreeze(true);
    }

    /**
     * @dataProvider modificationMethods
     */
    public function testDisallowModificationMethods(string $method, array $arguments): void
    {
        $this::expectException(LogicException::class);
        $this::expectExceptionMessage('modification is disallowed');

        $input     = new InputText('disallowed');
        $decorator = new ElementDecorator($input);
        \call_user_func_array([$decorator, $method], $arguments);
    }

    public function testForwardsCustomMethods(): void
    {
        $input     = (new InputCheckbox('foo'))
            ->setContent('foo label');
        $decorator = new ElementDecorator($input);

        $this::assertEquals('foo label', $decorator->getContent());
    }

    public function testMissingCustomMethod(): void
    {
        $input     = new InputText('missing');
        $decorator = new ElementDecorator($input);

        try {
            set_error_handler(
                static function (int $errno, string $errstr) {
                    throw new \Exception($errstr, $errno);
                },
                E_ALL
            );
            $this::expectException(\Exception::class);
            $this::expectExceptionMessage(InputText::class . '::fooBar');
            $decorator->fooBar();

        } finally {
            restore_error_handler();
        }
    }

    public function modificationMethods(): array
    {
        return [
            ['setAttribute',        ['foo', 'bar']],
            ['setAttributes',       [['foo' => 'bar']]],
            ['mergeAttributes',     [['foo' => 'bar']]],
            ['removeAttribute',     ['foo']],
            ['setIndentLevel',      [10]],
            ['setComment',          ['a comment']],
            ['removeClass',         ['foo']],
            ['offsetSet',           ['foo', 'bar']],
            ['offsetUnset',         ['foo']],
            ['setName',             ['foo']],
            ['setId',               ['foo']],
            ['setValue',            ['bar']],
            ['setLabel',            ['label']],
            ['addRule',             ['required', 'an error']],
            ['removeRule',          [new EmptyRule(new InputText('other'))]],
            ['setError',            ['an error']],
            ['addFilter',           ['trim']],
            ['addRecursiveFilter',  ['trim']]
        ];
    }
}
