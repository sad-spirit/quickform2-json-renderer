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

/**
 * A reimplementation of \HTML_QuickForm2_Container_Repeat_JavascriptBuilder from base package
 *
 * This returns a string that is expected to be eval()'d when adding a new item to repeat.
 */
class RepeatJavascriptBuilder extends JavascriptBuilder
{
    /**
     * Fake "current form" ID
     * @var string
     */
    protected $formId = 'repeat';

    public function getRulesAsString(): string
    {
        return empty($this->ruleParts['repeat']) ? '' : self::encode($this->ruleParts['repeat']);
    }
}
