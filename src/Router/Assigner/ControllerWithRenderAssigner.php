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

namespace Skyline\Render\Router\Assigner;


use Skyline\Render\Router\Description\MutableRenderDescription;
use Skyline\Router\Description\MutableActionDescriptionInterface;
use Skyline\Router\PartialAssigner\PartialAssignerInterface;

class ControllerWithRenderAssigner implements PartialAssignerInterface
{
    /**
     * @inheritDoc
     */
    public function routePartial($information, MutableActionDescriptionInterface $actionDescription): bool
    {
        if(is_string($information) && $actionDescription instanceof MutableRenderDescription) {
            $parts = explode("::", $information, 3);
            if(count($parts) == 3) {
                list($renderClass, $className, $method) = $parts;
                $actionDescription->setRenderName(trim($renderClass));
                $actionDescription->setActionControllerClass( trim($className) );
                $actionDescription->setMethodName( trim($method) );

                return $renderClass && $className && $method ? true : false;
            }
            elseif(count($parts) == 2) {
                list($className, $method) = $parts;
                $actionDescription->setActionControllerClass( trim($className) );
                $actionDescription->setMethodName( trim($method) );

                return $actionDescription->getRenderName() && $className && $method ? true : false;
            } elseif (count($parts) == 1) {
                $actionDescription->setActionControllerClass( trim($parts[0]) );

                return $actionDescription->getRenderName() && $parts[0] && $actionDescription->getMethodName() ? true : false;
            }
        } elseif(is_array($information)) {
            if(isset($information["render"]) && $actionDescription instanceof MutableRenderDescription)
                $actionDescription->setRenderName( $information["render"] );
            if(isset($information["controller"]))
                $actionDescription->setActionControllerClass( $information["controller"] );
            if(isset($information["method"]))
                $actionDescription->setMethodName( $information["method"] );


            return (!($actionDescription instanceof MutableRenderDescription) || $actionDescription->getRenderName()) && $actionDescription->getActionControllerClass() && $actionDescription->getMethodName() ? true : false;

        }
        return false;
    }
}