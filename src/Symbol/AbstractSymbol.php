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

namespace Skyline\Expose\Symbol;


abstract class AbstractSymbol implements SymbolInterface, SymbolAwareInterface
{
    /** @var string */
    private $qualifiedName;
    /** @var string|null */
    private $symbolName;
    /** @var string|null */
    private $moduleName;

    /**
     * AbstractSymbol constructor.
     * @param string $qualifiedName
     * @param null|string $symbolName
     * @param null|string $moduleName
     */
    public function __construct(string $qualifiedName, string $symbolName = NULL, string $moduleName = NULL)
    {
        $this->qualifiedName = $qualifiedName;
        $this->symbolName = $symbolName;
        $this->moduleName = $moduleName;
    }


    public function getQualifiedName(): string
    {
        return $this->qualifiedName;
    }

    public function getSymbolName(): ?string
    {
        if(!$this->symbolName) {
            $qn = str_replace("\\", "/", $this->getQualifiedName());
            return basename($qn);
        }
        return $this->symbolName;
    }

    public function getSymbolModuleName(): ?string
    {
        return $this->moduleName;
    }

    public function getNamespace(): ?string {
        $qn = str_replace("\\", "/", $this->getQualifiedName());
        return ($d = dirname($qn)) ? str_replace("/", "\\", $d) : NULL;
    }
}