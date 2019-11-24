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
use Skyline\Render\Specification\Catalog;
use Skyline\Render\Template\TemplateInterface;

abstract class AbstractOrganizedTemplateController extends AbstractTemplateController implements OrganizedTemplateControllerInterface
{
    /**
     * Finds all templates with given name
     *
     * @param string $name
     * @return array
     */
    public function findTemplatesWithName(string $name): array {
        $temps = [];
        foreach($this->yieldIDsWithName($name) as $t)
            $temps[$t] = $this->getTemplate($t);
        return $temps;
    }

    /**
     * Finds all templates in given catalog
     *
     * @param string $catalogName
     * @return array
     */
    public function findTemplatesInCatalog(string $catalogName): array {
        $temps = [];
        foreach($this->yieldIDsInCatalog($catalogName) as $t)
            $temps[$t] = $this->getTemplate($t);
        return $temps;
    }

    /**
     * Finds all templates with given tags
     *
     * @param array $tags
     * @param bool $all
     * @return array
     */
    public function findTemplatesWithTags(array $tags, bool $all = false): array {
        $temps = [];
        foreach($this->yieldIDsWithTags($tags, $all) as $t)
            $temps[$t] = $this->getTemplate($t);
        return $temps;
    }

    /**
     * Returns first template found with name
     *
     * @param string $name
     * @return TemplateInterface|null
     */
    public function findTemplateWithName(string $name): ?TemplateInterface {
        foreach($this->yieldIDsWithName($name) as $t) {
            if($t = $this->getTemplate($t))
                return $t;
        }
        return NULL;
    }

    /**
     * Returns first template found in category
     *
     * @param string $catalogName
     * @return TemplateInterface|null
     */
    public function findTemplateInCatalog(string $catalogName): ?TemplateInterface {
        foreach($this->yieldIDsInCatalog( $catalogName) as $t) {
            if($t = $this->getTemplate($t))
                return $t;
        }
        return NULL;
    }

    /**
     * Returns first template found with tags
     *
     * @param array $tags
     * @return TemplateInterface|null
     */
    public function findTemplateWithTags(array $tags): ?TemplateInterface {
        foreach($this->yieldIDsWithTags($tags) as $t) {
            if($t = $this->getTemplate($t))
                return $t;
        }
        return NULL;
    }

    /**
     * @param array|Catalog|string $info
     * @return TemplateInterface|null
     */
    public function findTemplate($info): ?TemplateInterface
    {
        if($info instanceof TemplateInterface)
            $template = $info;
        elseif(is_array($info)) {
            $template = $this->findTemplateWithTags($info);
        } elseif ($info instanceof Catalog) {
            $template = $this->findTemplateInCatalog((string) $info);
        } else {
            $template = $this->findTemplateWithName($info);
            if(!$template)
                $template = $this->getTemplate($info);
        }
        return $template;
    }

    /**
     * @param array|Catalog|string $info
     * @return array
     */
    public function findTemplates($info): array
    {
        if(is_array($info)) {
            $template = $this->findTemplatesWithTags($info);
        } elseif ($info instanceof Catalog) {
            $template = $this->findTemplatesInCatalog((string) $info);
        } else {
            $template = $this->findTemplatesWithName($info);
            if(!$template)
                $template = ($tmp = $this->getTemplate($info)) ? [$tmp] : [];
        }
        return $template;
    }

    // Template ID providers
    abstract protected function yieldIDsWithName(string $name): Generator;
    abstract protected function yieldIDsInCatalog(string $name): Generator;
    abstract protected function yieldIDsWithTags(array $tags, bool $all = true): Generator;
}