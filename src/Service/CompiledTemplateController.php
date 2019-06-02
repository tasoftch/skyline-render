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

namespace Skyline\Render\Service;


use Generator;
use Skyline\Render\Template\TemplateInterface;

class CompiledTemplateController extends AbstractOrganizedTemplateController
{
    private $templateMeta = [];

    /**
     * CompiledTemplateController constructor.
     * @param string|array $templateFile
     */
    public function __construct($templateFile)
    {
        $this->templateMeta = is_array($templateFile) ? $templateFile : require $templateFile;
    }

    /**
     * @inheritDoc
     */
    protected function yieldIDsWithName(string $name): Generator
    {
        if($names = $this->templateMeta["names"][$name] ?? NULL) {
            foreach($names as $idx)
                yield $this->_getTemplateID($idx);
        }
    }

    /**
     * @inheritDoc
     */
    protected function yieldIDsInCatalog(string $name): Generator
    {
        if($names = $this->templateMeta["catalog"][$name] ?? NULL) {
            foreach($names as $idx)
                yield $this->_getTemplateID($idx);
        }
    }

    /**
     * @inheritDoc
     */
    protected function yieldIDsWithTags(array $tags, bool $all = false): Generator
    {
        $selection = $all ? array_keys($this->templateMeta["files"]) : [];

        foreach($tags as $tag) {
            $tmpl = $this->templateMeta["tags"][$tag] ?? [];
            if($all) {
                $selection = array_intersect($selection, $tmpl);
                if(!$selection)
                    return;
            } else {
                foreach($tmpl as $t) {
                    if(!in_array($t, $selection)) {
                        yield $this->_getTemplateID($t);
                        $selection[] = $t;
                    }
                }
            }
        }
        if($all) {
            foreach($selection as $idx)
                yield $this->_getTemplateID($idx);
        }
    }

    private function _getTemplateID(int $index): string {
        return $this->templateMeta["files"][$index] ?? "";
    }

    /**
     * @inheritDoc
     */
    protected function loadTemplate($id): ?TemplateInterface
    {
        if(!is_numeric($id)) {
            $idx = array_search($id, $this->templateMeta["files"]);
            if($idx === false)
                return NULL;
            $id = $idx;
        }

        if(isset($this->templateMeta["data"][$id])) {
            $tmp = $this->templateMeta["data"][$id];
            if(!($tmp instanceof TemplateInterface)) {
                $this->templateMeta["data"][$id] = $tmp = unserialize($tmp);
            }
            return $tmp;
        }
        return NULL;
    }
}