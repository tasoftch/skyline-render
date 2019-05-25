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

namespace Skyline\Render\Template;


class Metadata implements \Serializable
{
    private $name;
    private $catalogName;
    /** @var array */
    private $tags;

    private $templateID;
    /** @var string */
    private $moduleName;

    /** @var array  */
    private $attributes = [];

    public function __construct($templateID, string $templateName = NULL, string $catalogName = NULL, array $tags = [])
    {
        $this->templateID = $templateID;
        $this->name = $templateName;
        $this->catalogName = $catalogName;
        $this->tags = $tags;
    }


    public function getName(): string
    {
        return $this->name;
    }

    public function getCatalogName(): ?string
    {
        return $this->catalogName;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $catalogName
     */
    public function setCatalogName(string $catalogName = NULL): void
    {
        $this->catalogName = $catalogName;
    }

    /**
     * Tags are used to find templates easier.
     * @return array
     */
    public function getTags(): array {
        return $this->tags;
    }

    public function addTag(string $tag) {
        if(!in_array($tag, $this->tags))
            $this->tags[] = $tag;
    }

    public function removeTag(string $tag) {
        if(($idx = array_search($tag, $this->tags)) !== false)
            unset($this->tags[$idx]);
    }

    /**
     * @return mixed
     */
    public function getTemplateID()
    {
        return $this->templateID;
    }

    /**
     * Setting additional information
     *
     * @param string $name
     * @param null $value
     * @param bool $asArray
     * @return Metadata
     */
    public function setAttribute(string $name, $value = NULL, bool $asArray = false): Metadata {
        if($value) {
            if($asArray)
                $this->attributes[$name][] = $value;
            else
                $this->attributes[$name] = $value;
        }
        elseif(isset($this->attributes[$name]))
            unset($this->attributes[$name]);

        return $this;
    }

    public function getAttribute(string $name) {
        return $this->attributes[$name] ?? NULL;
    }


    public function serialize()
    {
        return serialize([
            $this->name,
            $this->catalogName,
            $this->tags,
            $this->templateID,
            $this->attributes
        ]);
    }

    public function unserialize($serialized)
    {
        list($this->name, $this->catalogName, $this->tags, $this->templateID, $this->attributes) = unserialize($serialized);
    }

    /**
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->moduleName ?? "";
    }

    /**
     * @param string $moduleName
     */
    public function setModuleName(string $moduleName): void
    {
        $this->moduleName = $moduleName;
    }
}