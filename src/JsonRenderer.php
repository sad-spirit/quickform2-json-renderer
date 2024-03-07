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

namespace sad_spirit\html_quickform2\json_renderer;

use sad_spirit\html_quickform2\json_renderer\{
    builders\JavascriptBuilder,
    builders\RepeatJavascriptBuilder,
    exceptions\InvalidArgumentException
};
use HTML_QuickForm2_Container_Repeat as Repeat;
use HTML_QuickForm2_Container_Repeat_JavascriptBuilder as BaseRepeatJavascriptBuilder;
use HTML_QuickForm2_Element_Input as Input;
use HTML_QuickForm2_Element_InputButton as InputButton;
use HTML_QuickForm2_Element_InputFile as InputFile;
use HTML_QuickForm2_Element_InputImage as InputImage;
use HTML_QuickForm2_Element_InputRadio as InputRadio;
use HTML_QuickForm2_Element_InputReset as InputReset;
use HTML_QuickForm2_Element_InputSubmit as InputSubmit;
use HTML_QuickForm2_Element_Script as Script;
use HTML_QuickForm2_Element_Select as Select;
use HTML_QuickForm2_Element_Select_OptionContainer as OptionContainer;
use HTML_QuickForm2_Element_Static as StaticElement;
use HTML_QuickForm2_Element_Textarea as Textarea;
use HTML_QuickForm2_JavascriptBuilder as BaseJavascriptBuilder;
use HTML_QuickForm2_Node as Node;
use HTML_QuickForm2_Renderer as Renderer;

/**
 * Converts the form to an array for further serialization to JSON and client-side rebuilding
 *
 * Unlike \HTML_QuickForm2_Renderer_Array, the resultant array does not contain generated HTML for form elements
 * and complete JavaScript code to insert into the page.
 *
 * NB: This doesn't implement JsonSerializable, as Renderers are always accessed through
 * \HTML_QuickForm2_Renderer_Proxy.
 */
class JsonRenderer extends Renderer
{
    /**
     * Attributes for the &lt;form> tag
     * @var array<string, string>
     */
    private array $attributes = [];

    /**
     * Elements that are immediate children of the form
     * @var list<array<string, mixed>>
     */
    private array $elements = [];

    /**
     * Mapping 'element name' => 'element value'
     * @var array<string, mixed>
     */
    private array $values = [];

    /**
     * Mapping 'element id' => 'element error' ('group_errors' option is treated as always true)
     * @var array<string, string>
     */
    private array $errors = [];

    /**
     * Aggregated hidden elements, if 'group_hiddens' option is true
     * @var list<array<string, mixed>>
     */
    private array $hidden = [];

    /**
     * Whether form contains required elements that should be marked in output
     * @var bool
     */
    private bool $hasRequired = false;

    /**
     * Array with references to 'elements' fields of currently processed containers
     * @var array<int, array<int, array<string, mixed>>>
     */
    private array $containers = [];

    /**
     * Data for building client-side validation rules
     * @var list<array<string, mixed>>
     */
    private array $rules = [];

    /**
     * JavascriptBuilder for Repeat elements
     * @var RepeatJavascriptBuilder
     */
    private RepeatJavascriptBuilder $repeatJS;

    /**
     * Opaque "styles" for element rendering on client side
     * @var array<string, mixed>
     */
    private array $styles = [];

    protected function exportMethods(): array
    {
        return [
            'toArray',
            'setStyleForId'
        ];
    }

    protected function __construct()
    {
        parent::__construct();
        $this->reset();
    }

    public function reset(): void
    {
        $this->attributes  = [];
        $this->elements    = [];
        $this->values      = [];
        $this->errors      = [];
        $this->hidden      = [];
        $this->containers  = [&$this->elements];
        $this->hasRequired = false;
        $this->rules       = [];
        $this->repeatJS    = new RepeatJavascriptBuilder();
    }

    public function renderElement(Node $element): void
    {
        $this->addValue($element);
        $this->pushElement($this->processElement($element));
    }

    public function renderHidden(Node $element): void
    {
        if ($element instanceof Script) {
            throw new InvalidArgumentException(\get_class($this) . " cannot render inline 'script' elements");
        }

        $this->addValue($element);
        if ($this->options['group_hiddens']) {
            $this->hidden[] = $this->processHidden($element);
        } else {
            $this->pushElement($this->processHidden($element));
        }
    }

    public function startForm(Node $form): void
    {
        $this->reset();

        $this->attributes = $form->getAttributes();
        if ('' !== ($error = $form->getError())) {
            $this->errors[(string)$form->getId()] = $error;
        }
    }

    public function finishForm(Node $form): void
    {
        $this->finishContainer($form);
        $this->rules = $this->getJavascriptBuilder()->getRules($form->getId());
    }

    public function startContainer(Node $container): void
    {
        $this->pushContainer($this->processVisible($container) + ['elements' => []]);
    }

    public function finishContainer(Node $container): void
    {
        \array_pop($this->containers);

        if ($container instanceof Repeat) {
            $cntIndex  =  \count($this->containers) - 1;
            $repeatAry =& $this->containers[$cntIndex][\count($this->containers[$cntIndex]) - 1];

            // We don't need elements with indexes substituted: they will be generated on client side based on
            // the 'repeatable' field. It is not named 'prototype' as the latter has a very special meaning in JS.
            $repeatAry['repeatable'] = \array_shift($repeatAry['elements']);
            unset($repeatAry['elements']);

            $repeatAry['indexes']    = \array_map(fn($item) => (string)$item, $container->getIndexes());
            // Similar to 'rules' and 'triggers' in original qf.elements.Repeat
            $repeatAry['rules']      = $this->repeatJS->getRulesAsString();
            $repeatAry['triggers']   = [];
            foreach ($container->getRecursiveIterator() as $child) {
                $repeatAry['triggers'][] = $child->getId();
            }

            $this->repeatJS = new RepeatJavascriptBuilder();
        }
    }

    public function startGroup(Node $group): void
    {
        $attributes = ['elements' => []];
        /** @var \HTML_QuickForm2_Container_Group $group */
        if (null !== ($separator = $group->getSeparator())) {
            $attributes['separator'] = [];
            for ($i = 0, $count = \count($group); $i < $count - 1; $i++) {
                if (!\is_array($separator)) {
                    $attributes['separator'][] = (string)$separator;
                } else {
                    $attributes['separator'][] = $separator[$i % \count($separator)];
                }
            }
        }

        $this->pushContainer($this->processVisible($group) + $attributes);
    }

    public function finishGroup(Node $group): void
    {
        $this->finishContainer($group);
    }

    /**
     * {@inheritDoc}
     *
     * This will always return an instance of JavascriptBuilder from this package,
     * other implementations are disallowed by {@see setJavascriptBuilder()}
     *
     * @return JavascriptBuilder
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function getJavascriptBuilder(): JavascriptBuilder
    {
        if (empty($this->jsBuilder)) {
            $this->jsBuilder = new JavascriptBuilder();
        }
        return $this->jsBuilder;
    }

    /**
     * {@inheritDoc}
     *
     * The method will accept either an instance of {@see JavascriptBuilder} from this package or
     * an instance of {@see \HTML_QuickForm2_Container_Repeat_JavascriptBuilder} that will be replaced by
     * {@see RepeatJavascriptBuilder}. Everything else will cause an exception.
     */
    public function setJavascriptBuilder(BaseJavascriptBuilder $builder = null): self
    {
        if (null !== $builder) {
            if ($builder instanceof BaseRepeatJavascriptBuilder) {
                $this->repeatJS = $builder = new RepeatJavascriptBuilder();
            } elseif (!$builder instanceof JavascriptBuilder) {
                throw new InvalidArgumentException("Only instances of " . JavascriptBuilder::class . " allowed");
            }
        }
        return parent::setJavascriptBuilder($builder);
    }

    /**
     * Returns the resultant array
     *
     * @return array{attributes: array<string, string>, elements: list<array<string, mixed>>,
     *               values: array<string, mixed>, errors: array<string, string>, hidden: list<array<string, mixed>>,
     *               hasRequired: bool, rules: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'attributes'  => $this->attributes,
            'elements'    => $this->elements,
            'values'      => $this->values,
            'errors'      => $this->errors,
            'hidden'      => $this->hidden,
            'hasRequired' => $this->hasRequired,
            'rules'       => $this->rules
        ];
    }

    /**
     * Sets a style for element rendering
     *
     * "Style" is some information that is opaque to the Renderer but may be
     * of use to code that builds a form from the resultant JSON.
     *
     * @param string|array $idOrStyles Element id or array ('element id' => 'style')
     * @param mixed        $style      Element style if $idOrStyles is not an array
     *
     * @return $this
     */
    public function setStyleForId($idOrStyles, $style = null): self
    {
        if (\is_array($idOrStyles)) {
            $this->styles = \array_merge($this->styles, $idOrStyles);
        } else {
            $this->styles[$idOrStyles] = $style;
        }
        return $this;
    }

    /**
     * Adds the element's value to the 'values' field, if applicable
     *
     * @param Node $element
     * @return void
     */
    private function addValue(Node $element): void
    {
        if (!$this->hasDisplayValue($element)) {
            return;
        }

        $name  = (string)$element->getName();
        $value = $element->getValue();

        if ($element instanceof InputRadio) {
            if (null !== $value || !\array_key_exists($name, $this->values)) {
                $this->values[$name] = $value;
            }
        } else {
            if ($element instanceof Select && null !== $element->getAttribute('multiple')) {
                // This is only appended in element's __toString() method
                $name .= '[]';
            }
            if ('[]' === \substr($name, -2) && \array_key_exists($name, $this->values)) {
                $value = \array_merge((array)$this->values[$name], (array)$value);
            }
            $this->values[$name] = $value;
        }
    }


    /**
     * Checks whether an element has an initial display value that should be added to 'values' field
     *
     * @param Node $element
     * @return bool
     */
    protected function hasDisplayValue(Node $element): bool
    {
        return $element instanceof Select
            || $element instanceof Textarea
            || $element instanceof Input
                && !$element instanceof InputButton
                && !$element instanceof InputFile
                && !$element instanceof InputImage
                && !$element instanceof InputReset
                && !$element instanceof InputSubmit;
    }

    /**
     * Adds an array representing a "scalar" element to the current container
     *
     * @param array $element
     * @return void
     */
    private function pushElement(array $element): void
    {
        $this->containers[\count($this->containers) - 1][] = $element;
    }

    /**
     * Adds an array representing a container element to the current container
     *
     * @param array $container
     * @return void
     */
    private function pushContainer(array $container): void
    {
        $cntIndex = \count($this->containers) - 1;
        $myIndex  = \count($this->containers[$cntIndex]);
        $this->containers[$cntIndex][$myIndex] = $container;
        $this->containers[$cntIndex + 1] =& $this->containers[$cntIndex][$myIndex]['elements'];
    }

    /**
     * Creates an array representing &lt;input type="hidden" /> and similar elements
     *
     * These obviously cannot have labels, validation errors and similar fields
     *
     * @param Node $element
     * @return array{type: string, attributes: array}
     */
    private function processHidden(Node $element): array
    {
        return [
            'type'       => $element->getType(),
            'attributes' => $element->getAttributes()
        ];
    }

    /**
     * Creates an array representing a visible form element
     *
     * @param Node $element
     * @return array{type: string, attributes: array, frozen: bool, required: bool, label: null|string|string[],
     *               content?: ?string, style?: mixed}
     */
    private function processVisible(Node $element): array
    {
        if ('' !== ($error = $element->getError())) {
            $this->errors[(string)$element->getId()] = $error;
        }

        $converted = [
            // Classes for <button> and <input type="button"> both return 'button', that's bad
            'type'       => $element instanceof InputButton ? 'input-button' : $element->getType(),
            'attributes' => $element->getAttributes(),
            'frozen'     => $element->toggleFrozen(),
            'required'   => $element->isRequired()
        ];
        if ($converted['required']) {
            $this->hasRequired = true;
        }
        if (null !== ($label = $element->getLabel())) {
            $converted['label'] = $label;
        }
        if (\method_exists($element, 'getContent')) {
            $converted['content'] = $element->getContent();
        }
        if (\array_key_exists($converted['attributes']['id'], $this->styles)) {
            $converted['style'] = $this->styles[$converted['attributes']['id']];
        }

        return $converted;
    }

    /**
     * Creates an array for the options of the &lt;select> element
     *
     * @param OptionContainer $container
     * @return list<array>
     */
    private function processOptionContainer(OptionContainer $container): array
    {
        $converted = [];
        foreach ($container as $option) {
            if ($option instanceof OptionContainer) {
                $converted[] = [
                    'options'    => $this->processOptionContainer($option),
                    'attributes' => $option->getAttributes()
                ];
            } else {
                $converted[] = [
                    'text'       => $option['text'],
                    'attributes' => $option['attr']
                ];
            }
        }
        return $converted;
    }

    /**
     * Creates an array representing a "scalar" element
     *
     * @param Node $element
     * @return array{type: string, attributes: array, frozen: bool, required: bool, label: null|string|string[],
     *               content?: ?string, style?: mixed, tagName?: ?string, options?: list<array>}
     */
    private function processElement(Node $element): array
    {
        $converted = $this->processVisible($element);

        if ($element instanceof StaticElement) {
            $converted['tagName'] = $element->getTagName();
        }
        if ($element instanceof Select) {
            if (!empty($converted['attributes']['multiple'])) {
                $converted['attributes']['name'] .= '[]';
            }
            $converted['options'] = $this->processOptionContainer($element->getOptionContainer());
        }

        return $converted;
    }
}
