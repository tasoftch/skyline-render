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

/**
 * CompiledTemplatesTest.php
 * skyline-render
 *
 * Created on 2019-06-02 20:49 by thomas
 */

use PHPUnit\Framework\TestCase;
use Skyline\Render\Service\CompiledTemplateController;
use Skyline\Render\Template\FileTemplate;

class CompiledTemplatesTest extends TestCase
{
    public function testDirectTemplateInitialisation() {
        $man = new CompiledTemplateController([
            'files' => [
                0 => 'test.tmp',
            ],
            "data" => [
                0 => 'C:36:"Skyline\Render\Template\FileTemplate":67:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:8:"test.tmp";i:4;a:0:{}}}',
            ]
        ]);

        $tmp = $man->getTemplate("test.tmp");
        $this->assertInstanceOf(FileTemplate::class, $tmp);
    }

    public function testNamedTemplate() {
        $man = new CompiledTemplateController([
            'files' => [
                0 => 'test.tmp',
                1 => 'other.tmp',
                2 => '3rd.php',
                3 => '4th.php'
            ],
            "names" => [
                "Test" => [0, 2],
                "Hehe" => [0, 1, 3]
            ],
            "data" => [
                0 => 'C:36:"Skyline\Render\Template\FileTemplate":67:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:8:"test.tmp";i:4;a:0:{}}}',
                1 => 'C:36:"Skyline\Render\Template\FileTemplate":68:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:9:"other.tmp";i:4;a:0:{}}}',
                2 => 'C:36:"Skyline\Render\Template\FileTemplate":66:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:7:"3rd.php";i:4;a:0:{}}}',
                3 => 'C:36:"Skyline\Render\Template\FileTemplate":66:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:7:"4th.php";i:4;a:0:{}}}'
            ]
        ]);

        $this->assertEquals([
            "test.tmp",
            "3rd.php"
        ], array_keys($man->findTemplatesWithName("Test")));

        $this->assertEquals([
            "test.tmp",
            "other.tmp",
            "4th.php"
        ], array_keys($man->findTemplatesWithName("Hehe")));

        $this->assertEquals([], array_keys($man->findTemplatesWithName("Nonexisting")));
    }

    public function testTemplatesInCatalog() {
        $man = new CompiledTemplateController([
            'files' => [
                0 => 'test.tmp',
                1 => 'other.tmp',
                2 => '3rd.php',
                3 => '4th.php'
            ],
            "catalog" => [
                "Shop" => [1, 2, 3],
                "Web" => [0, 1, 2]
            ],
            "data" => [
                0 => 'C:36:"Skyline\Render\Template\FileTemplate":67:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:8:"test.tmp";i:4;a:0:{}}}',
                1 => 'C:36:"Skyline\Render\Template\FileTemplate":68:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:9:"other.tmp";i:4;a:0:{}}}',
                2 => 'C:36:"Skyline\Render\Template\FileTemplate":66:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:7:"3rd.php";i:4;a:0:{}}}',
                3 => 'C:36:"Skyline\Render\Template\FileTemplate":66:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:7:"4th.php";i:4;a:0:{}}}'
            ]
        ]);

        $this->assertEquals([
            "other.tmp",
            "3rd.php",
            "4th.php"
        ], array_keys($man->findTemplatesInCatalog("Shop")));
    }

    public function testTemplatesWithTags() {
        $man = new CompiledTemplateController([
            'files' => [
                0 => 'test.tmp',
                1 => 'other.tmp',
                2 => '3rd.php',
                3 => '4th.php'
            ],
            "tags" => [
                "tag1" => [0, 1],
                "tag2" => [0, 1, 2],
                "tag3" => [2, 3, 1],
                "tag4" => [2],
                "tag5" => [1],
                "tag6" => [3, 0],
                "tag7" => [0, 2]
            ],
            "data" => [
                0 => 'C:36:"Skyline\Render\Template\FileTemplate":67:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:8:"test.tmp";i:4;a:0:{}}}',
                1 => 'C:36:"Skyline\Render\Template\FileTemplate":68:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:9:"other.tmp";i:4;a:0:{}}}',
                2 => 'C:36:"Skyline\Render\Template\FileTemplate":66:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:7:"3rd.php";i:4;a:0:{}}}',
                3 => 'C:36:"Skyline\Render\Template\FileTemplate":66:{a:5:{i:0;s:0:"";i:1;s:0:"";i:2;a:0:{}i:3;s:7:"4th.php";i:4;a:0:{}}}'
            ]
        ]);

        $this->assertEquals([
            "test.tmp",
            "other.tmp",
            "3rd.php"
        ], array_keys($man->findTemplatesWithTags(["tag1", "tag4"])));

        $this->assertEquals([
            "test.tmp",
            "3rd.php",
            "other.tmp"
        ], array_keys($man->findTemplatesWithTags(["tag7", "tag2"])));

        $this->assertEquals([
            "other.tmp",
            "3rd.php"
        ], array_keys($man->findTemplatesWithTags(["tag2", "tag3"], true)));
    }
}
