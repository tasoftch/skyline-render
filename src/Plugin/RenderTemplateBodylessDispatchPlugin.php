<?php


namespace Skyline\Render\Plugin;


class RenderTemplateBodylessDispatchPlugin extends RenderTemplateDefaultDispatchPlugin
{
	protected function invokeMainBodyRender($eventManager, $before, $after, $renderInfo, $event, $template) {
		$this->renderBeforeBody($eventManager, $before, $renderInfo);
		$this->renderAfterBody($eventManager, $after, $renderInfo);
	}
}