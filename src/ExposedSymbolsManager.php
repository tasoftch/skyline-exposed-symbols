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

namespace Skyline\Expose;


use Closure;
use Skyline\Expose\Symbol\ClassSymbol;
use Skyline\Expose\Symbol\MethodSymbol;
use Skyline\Expose\Symbol\SymbolInterface;
use TASoft\Service\Exception\FileNotFoundException;

class ExposedSymbolsManager
{
    private $classPurposes;
    private $methodPurposes;

    private $classDeclarations;
    private $methodDeclarations;

    public function __construct($filename)
    {
        if(is_file($filename) || is_array($filename)) {
            $cfg = is_array($filename) ? $filename : require $filename;
            $this->classPurposes = $cfg["purposes"] ?? [];
            $this->methodPurposes = $cfg["method_purposes"] ?? [];
            $this->classDeclarations = $cfg["classes"] ?? [];
        } else {
            throw new FileNotFoundException("Could not find configuration file for ExposedSymbolsManager", 404);
        }
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
    public function yieldClasses(string $purpose, bool $includeParents = false) {
        $deepSearch = $this->getDeepSearchClosure($includeParents);

        $ppParts = explode(".", strtoupper($purpose));
        yield from $deepSearch($ppParts, $this->classPurposes);
    }

    public function yieldMethods(string $purpose, bool $includeParents = false) {
        $deepSearch = $this->getDeepSearchClosure($includeParents);

        $ppParts = explode(".", strtoupper($purpose));
        yield from $deepSearch($ppParts, $this->methodPurposes);
    }

    /**
     * Returns the name of an exposed class
     * Names are provided using the @display annotation tag in class doc comment
     *
     * @param string $className
     * @return string|null
     */
    public function getDisplayNameOfClass(string $className): ?string {
        if($cl = $this->classDeclarations[$className] ?? NULL) {
            if($cl instanceof ClassSymbol)
                return $cl->getSymbolName();
            return $cl["display"] ?? NULL;
        }
        return NULL;
    }

    /**
     * Returns the name of an exposed method
     * Names are provided using the @display annotation tag in method doc comment
     *
     * @param string $methodName
     * @return string|null
     */
    public function getDisplayNameOfMethod(string $methodName): ?string {
        if($cl = $this->methodDeclarations[$methodName] ?? NULL) {
            if($cl instanceof MethodSymbol)
                return $cl->getSymbolName();
            return $cl["display"] ?? NULL;
        }
        return NULL;
    }

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

    /**
     * Loads the exposed class by a name if exists.
     *
     * @param $className
     * @return ClassSymbol|null
     */
    public function getExposedClass($className): ?ClassSymbol {
        $class = $this->classDeclarations[ $className ] ?? NULL;
        if(is_null($class))
            return NULL;
        if(!($class instanceof SymbolInterface)) {

            $vi = new class($cl = new ClassSymbol($className, $class["display"] ?? NULL, $class["module"] ?? NULL)) {
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

            $vi->inheritance = $class["inheritance"] ?? [];
            $vi->instantiable = @$class["isAbstract"] ? false : true;

            $this->classDeclarations[$className] = $cl;

            if($methods = @$class["methods"]) {
                $mthds = [];
                foreach($methods as $methodName) {
                    $mthds[] = $this->getExposedMethod("$className::$methodName");
                }
                $vi->exposedMethods = $mthds;
            }
            $class = $cl;
        }
        return $class;
    }

    /**
     * Loads the exposed method by a name if exists.
     *
     * @param $methodName
     * @return MethodSymbol|null
     */
    public function getExposedMethod($methodName): ?MethodSymbol {
        $methodInfo = $this->methodDeclarations[$methodName] ?? NULL;
        if($methodInfo === false)
            return NULL;
        if(!($methodInfo instanceof MethodSymbol)) {
            $className = explode("::", $methodName, 2)[0];

            $vim = new class($mt = new MethodSymbol("$methodName", $methodInfo["display"] ?? NULL)) {
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

            $vim->parentClass = $this->getExposedClass($className);
            $vim->isPublic = @$methodInfo["isPublic"] ? true : false;
            $vim->isProtected = @$methodInfo["isProtected"] ? true : false;
            $vim->isPrivate = @$methodInfo["isPrivate"] ? true : false;

            $vim->isAbstract = @$methodInfo["isAbstract"] ? true : false;
            $vim->isFinal = @$methodInfo["isFinal"] ? true : false;
            $vim->isStatic = @$methodInfo["isStatic"] ? true : false;

            $vim->isInternal = @$methodInfo["isInternal"] ? true : false;
            $vim->isConstructor = @$methodInfo["isConstructor"] ? true : false;
            $vim->isDestructor = @$methodInfo["isDestructor"] ? true : false;

            $this->methodDeclarations[ $mt->getQualifiedName() ] = $mt;
            $methodInfo = $mt;
        }
        return $methodInfo;
    }
}