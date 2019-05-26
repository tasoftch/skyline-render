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


use Skyline\Render\AbstractRender;
use Skyline\Render\Event\InternRenderEvent;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Template\TemplateInterface;
use TASoft\DI\DependencyManager;
use TASoft\DI\Injector\ObjectListInjector;
use TASoft\EventManager\EventManagerInterface;
use TASoft\Service\ServiceForwarderTrait;

class RenderTemplateDefaultPlugin extends RenderTemplateDispatchPlugin
{
    use ServiceForwarderTrait;

    public function initialize(EventManagerInterface $eventManager)
    {
        parent::initialize($eventManager);
        $eventManager->addListener(static::EVENT_BODY_RENDER, $this, 100);
    }

    public function __invoke(string $eventName, InternRenderEvent $event, AbstractRender $eventManager, ...$arguments)
    {
        if($eventName == static::EVENT_BODY_RENDER) {
            $sm = $this->getServiceManager();
            /** @var DependencyManager $dm */
            $dm = $sm->get("dependencyManager");

            $template = $event->getInfo()->get( RenderInfoInterface::INFO_TEMPLATE );
            if($template instanceof TemplateInterface) {
                $cb = $eventManager->modifyRenderable( $template->getRenderable() );
                $dm->pushGroup(function() use ($event, $cb, $dm) {
                    $dm->addDependencyInjector(new ObjectListInjector([
                        'renderInfo' => $event->getInfo()
                    ]));
                    $dm->call($cb);
                });
            }
        } else
            parent::__invoke($eventName, $event, $eventManager, $arguments);
    }
}