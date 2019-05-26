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

class CaptureContentsPlugin implements RenderPluginInterface
{
    public function initialize(EventManagerInterface $eventManager)
    {
        $eventManager->addListener(AbstractRender::EVENT_PRE_RENDER, $this, 0);
        $eventManager->addListener(AbstractRender::EVENT_POST_RENDER, $this, 0);
    }

    public function __invoke(string $eventName, InternRenderEvent $event, AbstractRender $eventManager, ...$arguments)
    {
        if($eventName == AbstractRender::EVENT_PRE_RENDER) {
            ob_start();
        }
        if($eventName == AbstractRender::EVENT_POST_RENDER) {
            $contents = ob_get_contents();
            ob_end_clean();
            if($response = $event->getResponse()) {
                $response->setContent($contents);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function tearDown()
    {
    }
}