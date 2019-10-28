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

namespace Skyline\Render\Compiler;


use Skyline\Compiler\AbstractCompiler;
use Skyline\Compiler\CompilerConfiguration;
use Skyline\Compiler\CompilerContext;
use Skyline\Compiler\Context\Code\SourceFile;
use Skyline\Compiler\Project\Attribute\SearchPathAttribute;
use Skyline\Render\Compiler\Template\MutableTemplate;
use Skyline\Render\Template\Loader\LoaderInterface;
use Skyline\Render\Template\Loader\TemplateFileLoader;

class FindTemplatesCompiler extends AbstractCompiler
{
    public $ignoreModuleTemplates = true;
    /**
     * @inheritDoc
     */
    public function compile(CompilerContext $context)
    {
        $spt = $context->getProjectSearchPaths(SearchPathAttribute::SEARCH_PATH_TEMPLATES);

        $fn = $context->getSkylineAppDirectory(CompilerConfiguration::SKYLINE_DIR_COMPILED) . DIRECTORY_SEPARATOR . "templates.config.php";
        if(file_exists($fn)) {
            $templates = require $fn;
        } else
            $templates = [];

        /** @var SourceFile $sourceFile */
        $scm = $context->getSourceCodeManager();
        $scm->setRespectPackageOrder(true);

        $shiftIndex = function(&$array, $index) {
            if(!is_array($array))
                $array[] = $index;
            else
                array_unshift($array, $index);
        };

        foreach($scm->yieldSourceFiles($this->getTemplateFilenamePattern(), $spt) as $sourceFile) {
            if($this->ignoreModuleTemplates && $scm->isFilePartOfModule($sourceFile))
                continue;

            $loader = $this->getLoaderForFile($sourceFile);
            $template = $loader->loadTemplate();
            $template = $this->adjustLoadedTemplate($template, $sourceFile);

            $templates["files"][] = $ref = $context->useZeroLinks() ? $sourceFile->getRealPath() : $sourceFile->getPathName();
            $idx = array_search($ref, $templates["files"]);

            $name = $template->getName();
            if(!$name)
                trigger_error("Template $sourceFile does not provide a name", E_USER_WARNING);
            else
                $shiftIndex($templates["names"][$name], $idx);

            if($catalog = $template->getCatalogName()) {
                $shiftIndex($templates["catalog"][$catalog][$name], $idx);
            }

            if($tags = $template->getTags()) {
                foreach($tags as $tag)
                    $shiftIndex($templates["tags"][$tag], $idx);
            }

            $templates["data"][$idx] = $template->getSerializedTemplate();
        }
        $scm->setRespectPackageOrder(false);

        $data = var_export($templates, true);
        file_put_contents($fn, "<?php\nreturn $data;");
    }

    /**
     * Creates a loader to load specified template from source file
     *
     * @param SourceFile $sourceFile
     * @return LoaderInterface
     */
    protected function getLoaderForFile(SourceFile $sourceFile): LoaderInterface {
        return new TemplateFileLoader($sourceFile->getPathName());
    }

    /**
     * After loading, adjust the template if needed
     *
     * @param MutableTemplate $template
     * @param SourceFile $sourceFile
     * @return MutableTemplate
     */
    protected function adjustLoadedTemplate(MutableTemplate $template, SourceFile $sourceFile): MutableTemplate {
        return $template;
    }

    /**
     * Returns a regex template match string
     *
     * @return string
     */
    protected function getTemplateFilenamePattern(): string {
        return "/\.temp\.php$/i";
    }
}