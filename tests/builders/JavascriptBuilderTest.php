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

namespace sad_spirit\html_quickform2\json_renderer\tests\builders;

use PHPUnit\Framework\TestCase;
use sad_spirit\html_quickform2\json_renderer\{
    builders\JavascriptBuilder,
    decorators\RuleDecorator,
    exceptions\InvalidArgumentException,
    exceptions\LogicException
};
use HTML_QuickForm2_Container_Group as Group;
use HTML_QuickForm2_Container_Repeat as Repeat;
use HTML_QuickForm2_Element_Hierselect as Hierselect;
use HTML_QuickForm2_Element_InputText as InputText;
use HTML_QuickForm2_Renderer as Renderer;
use HTML_QuickForm2_Rule as Rule;

/**
 * Test for JavaScript builder returning validation rules that will be encoded in JSON
 */
class JavascriptBuilderTest extends TestCase
{
    private Renderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = Renderer::factory('stub')
            ->setJavascriptBuilder(new JavascriptBuilder());
    }

    public function testIgnoresRepeatElementJavascript(): void
    {
        $repeat = (new Repeat())->setPrototype(new Group());
        $repeat->render($this->renderer);

        $this::assertEquals('', $this->renderer->getJavascriptBuilder()->getSetupCode());
    }

    public function testDisallowsOtherElementsJavascript(): void
    {
        $hierselect = (new Hierselect('foo'))
            ->loadOptions([
                [0 => 'One', 1 => 'Two'],
                [0 => [0 => 'One One', 1 => 'One Two'], 1 => [0 => 'Two One', 1 => 'Two Two']]
            ]);

        $this::expectException(InvalidArgumentException::class);
        $this::expectExceptionMessage('does not handle');
        $hierselect->render($this->renderer);
    }

    public function testDisallowsGetValidator(): void
    {
        $this::expectException(LogicException::class);
        $this::expectExceptionMessage('inline JavaScript');

        (new JavascriptBuilder())->getValidator();
    }

    public function testGetRules(): void
    {
        /** @var JavascriptBuilder $builder */
        $builder  = $this->renderer->getJavascriptBuilder();
        $inputOne = new InputText('one');
        $ruleOne  = $inputOne->addRule('empty', 'should be empty', null, Rule::CLIENT_SERVER);
        $inputTwo = new InputText('two');
        $ruleTwo  = $inputTwo->addRule('nonempty', 'should not be empty', null, Rule::ONBLUR_CLIENT_SERVER);

        $this::assertEquals([], $builder->getRules());

        $builder->setFormId('form-one');
        $this::assertEquals([], $builder->getRules('form-one'));

        $inputOne->render($this->renderer);
        $this::assertEquals(
            [(new RuleDecorator($ruleOne))->getJavascriptParts(false)],
            $builder->getRules('form-one')
        );

        $builder->setFormId('form-two');
        $inputTwo->render($this->renderer);
        $this::assertEquals(
            [(new RuleDecorator($ruleTwo))->getJavascriptParts(true)],
            $builder->getRules('form-two')
        );

        $this::assertCount(2, $builder->getRules());
    }
}
