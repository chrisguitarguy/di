<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace OrnoTest;

class ParamterReflectionTraitTest extends \PHPUnit_Framework_TestCase
{
    use \Orno\Di\ParameterReflectionTrait;

    public static function noTypeHints($one)
    {
        
    }

    public static function hasTypeHints(\Exception $e)
    {
        
    }

    public static function hasDefaults($one='example')
    {
        
    }

    /**
     * @expectedException Orno\Di\Exception\UnresolvableDependencyException
     */
    public function testParamtersWithoutTypeHintAndDefaultValueThrowsException()
    {
        $this->reflectArguments(new \ReflectionMethod(__CLASS__, 'noTypeHints'));
    }

    public function testNullReflectionObjectReturnsEmptyParamArray()
    {
        $params = $this->reflectArguments(null);

        $this->assertInternalType('array', $params);
        $this->assertEmpty($params);
    }

    public function testParametersWithTypehintsAreResolvedToClassNames()
    {
        $params = $this->reflectArguments(new \ReflectionMethod(__CLASS__, 'hasTypeHints'));

        $this->assertCount(1, $params);
        $this->assertEquals('Exception', $params[0]);
    }

    public function testParamtersWithDefaultValuesUseDefaultsAsParamValue()
    {
        $params = $this->reflectArguments(new \ReflectionMethod(__CLASS__, 'hasDefaults'));

        $this->assertcount(1, $params);
        $this->assertEquals('example', $params[0]);
    }
}
