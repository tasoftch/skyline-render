<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Skyline\Render\Plugin;


use Skyline\Kernel\Exception\SkylineKernelDetailedException;
use Skyline\Render\AbstractRender;
use Skyline\Render\Event\InternRenderEvent;
use Skyline\Render\Exception\TemplateNotFoundException;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Template\Extension\ExtendableAwareTemplateInterface;
use Skyline\Render\Template\Extension\ExtendableTemplateInterface;
use Skyline\Render\Template\Extension\TemplateExtensionInterface;
use Skyline\Render\Template\TemplateInterface;
use TASoft\EventManager\EventManagerInterface;

class RenderTemplateDefaultDispatchPlugin extends RenderTemplateDispatchPlugin
{
    /**
     * @inheritDoc
     */
    public function tearDown()
    {
    }

    public function initialize(EventManagerInterface $eventManager)
    {
        parent::initialize($eventManager);
        $eventManager->addListener(static::EVENT_BODY_RENDER, $this, 100);
    }

    public function __invoke(string $eventName, InternRenderEvent $event, AbstractRender $eventManager, ...$arguments)
    {
        $template = $event->getInfo()->get( RenderInfoInterface::INFO_TEMPLATE );
        if(!$template) {
            $e = new SkylineKernelDetailedException("No template specified for render");
            $e->setDetails("Your action controller MUST specify either a response or a template to render.");
            throw $e;
        }
        $renderInfo = $event->getInfo();

        static $beforeBody = [];
        static $afterBody = [];
        static $footer = [];

        if($eventName == static::EVENT_HEADER_RENDER) {
            // Capture header event to get extensions (if available) that need to be rendered in header phase
            $beforeBody = $afterBody = $footer = [];

            if($template instanceof ExtendableTemplateInterface) {
                $extLoader = function(ExtendableTemplateInterface $templateWithExtensions) use (&$extLoader, &$beforeBody, &$afterBody, &$footer, $eventManager, $renderInfo) {

                    if($templateWithExtensions instanceof ExtendableAwareTemplateInterface)
                        $this->loadExtensionAwareTemplate($templateWithExtensions);

                    foreach ($templateWithExtensions->getTemplateExtensions() as $reuseIdentifier => $extension) {
                        if($extension instanceof TemplateExtensionInterface) {
                            // Recursive load further extensions of extension
                            if($extension instanceof ExtendableTemplateInterface)
                                $extLoader($extension);

                            switch ($extension->getPosition()) {
                                case TemplateExtensionInterface::POSITION_HEADER:
                                    $this->renderHeader($eventManager, $extension, $renderInfo);
                                    break;
                                case TemplateExtensionInterface::POSITION_BEFORE_BODY:
                                    $beforeBody[$reuseIdentifier] = $extension;
                                    break;
                                case TemplateExtensionInterface::POSITION_AFTER_BODY:
                                    $afterBody[$reuseIdentifier] = $extension;
                                    break;
                                case TemplateExtensionInterface::POSITION_FOOTER:
                                    $footer[$reuseIdentifier] = $extension;
                                    break;
                                default:
                                    trigger_error("Unknown position " . $extension->getPosition() . " of extension $reuseIdentifier", E_USER_WARNING);
                            }
                        }
                    }
                };

                $extLoader($template);
            }
        } elseif($eventName == static::EVENT_BODY_RENDER) {
            // Render the template right now in body phase
            $this->renderBeforeBody($eventManager, $beforeBody, $renderInfo);
            $eventManager->renderTemplate($template, $event->getInfo());
            $this->renderAfterBody($eventManager, $afterBody, $renderInfo);
        } elseif($eventName == static::EVENT_FOOTER_RENDER) {
            $this->renderFooter($eventManager, $footer, $renderInfo);
        } else
            parent::__invoke($eventName, $event, $eventManager, $arguments);
    }

    /**
     * This method is called for templates that know about their extensions.
     *
     * @param ExtendableAwareTemplateInterface $template
     */
    protected function loadExtensionAwareTemplate(ExtendableAwareTemplateInterface $template) {
        $render = AbstractRender::getCurrentRender();
        $tc = $render->getTemplateController();

        foreach($template->getRequiredExtensionIdentifiers() as $reuse => $identifier) {
            $tmp = $tc->getTemplate($identifier);
            if(!$tmp) {
                $e = new TemplateNotFoundException("Template $identifier not found");
                $e->setRender($render);
                $e->setTemplateID($identifier);
                throw $e;
            }

            if($tmp instanceof TemplateExtensionInterface)
                $template->registerExtension($tmp, is_string($reuse) ? $reuse : NULL);
            else {
                trigger_error(sprintf("Template %s (%s) does not implement %s", $tmp->getName(), $tmp->getID(), TemplateExtensionInterface::class), E_USER_WARNING);
            }
        }

        foreach($template->getOptionalExtensionIdentifiers() as $reuse => $identifier) {
            $tmp = $tc->getTemplate($identifier);
            if($tmp) {
                if($tmp instanceof TemplateExtensionInterface)
                    $template->registerExtension($tmp, is_string($reuse) ? $reuse : NULL);
                else {
                    trigger_error(sprintf("Template %s (%s) does not implement %s", $tmp->getName(), $tmp->getID(), TemplateExtensionInterface::class), E_USER_WARNING);
                }
            }
        }
    }

    /**
     * Called to render a header template. Please note that this method can be called multiple times with different header templates
     *
     * @param AbstractRender $render
     * @param TemplateExtensionInterface $headerTemplate
     * @param RenderInfoInterface $renderInfo
     */
    protected function renderHeader(AbstractRender $render, TemplateExtensionInterface $headerTemplate, RenderInfoInterface $renderInfo) {
        $render->renderTemplate($headerTemplate, $renderInfo);
    }

    /**
     * @param AbstractRender $render
     * @param TemplateExtensionInterface[] $templates
     * @param RenderInfoInterface $renderInfo
     */
    protected function renderBeforeBody(AbstractRender $render, array $templates, RenderInfoInterface $renderInfo) {
        foreach($templates as $template)
            $render->renderTemplate($template, $renderInfo);
    }

    /**
     * @param AbstractRender $render
     * @param TemplateInterface $bodyTemplate
     * @param RenderInfoInterface $renderInfo
     */
    protected function renderBody(AbstractRender $render, TemplateInterface $bodyTemplate, RenderInfoInterface $renderInfo) {
        $render->renderTemplate($bodyTemplate, $renderInfo);
    }

    /**
     * @param AbstractRender $render
     * @param TemplateExtensionInterface[] $templates
     * @param RenderInfoInterface $renderInfo
     */
    protected function renderAfterBody(AbstractRender $render, array $templates, RenderInfoInterface $renderInfo) {
        foreach($templates as $template)
            $render->renderTemplate($template, $renderInfo);
    }

    /**
     * @param AbstractRender $render
     * @param TemplateExtensionInterface[] $templates
     * @param RenderInfoInterface $renderInfo
     */
    protected function renderFooter(AbstractRender $render, array $templates, RenderInfoInterface $renderInfo) {
        foreach($templates as $template)
            $render->renderTemplate($template, $renderInfo);
    }
}