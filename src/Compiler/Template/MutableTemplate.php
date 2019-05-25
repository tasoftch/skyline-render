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

namespace Skyline\Render\Compiler\Template;


use Skyline\Render\Template\TemplateInterface;

class MutableTemplate implements TemplateInterface, \Serializable
{
    private $id;
    /** @var string */
    private $name;
    /** @var string|null */
    private $catalogName;
    /** @var array */
    private $tags = [];
    /** @var array  */
    private $attributes = [];

    /** @var string */
    private $className;

    /**
     * MutableTemplate constructor.
     * @param $id
     * @param string $name
     * @param string|null $catalogName
     * @param array $tags
     */
    public function __construct(string $templateClass, $id, string $name = "", ?string $catalogName = "", array $tags = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->catalogName = $catalogName;
        $this->tags = $tags;
        $this->className = $templateClass;
    }


    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string|null $catalogName
     */
    public function setCatalogName(?string $catalogName): void
    {
        $this->catalogName = $catalogName;
    }


    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getCatalogName(): ?string
    {
        return $this->catalogName;
    }

    /**
     * @inheritDoc
     */
    public function getTags(): array {
        return $this->tags;
    }

    /**
     * Adds a tag if not exist
     * @param string $tag
     */
    public function addTag(string $tag) {
        if(!in_array($tag, $this->tags))
            $this->tags[] = $tag;
    }

    /**
     * Removes a tag
     * @param string $tag
     */
    public function removeTag(string $tag) {
        if(($idx = array_search($tag, $this->tags)) !== false)
            unset($this->tags[$idx]);
    }

    /**
     * @inheritDoc
     */
    public function getAttribute(string $name)
    {
        return $this->attributes[$name] ?? NULL;
    }

    /**
     * Setting additional information
     *
     * @param string $name
     * @param null $value
     * @param bool $asArray
     */
    public function setAttribute(string $name, $value = NULL, bool $asArray = false) {
        if($value !== NULL) {
            if($asArray)
                $this->attributes[$name][] = $value;
            else
                $this->attributes[$name] = $value;
        }
        elseif(isset($this->attributes[$name]))
            unset($this->attributes[$name]);
    }

    public function getRenderable(): callable
    {
        return function(){};
    }


    public function serialize()
    {
        return serialize([
            $this->name,
            $this->catalogName,
            $this->tags,
            $this->id,
            $this->attributes
        ]);
    }

    public function getSerializedTemplate(): string {
        $content = serialize($this);
        $classLen = strlen($this->className);
        return preg_replace("/^C:\d+:\"[^\"]+\"/i", "C:$classLen:\"$this->className\"", $content);
    }

    public function unserialize($serialized)
    {
        // Can not unserialize!
    }
}