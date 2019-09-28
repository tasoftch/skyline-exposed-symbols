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
 * ValueInjectionTest.php
 * skyline-exposed-symbols
 *
 * Created on 2019-09-28 16:32 by thomas
 */

use PHPUnit\Framework\TestCase;

class ValueInjectionTest extends TestCase
{
    public function testValueInjection() {
        $test = new MyTest();

        $vi = new class($test) {
            private $obj;
            private $cb;
            public function __construct($obj)
            {
                $this->obj = $obj;
                $this->cb = (function($name, $value) {
                    $this->$name = $value;
                })->bindTo($obj, get_class($obj));
            }

            public function __set($name, $value)
            {
                ($this->cb)($name, $value);
            }
        };

        $vi->hehe = 23;
        $vi->variable = 67;

        $this->assertAttributeEquals(23, 'hehe', $test);
        $this->assertAttributeEquals(67, 'variable', $test);

        $this->assertEquals(67, $test->getVariable());
    }
}


class MyTest {
    private $variable;

    /**
     * @return mixed
     */
    public function getVariable()
    {
        return $this->variable;
    }
}