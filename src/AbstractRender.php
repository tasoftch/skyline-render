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

namespace Skyline\Render;


use Closure;
use Skyline\Render\Context\RenderContextInterface;
use Skyline\Render\Event\InternRenderEvent;
use Skyline\Render\Exception\RenderException;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Service\AbstractTemplateController;
use Skyline\Render\Template\TemplateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TASoft\DI\DependencyManager;
use TASoft\DI\Injector\ObjectListInjector;
use TASoft\EventManager\EventManagerInterface;
use TASoft\EventManager\EventManagerTrait;
use TASoft\Service\ServiceForwarderTrait;

abstract class AbstractRender implements RenderInterface, EventManagerInterface
{
    use EventManagerTrait;
    use ServiceForwarderTrait;


    const EVENT_PRE_RENDER = 'pre-render';
    const EVENT_POST_RENDER = 'post-render';
    const EVENT_MAIN_RENDER = 'main-render';

    /** @var Request */
    private $request;
    /** @var Response|null */
    private $response;

    /** @var AbstractRender|null */
    private static $currentRender;

    /**
     * @return AbstractRender|null
     */
    public static function getCurrentRender(): AbstractRender
    {
        if(!self::$currentRender) {
            $e = new RenderException("Calling current render when not in render context is not allowed");
            throw $e;
        }
        return self::$currentRender;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @return Response|null
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * @param Response|null $response
     */
    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }

    /**
     * @inheritDoc
     */
    public function render(RenderInfoInterface $renderInfo)
    {
        $event = new InternRenderEvent($this->getRequest(), $this, $renderInfo);
        $event->setResponse($this->getResponse());

        self::$currentRender = $this;

        $ctx = $this->getServiceManager()->get("renderContext");
        if($ctx instanceof RenderContextInterface) {
            $ctx->setRenderInfo($renderInfo);
        }

        $this->trigger(static::EVENT_PRE_RENDER, $event);
        $this->trigger(static::EVENT_MAIN_RENDER, $event);
        $this->trigger(static::EVENT_POST_RENDER, $event);

        self::$currentRender = NULL;
        $this->setResponse( $event->getResponse() );
    }

    /**
     * Called from plugins that handle renderable templates.
     *
     * @param callable $renderable
     * @return callable
     */
    public function modifyRenderable(callable $renderable): callable {
        if($renderable instanceof Closure)
            $renderable = $renderable->bindTo($this, static::class);
        return $renderable;
    }

    /**
     * This method should render a template without care about extensions and nestings.
     *
     * @param TemplateInterface $template
     * @param RenderInfoInterface|null $renderInfo
     */
    public function renderTemplate(TemplateInterface $template, RenderInfoInterface $renderInfo = NULL) {
        if($template instanceof TemplateInterface) {
            $dm = $this->getDependencyManager();
            $cb = $this->modifyRenderable( $template->getRenderable() );

            $dm->pushGroup(function() use ($renderInfo, $cb, $dm, $template) {
                $dm->addDependencyInjector(new ObjectListInjector([
                    'renderInfo' => $renderInfo,
                    "template" => $template
                ]));
                $dm->call($cb);
            });
        }
    }


    // Services

    /**
     * Fetches the dependency manager from services
     *
     * @return DependencyManager
     */
    public function getDependencyManager(): DependencyManager {
        $sm = $this->getServiceManager();
        /** @var DependencyManager $dm */
        $dm = $sm->get("dependencyManager");
        return $dm;
    }

    public function getTemplateController(): AbstractTemplateController {
        $sm = $this->getServiceManager();
        /** @var AbstractTemplateController $dm */
        $dm = $sm->get("templateController");
        return $dm;
    }

    public function getRenderController(): RenderInfoInterface {
        $sm = $this->getServiceManager();
        /** @var RenderInfoInterface $dm */
        $dm = $sm->get("renderController");
        return $dm;
    }
}