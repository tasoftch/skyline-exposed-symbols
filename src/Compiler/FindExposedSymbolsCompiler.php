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


use ReflectionClass;
use ReflectionException;
use Skyline\Compiler\AbstractCompiler;
use Skyline\Compiler\CompilerConfiguration;
use Skyline\Compiler\CompilerContext;
use Skyline\Compiler\Project\Attribute\SearchPathAttribute;
use Skyline\Kernel\ExposeClassInterface;
use Skyline\Kernel\ExposeClassMethodsInterface;
use Throwable;

class FindExposedSymbolsCompiler extends AbstractCompiler
{
    private $registered = ["purposes" => [], "method_purposes" => [], "classes" => [], 'methods' => []];
    private $searchPaths;

    const CACHE_EXPOSED_SYMBOLS = 'exposedSymbols';
    const CACHE_CLASS_REFLECTIONS = 'classReflections';


    public function __construct(string $compilerID, array $searchPaths = NULL)
    {
        parent::__construct($compilerID);
        if(!$searchPaths)
            $searchPaths = [ SearchPathAttribute::SEARCH_PATH_CLASSES ];
        $this->searchPaths = $searchPaths;
    }

    public function compile(CompilerContext $context)
    {
        $reflections = [];

        spl_autoload_register($alf = function($className) {
            throw new \RuntimeException("Class $className not found");
        });

        foreach($context->getSourceCodeManager()->yieldSourceFiles("/^[a-z_][a-z_0-9]*?\.php$/i", $this->searchPaths) as $file) {
            if(preg_match("/^([a-z_][a-z_0-9]*?)\.php$/i", basename($file), $ms)) {
                $className = $ms[1];
                $contents = file_get_contents($file);

                if(preg_match("/class\s+$className/i", $contents)) {
                    if(preg_match("/^\s*namespace\s+([a-z_0-9\\\]+)\s*;/im", $contents, $ms)) {
                        $className = "$ms[1]\\$className";
                    }

                    try {
                        $repetitionBlock = 0;
                        restart:

                        if(class_exists($className)) {
                            $class = new ReflectionClass($className);

                            $reflections[$className] = $class;

                            if ($class->implementsInterface(ExposeClassInterface::class)) {
                                $purposes = $className::getPurposes();
                                foreach($purposes as $purpose) {
                                    $this->registerPurpose($purpose, $className);
                                }

                                $theModuleName = "";
                                $context->getSourceCodeManager()->isFilePartOfModule( $file, $theModuleName );


                                $par = $class;
                                $inheritance = [];
                                $info = [];

                                while($par = $par->getParentClass()) {
                                    array_unshift($inheritance, $par->getName());
                                }

                                if(preg_match_all("/^\s*use\s+([a-z_0-9\\\]+)\s*;/im", $contents, $ms)) {
                                    $imports = [];
                                    foreach ($ms[1] as $import) {
                                        $i = explode("\\", $import);
                                        $imports[ array_pop($i) ] = $import;
                                    }
                                    $info["imports"] = $imports;
                                }



                                if($inheritance)
                                    $info["inheritance"] = $inheritance;

                                if($class->isInstantiable() == false)
                                    $info["isAbstract"] = true;

                                if(($doc = $class->getDocComment()) && preg_match_all("/^\s*\*\s*@(display|module)\s*(.*?)\s*$/im", $doc, $ms)) {
                                    foreach($ms[1] as $idx => $pn) {
                                        if(strcasecmp($pn, 'display') == 0)
                                            $info["display"] = $ms[2][$idx];
                                        elseif(strcasecmp($pn, 'module') == 0)
                                            $info["module"] = $ms[2][$idx];
                                    }
                                }
                                if($theModuleName)
                                    $info["module"] = $theModuleName;

                                $methods = [];

                                if($class->implementsInterface( ExposeClassMethodsInterface::class )) {
                                    $methodFilterOptions = $className::getMethodFilterOptions();

                                    foreach ($class->getMethods( $methodFilterOptions  ) as $method) {

                                        if($methodFilterOptions & ExposeClassMethodsInterface::FILTER_STATIC && $method->isStatic() == false)
                                            continue;
                                        if($methodFilterOptions & ExposeClassMethodsInterface::FILTER_OBJECTIVE && $method->isStatic())
                                            continue;

                                        if($methodFilterOptions & ExposeClassMethodsInterface::FILTER_EXCLUDE_MAGIC && strpos($method->getName(), "__") === 0)
                                            continue;

                                        $mthd = [];

                                        if(($doc = $method->getDocComment()) && preg_match_all("/^\s*\*\s*@(purpose|display)\s*(.*?)\s*$/im", $doc, $ms)) {
                                            $mn = "$className::".$method->getName();
                                            foreach($ms[1] as $idx => $pn) {
                                                if(strcasecmp($pn, 'purpose') == 0)
                                                    $this->registerPurpose($ms[2][$idx], $mn, true);
                                                elseif(strcasecmp($pn, 'display') == 0)
                                                    $mthd["display"] = $ms[2][$idx];
                                            }
                                        } elseif($methodFilterOptions & ExposeClassMethodsInterface::FILTER_PURPOSED_ONLY)
                                            continue;

                                        if($method->isStatic())
                                            $mthd["isStatic"] = true;
                                        if($method->isPublic())
                                            $mthd["isPublic"] = true;
                                        if($method->isInternal())
                                            $mthd["isInternal"] = true;
                                        if($method->isAbstract())
                                            $mthd["isAbstract"] = true;
                                        if($method->isConstructor())
                                            $mthd["isConstructor"] = true;
                                        if($method->isDeprecated())
                                            $mthd["isDeprecated"] = true;
                                        if($method->isDestructor())
                                            $mthd["isDestructor"] = true;
                                        if($method->isFinal())
                                            $mthd["isFinal"] = true;
                                        if($method->isPrivate())
                                            $mthd["isPrivate"] = true;
                                        if($method->isProtected())
                                            $mthd["isProtected"] = true;

                                        if($return = $method->getReturnType())
                                            $mthd["return"] = $return->getName();

                                        $this->registered["methods"][ $className . "::" . $method->getName() ] = $mthd;
                                        $methods[] = $method->getName();
                                    }
                                }
                                if($methods)
                                    $info["methodNames"] = $methods;
                                $this->registered["classes"][$className] = $info;
                            }
                        } else {
                            include $file;
                            if(!$repetitionBlock) {
                                $repetitionBlock = 1;
                                goto restart;
                            }
                        }
                    } catch (ReflectionException $exception) {
                        $context->getLogger()->logError("Class $className not found");
                    } catch (Throwable $exception) {
						$context->getLogger()->logError("E: " . $exception->getMessage());
                    }
                }
            }
        }
        $context->getValueCache()->postValue($this->registered, self::CACHE_EXPOSED_SYMBOLS);
        $context->getValueCache()->postValue($reflections, self::CACHE_CLASS_REFLECTIONS);

        $data = var_export($this->registered, true);
        $dir = $context->getSkylineAppDirectory(CompilerConfiguration::SKYLINE_DIR_COMPILED);
        file_put_contents( "$dir/exposed.classes.php", "<?php\nreturn $data;" );

        spl_autoload_unregister($alf);
    }


    private function registerPurpose(string $purpose, string $className, bool $isMethod = false) {
        if(!$isMethod)
            $array = &$this->registered["purposes"];
        else
            $array = &$this->registered["method_purposes"];

        $ppParts = explode(".", strtoupper($purpose));
        while($part = array_shift($ppParts)) {
            if(!$ppParts)
                break;
            if(!isset($array[$part])) {
                $array[$part] = ['#'=>[]];
            }
            $array = &$array[$part];
        }

        if(!isset($array[$part]))
            $array[$part] = [];

        $list = &$array[$part];

        $list["#"][] = $className;
    }


    public function getCompilerName(): string
    {
        return "Expose Symbols Compiler";
    }
}