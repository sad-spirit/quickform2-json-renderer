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

namespace sad_spirit\html_quickform2\json_renderer\builders;

use sad_spirit\html_quickform2\json_renderer\{
    decorators\RuleDecorator,
    exceptions\InvalidArgumentException,
    exceptions\LogicException
};
use HTML_QuickForm2_JavascriptBuilder as BaseJavascriptBuilder;
use HTML_QuickForm2_Rule as Rule;

/**
 * Aggregates client-side validation rules for JSON representation of the form
 */
class JavascriptBuilder extends BaseJavascriptBuilder
{
    /**
     * Parts of client rules
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected array $ruleParts = [];

    public function addRule(Rule $rule, $triggers = false)
    {
        $this->ruleParts[$this->formId][] = (new RuleDecorator($rule))->getJavascriptParts($triggers);
    }

    public function addElementJavascript($script)
    {
        // Repeat elements will be properly set up elsewhere, everything else is not supported
        if (0 !== \strpos($script, 'new qf.elements.Repeat(')) {
            throw new InvalidArgumentException(\get_class($this) . ' does not handle element setup scripts');
        }
    }

    public function getValidator($formId = null, $addScriptTags = false): string
    {
        throw new LogicException(
            \get_class($this) . ' should not be used to build inline JavaScript containing validation code'
        );
    }

    /**
     * Returns validation rules prepared by {@see RuleDecorator::getJavascriptParts()} for the given form
     *
     * We do not override getValidator() as it is defined as returning string
     *
     * @param string|null $formId Form "id" attribute, if null returns rules for all forms
     * @return array
     */
    public function getRules(?string $formId = null): array
    {
        $rules = [];

        foreach ($this->ruleParts as $id => $formRules) {
            if (
                (null === $formId || $id === $formId)
                && ([] !== $formRules || $this->forceValidator[$id])
            ) {
                $rules = \array_merge($rules, $formRules);
            }
        }

        return $rules;
    }
}
