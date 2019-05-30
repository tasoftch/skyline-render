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

use Skyline\Render\CompiledRender;
use Skyline\Render\Exception\RenderException;
use Skyline\Render\RenderInterface;

class CompiledRenderController implements RenderControllerInterface
{
    private $compiledRenderFilename;
    private $compiledRenderInfo;

    /**
     * CompiledRenderController constructor.
     * @param $compiledRenderFilename
     */
    public function __construct($compiledRenderFilename)
    {
        $this->compiledRenderFilename = $compiledRenderFilename;
    }

    /**
     * @return mixed
     */
    public function getCompiledRenderFilename()
    {
        return $this->compiledRenderFilename;
    }

    public function getRender(string $name): RenderInterface {
        if(NULL === $this->compiledRenderInfo) {
            $this->compiledRenderInfo = require getcwd() . DIRECTORY_SEPARATOR . $this->getCompiledRenderFilename();
        }

        if($renderInfo = $this->compiledRenderInfo[ $name ] ?? NULL) {
            if($renderInfo instanceof RenderInterface)
                return $renderInfo;

            $rc = $renderInfo[ CompiledRender::CONFIG_RENDER_CLASS ] ?? NULL;
            if(!$rc)
                throw new RenderException("Configuration for render $name does not specify a render class name");

            return $this->compiledRenderInfo[ $name ] = new $rc($renderInfo);
        } else {
            throw new RenderException("Could not find desired render $name");
        }
    }
}