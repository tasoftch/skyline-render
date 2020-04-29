<?php

namespace Skyline\Render\Compiler;

use Skyline\Kernel\ExposeClassInterface;

/**
 * Any service implementing
 *
 * @package Skyline\Render\Compiler
 */
interface ContextMethodForwarderInterface extends ExposeClassInterface
{
	/**
	 * Returns a string defining the service name to forward a method call.
	 *
	 * @return string
	 */
	public static function getServiceName(): string;

	const PURPOSE_CONTEXT_FORWARDING = 'CTX_FORWARDING';
}