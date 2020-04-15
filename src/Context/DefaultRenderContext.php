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


use Skyline\Kernel\Service\CORSService;
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
use Skyline\Render\Specification\Catalog;
use Skyline\Render\Template\RenderableInterface;
use Skyline\Render\Template\TemplateInterface;
use TASoft\Service\ServiceForwarderTrait;
use TASoft\Service\ServiceManager;

class DefaultRenderContext implements RenderContextInterface
{
    use ServiceForwarderTrait;

    /** @var int Encodes an ascii string into an utf-8 */
    const ENCODE_UTF8_STRING_MODE = 1;
    /** @var int encodes an utf-8 string into an ascii */
	const ENCODE_ASCII_STRING_MODE = 2;
	/** @var int Encodes a string into base64 data string */
	const ENCODE_BASE64_STRING_MODE = 3;
	/** @var int Encodes a string into base64 data string wrapped into script tag decoding it */
	const ENCODE_BASE64_JS_STRING_MODE = 4;

	/** @var int Encodes a string to not have HTML specific characters anymore */
	const ENCODE_HTML_STRING_MODE = 0;
	/** @var int Encodes a string into a valid URL string */
	const ENCODE_URL_STRING_MODE = 5;




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
     * Use this method in templates to refer to action controllers.
     * Normally an action controller holds a class constant for each reachable action.
     *
     * @param string $host                 The host, may be directly a host or a labelled registered host from compilation
     * @param string $URI                  The URI to append
     * @param bool $forceHost              If set, puts always http://host as prefix
     * @param mixed ...$arguments          Arguments to apply to the URL. List strings to apply into $0-9 markers, and an array to build query from
     * @return string
     */
    public function buildURL($host, $URI = '/', bool $forceHost = false,  ...$arguments) {
        $theArgs = [];
        $q = [];

        foreach($arguments as $arg) {
            if(is_array($arg))
                $q = $arg;
            else
                $theArgs[] = $arg;
        }

        $host = CORSService::getHostByLabel($host, $host);
        if($q) {
            $q = "?" . http_build_query($q);
        } else
            $q = '';

        $URI = preg_replace_callback('/\$(\d+)/i', function($ms) use ($theArgs) {
            $idx = $ms[1];
            return $theArgs[$idx] ?? "";
        }, "$URI$q");

        CORSService::getHostOfRequest($this->request, $myHost);

        if($forceHost || $host != $myHost) {
            if(stripos($host, 'http') !== 0) {
                $host = (($_SERVER["HTTPS"] ?? false) ? 'https://' : 'http://') . $host;
            }
            return "$host$URI";
        }

        return $URI;
    }

	/**
	 * This method can be used to transform any string into a secure version specified by the mode argument.
	 *
	 * @param string $html
	 * @param int $mode
	 */
    public function encodeString($string, int $mode = self::ENCODE_HTML_STRING_MODE) {
    	$code = "";
    	switch ($mode) {
			case self::ENCODE_HTML_STRING_MODE: return htmlspecialchars($string);
			case self::ENCODE_BASE64_STRING_MODE: return base64_encode($string);
			case self::ENCODE_ASCII_STRING_MODE: return utf8_decode($string);
			case self::ENCODE_UTF8_STRING_MODE: return utf8_encode($string);
			case self::ENCODE_URL_STRING_MODE: return urlencode($string);
			case self::ENCODE_BASE64_JS_STRING_MODE: return "<script type='application/javascript'>document.write(atob('" . base64_encode($string) . "'));</script>";

			default:
				$code = $string;
		}

		return $code;
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

                        if(is_callable( $tmp ))
                            $tmp = call_user_func($tmp);

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