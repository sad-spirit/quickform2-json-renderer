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
use sad_spirit\html_quickform2\json_renderer\decorators\RuleDecorator;
use sad_spirit\html_quickform2\json_renderer\exceptions\LogicException;
use HTML_QuickForm2_Container_Group as Group;
use HTML_QuickForm2_Element_InputText as InputText;
use HTML_QuickForm2_Rule as Rule;
use HTML_QuickForm2_Rule_Compare as CompareRule;
use HTML_QuickForm2_Rule_Each as EachRule;
use HTML_QuickForm2_Rule_Empty as EmptyRule;
use HTML_QuickForm2_Rule_Nonempty as NonemptyRule;

/**
 * Test for decorator used for Rules when building client-side validation code
 */
class RuleDecoratorTest extends TestCase
{
    public function testGetJavascriptParts(): void
    {
        $empty = new EmptyRule(
            new InputText('foo', ['id' => 'foo-id']),
            'should be empty'
        );
        $nonempty = new NonemptyRule(
            new InputText('bar', ['id' => 'bar-id']),
            'should not be empty'
        );

        $decorator = new RuleDecorator($empty->and_($nonempty));
        $partsTriggers = $decorator->getJavascriptParts(true);
        $partsNoTriggers = $decorator->getJavascriptParts(false);

        $this::assertEquals(['foo-id', 'bar-id'], $partsTriggers['triggers']);

        unset($partsTriggers['triggers']);
        $this::assertEquals($partsNoTriggers, $partsTriggers);

        $this::assertEquals('foo-id', $partsTriggers['owner']);
        $this::assertEquals('should be empty', $partsTriggers['message']);
        $this::assertStringContainsString('qf.rules.empty(qf.$v("foo"))', $partsTriggers['callback']);
        $this::assertEquals(
            (new RuleDecorator($nonempty))->getJavascriptParts(false),
            $partsTriggers['chained'][0][0]
        );
    }

    public function testDecoratesConfig(): void
    {
        $group     = new Group();
        $empty     = new EmptyRule($group);
        $each      = new EachRule($group, 'should be empty', $empty);
        $decorator = new RuleDecorator($each);

        $this->assertInstanceOf(RuleDecorator::class, $decorator->getConfig());

        $owner     = new InputText('validated');
        $template  = new InputText('template');
        $compare   = new CompareRule(
            $owner,
            'should be different',
            ['operator' => '!==', 'operand' => $template]
        );
        $decorator = new RuleDecorator($compare);

        $this::assertInstanceOf(ElementDecorator::class, $decorator->getConfig()['operand']);
        $this::assertEquals('!==', $decorator->getConfig()['operator']);
    }

    public function testForwardsGetters(): void
    {
        $input    = new InputText('foo', ['id' => 'foo-id']);
        $mockRule = $this->getMockBuilder(Rule::class)
            ->setConstructorArgs([$input, 'a message'])
            ->onlyMethods(['getJavascriptCallback'])
            ->getMockForAbstractClass();
        $mockRule->expects($this->atLeastOnce())
            ->method('getJavascriptCallback')
            ->willReturn('someCallbackFn');

        $decorator = new RuleDecorator($mockRule);
        $this::assertEquals('a message', $decorator->getMessage());
        $this::assertStringStartsWith('new qf.Rule(someCallbackFn,', $decorator->getJavascript(false));
        $this::assertStringStartsWith('new qf.LiveRule(someCallbackFn,', $decorator->getJavascript(true));
    }

    /**
     * @dataProvider modificationMethods
     */
    public function testDisallowsModificationMethods(string $method, array $arguments): void
    {
        $this::expectException(LogicException::class);
        $this::expectExceptionMessage('modification is disallowed');

        $rule      = new EmptyRule(new InputText('disallowed'), 'should be empty');
        $decorator = new RuleDecorator($rule);
        \call_user_func_array([$decorator, $method], $arguments);
    }

    public function modificationMethods(): array
    {
        $input = new InputText('stub');
        $rule  = new NonemptyRule($input, 'should not be empty');

        return [
            ['setConfig',   [10]],
            ['setMessage',  ['another message']],
            ['setOwner',    [$input]],
            ['and_',        [$rule]],
            ['or_',         [$rule]]
        ];
    }
}
