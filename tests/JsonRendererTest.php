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

namespace sad_spirit\html_quickform2\json_renderer\tests;

use HTML_QuickForm2_Container_Fieldset as Fieldset;
use PHPUnit\Framework\TestCase;
use sad_spirit\html_quickform2\json_renderer\{
    JsonRenderer,
    builders\JavascriptBuilder,
    builders\RepeatJavascriptBuilder,
    exceptions\InvalidArgumentException
};
use HTML_QuickForm2 as QuickForm;
use HTML_QuickForm2_Container_Repeat_JavascriptBuilder as BaseRepeatJavascriptBuilder;
use HTML_QuickForm2_JavascriptBuilder as BaseJavascriptBuilder;
use HTML_QuickForm2_Rule as Rule;
use HTML_QuickForm2_Element_InputHidden as InputHidden;
use HTML_QuickForm2_Element_Script as Script;
use HTML_QuickForm2_Renderer as Renderer;
use HTML_QuickForm2_Renderer_Proxy as RendererProxy;

/**
 * Test for main renderer class
 */
class JsonRendererTest extends TestCase
{
    /** @var JsonRenderer&RendererProxy */
    private Renderer $renderer;

    public static function setUpBeforeClass(): void
    {
        Renderer::register('json', JsonRenderer::class);
    }

    protected function setUp(): void
    {
        $this->renderer = Renderer::factory('json');
    }

    public function testDisallowsBaseJavascriptBuilder(): void
    {
        $this::expectException(InvalidArgumentException::class);
        $this::expectExceptionMessage(JavascriptBuilder::class);

        $this->renderer->setJavascriptBuilder(new BaseJavascriptBuilder());
    }

    public function testAllowsOwnJavascriptBuilder(): void
    {
        $this::assertInstanceOf(JavascriptBuilder::class, $this->renderer->getJavascriptBuilder());

        $this->renderer->setJavascriptBuilder($builder = new JavascriptBuilder());
        $this::assertSame($builder, $this->renderer->getJavascriptBuilder());
    }

    public function testReplacesBaseRepeatJavascriptBuilder(): void
    {
        $this->renderer->setJavascriptBuilder(new BaseRepeatJavascriptBuilder());
        $this::assertInstanceOf(RepeatJavascriptBuilder::class, $this->renderer->getJavascriptBuilder());
    }

    public function testRenderHidden(): void
    {
        $input = (new InputHidden('foo', ['id' => 'foo-id']))
            ->setValue('foo value');

        $input->render($this->renderer->setOption('group_hiddens', false));
        $renderSeparate = $this->renderer->toArray();

        $this::assertCount(0, $renderSeparate['hidden']);
        $this::assertCount(1, $renderSeparate['elements']);
        $this::assertEquals(
            $elArray = [
                'type'       => 'hidden',
                'attributes' => [
                    'name'  => 'foo',
                    'type'  => 'hidden',
                    'id'    => 'foo-id',
                    'value' => 'foo value'
                ]
            ],
            $renderSeparate['elements'][0]
        );
        $this::assertEquals(['foo' => 'foo value'], $renderSeparate['values']);

        $this->renderer->setOption('group_hiddens', true)
            ->reset();

        $input->render($this->renderer);
        $renderGrouped = $this->renderer->toArray();

        $this::assertCount(1, $renderGrouped['hidden']);
        $this::assertCount(0, $renderGrouped['elements']);
        $this::assertEquals($elArray, $renderGrouped['hidden'][0]);
        $this::assertEquals(['foo' => 'foo value'], $renderSeparate['values']);
    }

    public function testDisallowsInlineScript(): void
    {
        $this::expectException(InvalidArgumentException::class);
        $this::expectExceptionMessage("inline 'script'");

        $script = (new Script())->setContent("alert('foo');");
        $script->render($this->renderer);
    }

    public function testRenderElements(): void
    {
        $form = new QuickForm('elements', 'get', [], false);

        $form->addText('foo', ['id' => 'foo-id'])
            ->setError('element error')
            ->addRule('required', '!');

        $form->addSelect('single')
            ->loadOptions(['One', 'Two'])
            ->setValue(1)
            ->setLabel('a label:');
        $form->addSelect('multiple', ['multiple'])
            ->loadOptions(['One', 'Two', 'Three'])
            ->setValue(1)
            ->addRule('nonempty', '!', 1, Rule::CLIENT_SERVER);

        $form->addRadio('radiobutton', ['value' => 'one']);
        $form->addRadio('radiobutton', ['value' => 'two'])
            ->setAttribute('checked');
        $form->addRadio('radiobutton', ['value' => 'three']);

        $form->addCheckbox('box[]', ['value' => 'one']);
        $form->addCheckbox('box[]', ['value' => 'two'])
            ->setAttribute('checked');
        $form->addCheckbox('box[]', ['value' => 'three']);

        $form->addStatic('', ['href' => 'http://localhost/'])
            ->setTagName('a')
            ->setContent('back');

        $form->addSubmit('submitBtn', ['value' => 'Click!']);

        $form->render($this->renderer);
        $array = $this->renderer->toArray();

        $this::assertCount(11, $array['elements']);
        $this::assertEquals('get', $array['attributes']['method']);
        $this::assertEquals(
            [
                'foo'         => null,
                'single'      => '1',
                'multiple[]'  => ['1'],
                'radiobutton' => 'two',
                'box[]'       => ['two']
            ],
            $array['values']
        );
        $this::assertEquals(['foo-id' => 'element error'], $array['errors']);
        $this::assertCount(0, $array['hidden']);
        $this::assertTrue($array['hasRequired']);
        $this::assertCount(1, $array['rules']);

        $this::assertEqualsCanonicalizing(
            ['type', 'attributes', 'frozen', 'required'],
            \array_keys($array['elements'][0])
        );

        $this::assertEqualsCanonicalizing(
            ['type', 'attributes', 'frozen', 'required', 'label', 'options'],
            \array_keys($array['elements'][1])
        );

        $this::assertEqualsCanonicalizing(
            ['type', 'attributes', 'frozen', 'required', 'options'],
            \array_keys($array['elements'][2])
        );
        $this::assertCount(3, $array['elements'][2]['options']);
        $this::assertEquals('multiple[]', $array['elements'][2]['attributes']['name']);

        $this::assertEqualsCanonicalizing(
            ['type', 'attributes', 'frozen', 'required', 'tagName', 'content'],
            \array_keys($array['elements'][9])
        );
    }

    public function testSingleCheckboxWithBrackets(): void
    {
        $form = new QuickForm('boxes', 'get', [], false);

        $form->addCheckbox('unchecked[]', ['value' => 'no']);
        $form->addCheckbox('checked[]', ['value' => 'yes'])
            ->setAttribute('checked');

        $form->render($this->renderer);
        $array = $this->renderer->toArray();

        $this::assertEquals(
            [
                'unchecked[]' => [],
                'checked[]'   => ['yes']
            ],
            $array['values']
        );
    }

    public function testRenderWithStyle(): void
    {
        $form = new QuickForm('jsonStyle');
        $form->addText('foo', ['id' => 'testWithStyle']);
        $form->addText('bar', ['id' => 'testWithoutStyle']);

        $form->render($this->renderer->setStyleForId('testWithStyle', ['some', 'style', 'data']));

        $array = $this->renderer->toArray();
        $this->assertEquals(['some', 'style', 'data'], $array['elements'][0]['style']);
        $this->assertArrayNotHasKey('style', $array['elements'][1]);
    }

    public function testRenderContainers(): void
    {
        $form  = new QuickForm('containers');
        $fsOne = $form->addFieldset('', ['id' => 'fieldset-one']);
        $fsTwo = $fsOne->addFieldset('', ['id' => 'fieldset-two']);
        $fsTwo->addText('inner');
        $form->addTextarea('outer');

        $form->render($this->renderer);
        $array = $this->renderer->toArray();

        $this::assertCount(2, $array['elements']);

        $this::assertEqualsCanonicalizing(
            ['type', 'attributes', 'frozen', 'required', 'elements'],
            \array_keys($array['elements'][0])
        );

        $this::assertCount(1, $array['elements'][0]['elements']);
        $this::assertEquals('fieldset-two', $array['elements'][0]['elements'][0]['attributes']['id']);

        $this::assertCount(1, $array['elements'][0]['elements'][0]['elements']);
        $this::assertEquals('inner', $array['elements'][0]['elements'][0]['elements'][0]['attributes']['name']);

        $this::assertEquals('outer', $array['elements'][1]['attributes']['name']);
    }

    public function testRenderRepeat(): void
    {
        $form = new QuickForm('repeat', 'get', [], false);
        $repeat = $form->addRepeat()
            ->setPrototype(new Fieldset('', ['id' => 'repeatable-:idx:']))
            ->setId('test-repeat')
            ->setIndexes(['one', 'two', 'three']);

        $repeat->addText('text[:idx:]', ['id' => 'repeated-text-:idx:'])
            ->setLabel('Some text:')
            ->addRule('nonempty', 'Enter text', null, Rule::ONBLUR_CLIENT_SERVER);

        $form->render($this->renderer);
        $array = $this->renderer->toArray();

        $this::assertEqualsCanonicalizing(
            ['type', 'attributes', 'frozen', 'required', 'repeatable', 'indexes', 'rules', 'triggers'],
            \array_keys($array['elements'][0])
        );

        $this::assertEquals(['one', 'two', 'three'], $array['elements'][0]['indexes']);
        $this::assertEqualsCanonicalizing(
            ['repeatable-:idx:', 'repeated-text-:idx:'],
            $array['elements'][0]['triggers']
        );
        $this::assertStringContainsString('qf.rules.nonempty', $array['elements'][0]['rules']);
        $this::assertEqualsCanonicalizing(
            [
                'text[:idx:]' => null,
                'text[one]'   => null,
                'text[two]'   => null,
                'text[three]' => null
            ],
            $array['values']
        );

        $this::assertEqualsCanonicalizing(
            ['type', 'attributes', 'frozen', 'required', 'elements'],
            \array_keys($array['elements'][0]['repeatable'])
        );
        $this::assertEquals('repeatable-:idx:', $array['elements'][0]['repeatable']['attributes']['id']);
    }
}
