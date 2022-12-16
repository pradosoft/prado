<?php

namespace Prado\PHPStan;

use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;

class DynamicMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
     public function hasMethod(ClassReflection $classReflection, string $methodName): bool
     {
          if (!$classReflection->is('Prado\TComponent')) {
               return false;
          }

          return strncasecmp($methodName, 'dy', 2) === 0 || strncasecmp($methodName, 'fx', 2) === 0;
     }

     public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
     {
          return new DynamicMethodReflection($classReflection, $methodName);
     }
}
