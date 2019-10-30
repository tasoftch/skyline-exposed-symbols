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

namespace Skyline\Expose\Compiler;


use Closure;
use ReflectionClass;
use ReflectionMethod;
use Skyline\Compiler\AbstractCompiler;
use Skyline\Compiler\CompilerContext;

/**
 * A compiler helper sub class to work with exposed symbols.
 * Please note to make any AbstractExposedSymbolsCompiler dependent of the FindExposedSymbolsCompiler!
 *
 * @package Skyline\Expose
 */
abstract class AbstractExposedSymbolsCompiler extends AbstractCompiler
{
    const OPT_PUBLIC = ReflectionMethod::IS_PUBLIC;
    const OPT_PROTECTED = ReflectionMethod::IS_PROTECTED;
    const OPT_PRIVATE = ReflectionMethod::IS_PRIVATE;
    const OPT_STATIC = ReflectionMethod::IS_STATIC;
    const OPT_ABSTRACT = ReflectionMethod::IS_ABSTRACT;
    const OPT_FINAL = ReflectionMethod::IS_FINAL;
    const OPT_OBJECTIVE = 2048;

    const OPT_PUBLIC_STATIC = self::OPT_STATIC | self::OPT_PUBLIC;
    const OPT_PUBLIC_OBJECTIVE = self::OPT_PUBLIC | self::OPT_OBJECTIVE;

    private $excludeMagicMethods;

    public function __construct(string $compilerID, bool $excludeMagicMethods = true)
    {
        parent::__construct($compilerID);
        $this->excludeMagicMethods = $excludeMagicMethods;
    }


    /**
     * Returns all analyzed class names (Found in passed search paths)
     *
     * @return array|null
     */
    protected function getRegisteredClassNames(): ?array {
        $ctx = CompilerContext::getCurrentCompiler();
        if($ctx) {
            $reflections = $ctx->getValueCache()->fetchValue( FindExposedSymbolsCompiler::CACHE_CLASS_REFLECTIONS );
            return array_keys($reflections);
        }
        return NULL;
    }

    /**
     * Fetches the class reflection if available
     *
     * @param $className
     * @return ReflectionClass|null
     */
    protected function getClassReflection($className): ?ReflectionClass {
        $ctx = CompilerContext::getCurrentCompiler();
        if($ctx) {
            $reflections = $ctx->getValueCache()->fetchValue( FindExposedSymbolsCompiler::CACHE_CLASS_REFLECTIONS );
            return $reflections[$className] ?? NULL;
        }
        return NULL;
    }

    /**
     * Tries to find all methods matching to a filter
     *
     * @param $className
     * @param int $methodFilterOptions
     * @return ReflectionMethod[]|null
     */
    protected function findClassMethods($className, int $methodFilterOptions = ReflectionMethod::IS_PUBLIC): ?array {
        if($reflection = $this->getClassReflection($className)) {
            $methods = [];

            foreach($reflection->getMethods( $methodFilterOptions ) as $mthd) {
                if($methodFilterOptions & self::OPT_STATIC && $mthd->isStatic() == false)
                    continue;
                if($methodFilterOptions & self::OPT_OBJECTIVE && $mthd->isStatic())
                    continue;

                if($this->excludeMagicMethods && strpos($mthd->getName(), "__") === 0)
                    continue;

                if($mthd->getDeclaringClass()->getName() != $className)
                    continue;

                $methods[ $className . "::" . $mthd->getName() ] = $mthd;
            }
            return $methods;
        }
        return NULL;
    }



    /**
     * Yields all classes matching the purpose pattern. This may use the following patterns:
     *
     *      Separated by dot().                     => PURPOSE1.SUB_PURPOSE_OF_1.OTHER_PURPOSE
     *      Use glob pattern for purpose name       => PURPOSE1.* = Selects all child purposes of PURPOSE1
     *      Tailing dot recursive select            => PURPOSE1. = Selects all child purpose of PURPOSE1 and its children and their children and so on
     *          => PURPOSE1.*.TEST      = Selects every TEST purpose of any child of PURPOSE1
     *
     * @param string $purpose
     * @param bool $includeParents      Yields also parents
     * @return \Generator
     */
    protected function yieldClasses(string $purpose, bool $includeParents = false) {
        $deepSearch = $this->getDeepSearchClosure($includeParents);

        $ctx = CompilerContext::getCurrentCompiler();
        if($ctx) {
            $symbols = $ctx->getValueCache()->fetchValue( FindExposedSymbolsCompiler::CACHE_EXPOSED_SYMBOLS );

            $ppParts = explode(".", strtoupper($purpose));
            yield from $deepSearch($ppParts, $symbols["purposes"]);
        }
    }

    /**
     * Yields all methods matching a purpose. See yieldClasses for more information about searching for purposes
     *
     * @param string $purpose
     * @param bool $includeParents
     * @return \Generator
     */
    protected function yieldMethods(string $purpose, bool $includeParents = false) {
        $deepSearch = $this->getDeepSearchClosure($includeParents);

        $ctx = CompilerContext::getCurrentCompiler();
        if($ctx) {
            $symbols = $ctx->getValueCache()->fetchValue( FindExposedSymbolsCompiler::CACHE_EXPOSED_SYMBOLS );

            $ppParts = explode(".", strtoupper($purpose));
            yield from $deepSearch($ppParts, $symbols["method_purposes"]);
        }
    }

    /**
     * Returns all exposed classes
     *
     * @return array|null
     */
    protected function getExposedClasses(): ?array {
        $ctx = CompilerContext::getCurrentCompiler();
        if($ctx) {
            $symbols = $ctx->getValueCache()->fetchValue( FindExposedSymbolsCompiler::CACHE_EXPOSED_SYMBOLS );
            return array_keys($symbols["classes"]);
        }
        return NULL;
    }

    /**
     * Returns all exposed methods
     *
     * @return array|null
     */
    protected function getExposedMethods(): ?array {
        $ctx = CompilerContext::getCurrentCompiler();
        if($ctx) {
            $symbols = $ctx->getValueCache()->fetchValue( FindExposedSymbolsCompiler::CACHE_EXPOSED_SYMBOLS );
            return array_keys($symbols["methods"]);
        }
        return NULL;
    }

    /**
     * Tries to qualify a symbol using php's import mechanism
     *
     * @param $symbolName
     * @param $classContext
     * @return string|null
     */
    protected function qualifySymbol($symbolName, $classContext) {
        $ctx = CompilerContext::getCurrentCompiler();
        if($ctx) {
            $symbols = $ctx->getValueCache()->fetchValue( FindExposedSymbolsCompiler::CACHE_EXPOSED_SYMBOLS );
            if($imports = $symbols["classes"][$classContext]["imports"] ?? NULL) {
                return $imports[$symbolName] ?? $symbolName;
            }
        }
        return NULL;
    }

    /**
     * Returns the module name if a class is part of a module
     *
     * @param $classContext
     * @return string|null
     */
    protected function getDeclaredModule($classContext) {
        $ctx = CompilerContext::getCurrentCompiler();
        if($ctx) {
            $symbols = $ctx->getValueCache()->fetchValue( FindExposedSymbolsCompiler::CACHE_EXPOSED_SYMBOLS );
            return $symbols["classes"][$classContext]["module"] ?? NULL;
        }
        return NULL;
    }


    /**
     * @internal
     */
    private function getDeepSearchClosure($includeParents = false): Closure {
        $deepSearch = function($parts, $array, $ppReal = "", $pass = false) use (&$deepSearch, $includeParents) {
            $part = array_shift($parts);
            if($part === NULL && !$pass)
                return;

            foreach($array as $name => $children) {
                if($name == '#')
                    continue;

                if($pass || $part == "" || fnmatch($part, $name)) {
                    $ppR = $ppReal ? "$ppReal.$name" : $name;

                    if($includeParents || count($parts) < 1) {
                        foreach(($children['#']??[]) as $class) {
                            yield $ppR => $class;
                        }
                    }
                    yield from $deepSearch($parts, $children, $ppR, $part == "");
                }
            }
        };
        return $deepSearch;
    }
}