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
use Skyline\Render\Model\BoundTemplateModelInterface;
use Skyline\Render\Model\ModelInterface;
use Skyline\Render\Service\OrganizedTemplateControllerInterface;
use Skyline\Render\Service\TemplateControllerInterface;
use Skyline\Render\Specification\Container;
use Skyline\Render\Specification\ID;
use Skyline\Render\Specification\Name;
use Skyline\Render\Specification\Tag;
use Skyline\Render\Template\_InternalBoundModelTemplate;
use Skyline\Render\Template\AbstractTemplate;
use Skyline\Render\Template\AdvancedTemplateInterface;
use Skyline\Render\Template\Catalog;
use Skyline\Render\Template\RenderableInterface;
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
     * @param ID|Name|Catalog|Tag|Container ...$specifications
     * @return TemplateInterface[]|RenderableInterface[]|null
     */
    public function findTemplate(...$specifications) {
        $container = new Container(...$specifications);
        /** @var TemplateControllerInterface $tc */
        if($tc = $this->templateController) {
            if($id = $container->getId())
                return [$tc->getTemplate( (string)$id )];

            if($tc instanceof OrganizedTemplateControllerInterface) {
                $templates = NULL;

                if($catalog = $container->getCatalog()) {
                    $templates = $tc->findTemplatesInCatalog((string) $catalog);
                }

                if($tags = $container->getTags()) {
                    $all = $container->isMatchingAllTags();
                    $tags = array_map(function($a){return(string)$a;},$tags);

                    if($templates) {
                        $templates = array_filter($templates, function($tmp) use ($tags, $all) {
                            if($tmp instanceof AdvancedTemplateInterface) {
                                $myTags = $tmp->getTags();

                                if($all) {
                                    foreach($tags as $tag) {
                                        if(!in_array($tag, $myTags))
                                            return false;
                                    }
                                    return true;
                                } else {
                                    foreach($myTags as $tag) {
                                        if(in_array($tag, $tags))
                                            return true;
                                    }
                                }
                            }
                            return false;
                        });
                    } else {
                        $templates = $tc->findTemplatesWithTags( $tags, $all );
                    }
                }

                if($name = $container->getName()) {
                    if($templates) {
                        $templates = array_filter($templates, function($tmp) use ($name) {
                            if($tmp instanceof TemplateInterface) {
                                if($tmp->getName() == (string)$name)
                                    return true;
                            }
                            return false;
                        });
                    } else {
                        $templates = $tc->findTemplatesWithName((string)$name);
                    }
                }

                return $templates;
            }
        }
        return NULL;
    }

    /**
     * Renders a subtemplate
     *
     * @param string|TemplateInterface|BoundTemplateModelInterface|array $template
     * @param mixed $additionalInfo
     */
    public function renderSubTemplate($template, $additionalInfo = NULL) {
        $render = AbstractRender::getCurrentRender();
        if(method_exists($render, 'renderTemplate')) {
            $tmp = NULL;

            if($template instanceof BoundTemplateModelInterface) {
                _InternalBoundModelTemplate::$current = new _InternalBoundModelTemplate($template, $tmp);
                $template = $template->getTemplate();
            }

            if($template instanceof TemplateInterface || $template instanceof RenderableInterface)
                $tmp = $template;
            else {
                /** @var TemplateControllerInterface $tc */
                $tc = $this->templateController;

                if(is_string($template)) {

                    if($templates = $this->getRenderInfo()->get( RenderInfoInterface::INFO_SUB_TEMPLATES )) {
                        $tmp = $templates[$template] ?? NULL;

                        if(is_array($tmp) || $tmp instanceof TemplateInterface)
                            $template = $tmp;
                    }
                }

                if(is_array($template) && $tc instanceof OrganizedTemplateControllerInterface) {
                    $tmp = $tc->findTemplateWithTags($template);
                }

                if(is_string($template)) {
                    if($tc instanceof OrganizedTemplateControllerInterface)
                        $tmp = $tc->findTemplateWithName($template);
                    else
                        $tmp = $tc->getTemplate($template);
                }
            }

            if($tmp instanceof RenderableInterface) {
                $tmp->renderContents($additionalInfo);
                return;
            }

            if(!($tmp instanceof TemplateInterface)) {
                $e = new TemplateNotFoundException(is_string($template) ? "Requested sub template $template not found" : "Requested sub template not found");
                $e->setTemplateID((string) $tmp);
                throw $e;
            }

            $render->renderTemplate($tmp, $additionalInfo);
            _InternalBoundModelTemplate::$current = NULL;
        }
    }

    /**
     * Checks, if a sub template with given name exists
     *
     * @param $template
     * @return bool
     */
    public function hasSubTemplate($template) {
        if($templates = $this->getRenderInfo()->get( RenderInfoInterface::INFO_SUB_TEMPLATES )) {
            return isset($templates[$template]);
        }
        return false;
    }

    /**
     * Access to direct information on render info
     *
     * @return mixed|null
     */
    public function getAdditionalInfo() {
        return $this->getRenderInfo()->get( RenderInfoInterface::INFO_ADDITIONAL_INFO );
    }
}