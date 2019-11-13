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

namespace Skyline\Render\Specification;


use Serializable;
use Skyline\Render\Template\RenderableInterface;
use Skyline\Render\Template\TemplateInterface;

class Container implements Serializable
{
    const SPEC_MATCHING_ALL_TAGS = -19928;
    const SPEC_MATCHING_ONE_TAG = -19919;

    /** @var Catalog */
    private $catalog;
    /** @var ID */
    private $id;
    /** @var Name */
    private $name;
    /** @var Tag[] */
    private $tags = [];

    /** @var TemplateInterface[]|RenderableInterface[] */
    private $templates = [];

    /** @var bool  */
    private $matchingAllTags = true;

    /**
     * Container constructor
     * @param Tag|Name|ID|Catalog ...$_
     */
    public function __construct(...$_)
    {
        $this->append(...$_);
    }

    /**
     * @param Tag|Name|ID|Catalog ...$_
     */
    public function append(...$_) {
        foreach($_ as $item) {
            if($item instanceof Catalog)
                $this->catalog = $item;
            elseif($item instanceof Name)
                $this->name = $item;
            elseif($item instanceof ID)
                $this->id = $item;
            elseif($item instanceof Tag) {
                if(!in_array($item, $this->tags))
                    $this->tags[] = $item;
            }
            elseif(is_int($item) && $item == self::SPEC_MATCHING_ALL_TAGS)
                $this->matchingAllTags = true;
            elseif(is_int($item) && $item == self::SPEC_MATCHING_ONE_TAG)
                $this->matchingAllTags = false;
            elseif($item instanceof TemplateInterface || $item instanceof RenderableInterface)
                $this->templates[] = $item;
            elseif($item instanceof Container) {
                $this->catalog = $item->getCatalog() ?: $this->catalog;
                $this->id = $item->getId() ?: $this->id;
                $this->name = $item->getName() ?: $this->name;
                foreach($item->getTags() as $tag) {
                    if(!in_array($tag, $this->tags))
                        $this->tags[] = $tag;
                }
                foreach($item->getTemplates() as $template) {
                    if(!in_array($template, $this->templates))
                        $this->addTemplate($template);
                }
                $this->matchingAllTags = $item->isMatchingAllTags();
            }
            else
                trigger_error("Item $item is not supported", E_USER_WARNING);
        }
    }

    /**
     * @return Catalog|null
     */
    public function getCatalog(): Catalog
    {
        return $this->catalog;
    }

    /**
     * @return ID|null
     */
    public function getId(): ?ID
    {
        return $this->id;
    }

    /**
     * @return Name|null
     */
    public function getName(): ?Name
    {
        return $this->name;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return RenderableInterface[]|TemplateInterface[]
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @param RenderableInterface[]|TemplateInterface[] $templates
     */
    public function setTemplates($templates): void
    {
        $this->templates = $templates;
    }

    /**
     * @param TemplateInterface|RenderableInterface $template
     */
    public function addTemplate($template) {
        if(!in_array($template, $this->templates))
            $this->templates[] = $template;
    }

    /**
     * @return bool
     */
    public function isMatchingAllTags(): bool
    {
        return $this->matchingAllTags;
    }

    /**
     * String representation of object
     * @link https://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize([
            $this->id,
            $this->catalog,
            $this->name,
            $this->tags,
            $this->matchingAllTags
        ]);
    }

    /**
     * Constructs the object
     * @link https://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->catalog,
            $this->name,
            $this->tags,
            $this->matchingAllTags
            ) = unserialize($serialized);
    }
}