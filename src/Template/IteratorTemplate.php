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


use Skyline\Render\Context\RenderContextInterface;

class IteratorTemplate implements TemplateInterface, ContextControlInterface
{
    /** @var TemplateInterface */
    private $template;
    /** @var iterable */
    private $iterator;

    /**
     * IteratorTemplate constructor.
     * @param TemplateInterface $template
     * @param iterable $iterator
     */
    public function __construct(TemplateInterface $template, iterable $iterator)
    {
        $this->template = $template;
        $this->iterator = $iterator;
    }


    public function getID()
    {
        return $this->getTemplate()->getID();
    }

    public function getName(): string
    {
        return $this->getTemplate()->getName();
    }

    public function getRenderable(): callable
    {
        $self = $this;
        return function($info) use ($self) {
            $contents = "";
            $cb = $self->getTemplate()->getRenderable();
            if($cb instanceof \Closure)
                $cb = $cb->bindTo($this, get_class($this));

            foreach($self->getIterator() as $value) {
                $contents .= call_user_func($cb, $value, $info);
            }

            return $contents;
        };
    }

    /**
     * @return TemplateInterface
     */
    public function getTemplate(): TemplateInterface
    {
        return $this->template;
    }

    /**
     * @return iterable
     */
    public function getIterator(): iterable
    {
        return $this->iterator;
    }

    public function shouldBindToContext(RenderContextInterface $ctx): bool
    {
        $tpl = $this->getTemplate();
        if($tpl instanceof ContextControlInterface)
            return $tpl->shouldBindToContext($ctx);
        return true;
    }
}