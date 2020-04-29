<?php

namespace Skyline\Render\Compiler;


use Skyline\Compiler\CompilerConfiguration;
use Skyline\Compiler\CompilerContext;
use Skyline\Expose\Compiler\AbstractAnnotationCompiler;
use Skyline\Compiler\Helper\ModuleStorageHelper;

class ContextAnnotationCompiler extends AbstractAnnotationCompiler
{
	private $contextFile;
	public function __construct(string $compilerID, string $contextFile = "", bool $excludeMagicMethods = true)
	{
		parent::__construct($compilerID, $excludeMagicMethods);
		$this->contextFile = $contextFile;
	}
	
	
	public function compile(CompilerContext $context)
	{
		$storage = new ModuleStorageHelper();
		
		foreach($this->yieldClasses(ContextMethodForwarderInterface::PURPOSE_CONTEXT_FORWARDING) as $controller) {
			$list = $this->findClassMethods($controller, self::OPT_PUBLIC_OBJECTIVE);
			if($list) {
				if(is_callable("$controller::getServiceName")) {
					foreach($list as $method) {
						$sn = call_user_func("$controller::getServiceName");

						$annotations = $this->getAnnotationsOfMethod($method, true);
						if($methNames = $annotations["context"] ?? NULL) {
							if($module = $this->getDeclaredModule($controller)) {
								$storage->pushModule($module);
							}
							
							foreach($methNames as $methName) {
								if(isset($storage[$methName])) {
									$owner = $storage[$methName]['class'];
									$mthd = $storage[$methName]['method'];

									trigger_error("Method $methName is already defined by $owner::$mthd", E_USER_WARNING);
								} else {
									$storage[$methName] = [
										'class' => $controller,
										'method' => $method->getName(),
										"service" => $sn
									];
								}
							}
						}
					}
				} else {
					trigger_error("Class $controller does not implement Skyline\Render\Compiler\ContextMethodForwarderInterface", E_USER_WARNING);
				}
			}
		}

		$dir = $context->getSkylineAppDirectory(CompilerConfiguration::SKYLINE_DIR_COMPILED);
		file_put_contents("$dir/$this->contextFile", $storage->exportStorage());
	}
}