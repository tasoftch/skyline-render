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

namespace Skyline\Render\Context;


use Skyline\Render\AbstractRender;
use Skyline\Render\Exception\TemplateNotFoundException;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Model\ModelInterface;
use Skyline\Render\Service\AbstractTemplateController;
use Skyline\Render\Service\OrganizedTemplateControllerInterface;
use Skyline\Render\Service\TemplateControllerInterface;
use Skyline\Render\Template\AbstractTemplate;
use Skyline\Render\Template\TemplateInterface;
use TASoft\Service\ServiceForwarderTrait;
use TASoft\Service\ServiceManager;

class DefaultRenderContext implements RenderContextInterface
{
    use ServiceForwarderTrait;

    /** @var RenderInfoInterface */
    private $renderInfo;

    /**
     * The current version of Skyline CMS
     * @return string
     */
    public function getSkylineVersion() {
        return "v1.0";
    }

    /**
     * The public accessable website URL for Skyline CMS
     * @return string
     */
    public function getSkylineWebsiteURL() {
        return "https://www.skyline-cms.ch/";
    }

    /**
     * @inheritDoc
     */
    public function getValue(string $key, $default = NULL, int $depth = NULL)
    {
        /** @var ModelInterface $model */
        if(is_null($depth))
            $depth = 7;


        if($depth & self::VALUE_DEPTH_SUB_TEMPLATES) {
            $ha = $this->getRenderInfo()->get( RenderInfoInterface::INFO_SUB_TEMPLATES );

            if(is_array($ha)) {
                /** @var AbstractTemplate $template */
                foreach($ha as $template) {
                    if($template instanceof AbstractTemplate) {
                        $value = $template->getAttribute($key);
                        if($value)
                            return $value;
                    }
                }
            }
        }

        if($depth & self::VALUE_DEPTH_TEMPLATE) {
            $template = $this->getRenderInfo()->get( RenderInfoInterface::INFO_TEMPLATE );
            if($template) {
                $value = $template->getAttribute($key);
                if($value)
                    return $value;
            }
        }

        if($depth & self::VALUE_DEPTH_PARAMETERS) {
            $sm = ServiceManager::generalServiceManager();
            $value = $sm->getParameter($key);
            if($value)
                return $value;
        }

        return $default;
    }

    /**
     * @return RenderInfoInterface
     */
    public function getRenderInfo(): RenderInfoInterface
    {
        return $this->renderInfo;
    }

    /**
     * @param RenderInfoInterface $renderInfo
     */
    public function setRenderInfo(RenderInfoInterface $renderInfo): void
    {
        $this->renderInfo = $renderInfo;
    }

    /**
     * @param string|TemplateInterface|array $template
     */
    public function renderSubTemplate($template) {
        $render = AbstractRender::getCurrentRender();
        if(method_exists($render, 'renderTemplate')) {
            $tmp = NULL;
            /** @var TemplateControllerInterface $tc */
            $tc = $this->templateController;

            if(is_string($template)) {

                if($templates = $this->getRenderInfo()->get( RenderInfoInterface::INFO_SUB_TEMPLATES )) {
                    $tmp = $templates[$template];

                    if(is_array($tmp))
                        $template = $tmp;
                }
            }

            if(is_array($template) && $tc instanceof OrganizedTemplateControllerInterface) {
                $tmp = $tc->findTemplateWithTags($template);
            }

            if(is_string($template))
                $tmp = $tc->getTemplate($tmp);

            if(!($tmp instanceof TemplateInterface)) {
                $e = new TemplateNotFoundException("Template not $tmp found");
                $e->setTemplateID((string) $tmp);
                throw $e;
            }

            $render->renderTemplate($tmp, $this->getRenderInfo());
        }
    }
}