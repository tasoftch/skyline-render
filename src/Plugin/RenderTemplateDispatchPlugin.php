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
use TASoft\EventManager\EventManagerInterface;

/**
 * The render template dispatch plugin splits a main render event into head body and footer events.
 * @package Skyline\Render\Plugin
 */
class RenderTemplateDispatchPlugin implements RenderPluginInterface
{
    const EVENT_HEADER_RENDER = 'plugin.header';
    const EVENT_BODY_RENDER = 'plugin.body';
    const EVENT_FOOTER_RENDER = 'plugin.footer';

    /**
     * @inheritDoc
     */
    public function initialize(EventManagerInterface $eventManager)
    {
        $eventManager->addListener(AbstractRender::EVENT_MAIN_RENDER, $this, 0);
    }

    /**
     * Event handler
     *
     * @param string $eventName
     * @param InternRenderEvent $event
     * @param AbstractRender $eventManager
     * @param mixed ...$arguments
     */
    public function __invoke(string $eventName, InternRenderEvent $event, AbstractRender $eventManager, ...$arguments)
    {
        if($eventName == AbstractRender::EVENT_MAIN_RENDER) {
            $eventManager->trigger(static::EVENT_HEADER_RENDER, $event);
            $eventManager->trigger(static::EVENT_BODY_RENDER, $event);
            $eventManager->trigger(static::EVENT_FOOTER_RENDER, $event);
        }
    }
}