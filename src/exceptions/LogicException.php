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

namespace sad_spirit\html_quickform2\json_renderer\exceptions;

use sad_spirit\html_quickform2\json_renderer\Exception;

/**
 * Namespaced version of SPL's LogicException
 */
class LogicException extends \LogicException implements Exception
{
}
