<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace Orno\Di;

/**
 * Capable of inspecting parameters on `ReflectionFunction` objects and figuring
 * out what dependencies need to be included
 */
trait ParameterReflectionTrait
{
    /**
     * Inspects the parameter list for the object to locate the the parameters
     * required for definition invocation.
     *
     * @return  array A list of parameters.
     */
    public static function reflectArguments(\ReflectionFunctionAbstract $reflection=null)
    {
        $params = [];

        if (null === $reflection) {
            return $params;
        }

        foreach ($reflection->getParameters() as $param) {
            $class = $param->getClass();
            if (null === $class) {
                if ($param->isDefaultValueAvailable()) {
                    $params[] = $param->getDefaultValue();
                    continue;
                }

                throw new Exception\UnresolvableDependencyException(sprintf(
                    'Unable to resolve a non-class dependency of [%s] for [%s]', $param, $reflection
                ));
            }

            $params[] = $class->getName();
        }

        return $params;
    }
}
