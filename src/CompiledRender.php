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

namespace Skyline\Render;


use Skyline\Render\Exception\RenderException;
use Skyline\Render\Plugin\RenderPluginInterface;
use TASoft\Service\ServiceManager;

class CompiledRender extends AbstractConfiguredRender
{
    const CONFIG_PLUGINS = 'plugins';
    const CONFIG_PLUGIN_CLASS = 'class';
    const CONFIG_PLUGIN_ARGUMENTS = 'arguments';

    /** @var ServiceManager */
    private $serviceManager;

    /**
     * @return ServiceManager
     */
    public function getServiceManager(): ServiceManager
    {
        return $this->serviceManager;
    }

    /**
     * @param ServiceManager $serviceManager
     */
    public function setServiceManager(ServiceManager $serviceManager): void
    {
        $this->serviceManager = $serviceManager;
    }

    protected function tearDown()
    {
    }


    protected function setup($configuration)
    {
        if($plugins = $configuration[ static::CONFIG_PLUGINS ] ?? NULL) {
            foreach($plugins as $plugin) {
                $class = $plugin[ static::CONFIG_PLUGIN_CLASS ] ?? NULL;
                $arguments = $plugin[ static::CONFIG_PLUGIN_ARGUMENTS ] ?? NULL;

                if(!$class) {
                    $e = new RenderException("No class specified for plugin");
                    $e->setRender($this);
                    throw $e;
                }

                if($arguments) {
                    $arguments = $this->getServiceManager()->mapArray($arguments, true);
                    $plugin = new $plugin( ...array_values($arguments) );
                } else {
                    $plugin = new $plugin();
                }

                if($plugin instanceof RenderPluginInterface) {
                    $plugin->initialize($this);
                } else {
                    $e = new RenderException("Plugin %s does not implement %s", 500, NULL, get_class($plugin), RenderPluginInterface::class);
                    $e->setRender($this);
                    throw $e;
                }
            }
        } else {
            trigger_error("No plugins specified for render ".get_class($this), E_USER_NOTICE);
        }
    }
}