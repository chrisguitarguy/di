<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace OrnoTest;

use Orno\Di\Container;
use Orno\Di\Definition\Factory;

/**
 * ContainerTest
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    protected $configArray = [
        'OrnoTest\Assets\Foo' => [
            'class' => 'OrnoTest\Assets\Foo',
            'arguments' => ['OrnoTest\Assets\Bar'],
            'methods' => [
                'injectBaz' => ['OrnoTest\Assets\Baz']
            ]
        ],
        'OrnoTest\Assets\Bar' => [
            'definition' => 'OrnoTest\Assets\Bar',
            'arguments' => ['OrnoTest\Assets\Baz']
        ],
        'OrnoTest\Assets\Baz' => 'OrnoTest\Assets\Baz',
    ];

    public function testAutoResolvesNestedDependenciesWithAliasedInterface()
    {
        $c = new Container;

        $c->add('OrnoTest\Assets\BazInterface', 'OrnoTest\Assets\Baz');

        $foo = $c->get('OrnoTest\Assets\Foo');

        $this->assertInstanceOf('OrnoTest\Assets\Foo', $foo);
        $this->assertInstanceOf('OrnoTest\Assets\Bar', $foo->bar);
        $this->assertInstanceOf('OrnoTest\Assets\Baz', $foo->bar->baz);
        $this->assertInstanceOf('OrnoTest\Assets\BazInterface', $foo->bar->baz);
    }

    public function testInjectsArgumentsAndInvokesMethods()
    {
        $c = new Container;

        $c->add('OrnoTest\Assets\Bar')
          ->withArguments(['OrnoTest\Assets\Baz']);

        $c->add('OrnoTest\Assets\Baz');

        $c->add('OrnoTest\Assets\Foo')
          ->withArgument('OrnoTest\Assets\Bar')
          ->withMethodCall('injectBaz', ['OrnoTest\Assets\Baz']);

        $foo = $c->get('OrnoTest\Assets\Foo');

        $this->assertInstanceOf('OrnoTest\Assets\Foo', $foo);
        $this->assertInstanceOf('OrnoTest\Assets\Bar', $foo->bar);
        $this->assertInstanceOf('OrnoTest\Assets\Baz', $foo->baz);
    }

    public function testInjectsRuntimeArgumentsAndInvokesMethods()
    {
        $c = new Container;

        $c->add('OrnoTest\Assets\Bar')
          ->withArguments(['OrnoTest\Assets\Baz']);

        $c->add('closure1', function ($bar) use ($c) {
            return $c->get('OrnoTest\Assets\Foo', [$bar]);
        })->withArgument('OrnoTest\Assets\Bar');

        $c->add('OrnoTest\Assets\Baz');

        $c->add('OrnoTest\Assets\Foo')
          ->withArgument('OrnoTest\Assets\Bar')
          ->withMethodCalls(['injectBaz' => ['OrnoTest\Assets\Baz']]);

        $runtimeBar = new \OrnoTest\Assets\Bar(
            new \OrnoTest\Assets\Baz
        );

        $foo = $c->get('OrnoTest\Assets\Foo', [$runtimeBar]);

        $this->assertInstanceOf('OrnoTest\Assets\Foo', $foo);
        $this->assertInstanceOf('OrnoTest\Assets\Bar', $foo->bar);
        $this->assertInstanceOf('OrnoTest\Assets\Baz', $foo->baz);

        $this->assertSame($foo->bar, $runtimeBar);

        $fooClosure = $c->get('closure1');

        $this->assertInstanceOf('OrnoTest\Assets\Foo', $fooClosure);
        $this->assertInstanceOf('OrnoTest\Assets\Bar', $fooClosure->bar);
    }

    public function testSingletonReturnsSameInstanceEverytime()
    {
        $c = new Container;

        $c->singleton('OrnoTest\Assets\Baz');

        $this->assertTrue($c->isSingleton('OrnoTest\Assets\Baz'));

        $baz1 = $c->get('OrnoTest\Assets\Baz');
        $baz2 = $c->get('OrnoTest\Assets\Baz');

        $this->assertTrue($c->isSingleton('OrnoTest\Assets\Baz'));
        $this->assertSame($baz1, $baz2);
    }

    public function testStoresAndInvokesClosure()
    {
        $c = new Container;

        $c->add('foo', function () {
            $foo = new \OrnoTest\Assets\Foo(
                new \OrnoTest\Assets\Bar(
                    new \OrnoTest\Assets\Baz
                )
            );

            $foo->injectBaz(new \OrnoTest\Assets\Baz);

            return $foo;
        });

        $foo = $c->get('foo');

        $this->assertInstanceOf('OrnoTest\Assets\Foo', $foo);
        $this->assertInstanceOf('OrnoTest\Assets\Bar', $foo->bar);
        $this->assertInstanceOf('OrnoTest\Assets\Baz', $foo->baz);
    }

    public function testStoresAndInvokesClosureWithDefinedArguments()
    {
        $c = new Container;

        $baz = new \OrnoTest\Assets\Baz;
        $bar = new \OrnoTest\Assets\Bar($baz);

        $c->add('foo', function ($bar, $baz) {
            $foo = new \OrnoTest\Assets\Foo($bar);

            $foo->injectBaz($baz);

            return $foo;
        })->withArguments([$bar, $baz]);

        $foo = $c->get('foo');

        $this->assertInstanceOf('OrnoTest\Assets\Foo', $foo);
        $this->assertInstanceOf('OrnoTest\Assets\Bar', $foo->bar);
        $this->assertInstanceOf('OrnoTest\Assets\Baz', $foo->baz);
    }

    public function testSettingMethodCallOnClosureThrowsException()
    {
        $this->setExpectedException('Orno\Di\Exception\UnbindableMethodCallException');

        $c = new Container;

        $baz = new \OrnoTest\Assets\Baz;
        $bar = new \OrnoTest\Assets\Bar($baz);

        $c->add('foo', function ($bar, $baz) {
            $foo = new \OrnoTest\Assets\Foo($bar);

            $foo->injectBaz($baz);

            return $foo;
        })->withMethodCall('someMethod', []);
    }

    public function testSettingMethodCallsOnClosureThrowsException()
    {
        $this->setExpectedException('Orno\Di\Exception\UnbindableMethodCallException');

        $c = new Container;

        $baz = new \OrnoTest\Assets\Baz;
        $bar = new \OrnoTest\Assets\Bar($baz);

        $c->add('foo', function ($bar, $baz) {
            $foo = new \OrnoTest\Assets\Foo($bar);

            $foo->injectBaz($baz);

            return $foo;
        })->withMethodCalls([]);
    }

    public function testStoresAndReturnsArbitraryValues()
    {
        $baz1 = new \OrnoTest\Assets\Baz;
        $array1 = ['Phil', 'Bennett'];

        $c = new Container;

        $c->add('baz', $baz1);
        $baz2 = $c->get('baz');

        $c->add('array', $array1);
        $array2 = $c->get('array');

        $this->assertSame($baz1, $baz2);
        $this->assertSame($array1, $array2);
    }

    public function testReflectionOnNonClassThrowsException()
    {
        $this->setExpectedException('Orno\Di\Exception\ReflectionException');

        (new Container)->get('FakeClass');
    }

    public function testReflectionOnClassWithNoConstructorCreatesDefinition()
    {
        $c = new Container;

        $this->assertInstanceOf('OrnoTest\Assets\Baz', $c->get('OrnoTest\Assets\Baz'));
    }

    public function testReflectionInjectsDefaultValue()
    {
        $c = new Container;

        $this->assertSame('Phil Bennett', $c->get('OrnoTest\Assets\FooWithDefaultArg')->name);
    }

    public function testReflectionThrowsExceptionForArgumentWithNoDefaultValue()
    {
        $this->setExpectedException('Orno\Di\Exception\UnresolvableDependencyException');

        $c = new Container;

        $c->get('OrnoTest\Assets\FooWithNoDefaultArg');
    }

    public function testEnablingAndDisablingCachingWorksCorrectly()
    {
        $cache = $this->getMockBuilder('Orno\Cache\Cache')->disableOriginalConstructor()->getMock();

        $c = new Container($cache);

        $this->assertTrue($c->isCaching());

        $c->disableCaching();

        $this->assertFalse($c->isCaching());

        $c->enableCaching();

        $this->assertTrue($c->isCaching());
    }

    public function testContainerSetsCacheWhenAvailableAndEnabled()
    {
        $cache = $this->getMockBuilder('Orno\Cache\Cache')
                      ->setMethods(['get', 'set'])
                      ->disableOriginalConstructor()
                      ->getMock();

        $cache->expects($this->once())
              ->method('set')
              ->with($this->equalTo('orno::container::OrnoTest\Assets\Baz'));

        $cache->expects($this->once())
              ->method('get')
              ->with($this->equalTo('orno::container::OrnoTest\Assets\Baz'))
              ->will($this->returnValue(false));

        $c = new Container($cache);

        $this->assertInstanceOf('OrnoTest\Assets\Baz', $c->get('OrnoTest\Assets\Baz'));
    }

    public function testContainerGetsFromCacheWhenAvailableAndEnabled()
    {
        $cache = $this->getMockBuilder('Orno\Cache\Cache')
                      ->setMethods(['get', 'set'])
                      ->disableOriginalConstructor()
                      ->getMock();

        $definition = $this->getMockBuilder('Orno\Di\Definition\ClassDefinition')
                           ->disableOriginalConstructor()
                           ->getMock();

        $definition->expects($this->any())
                   ->method('__invoke')
                   ->will($this->returnValue(new Assets\Baz));

        $definition = serialize($definition);

        $cache->expects($this->once())
              ->method('get')
              ->with($this->equalTo('orno::container::OrnoTest\Assets\Baz'))
              ->will($this->returnValue($definition));

        $c = new Container($cache);

        $this->assertInstanceOf('OrnoTest\Assets\Baz', $c->get('OrnoTest\Assets\Baz'));
    }

    public function testArrayAccessMapsToCorrectMethods()
    {
        $c = new Container;

        $c['OrnoTest\Assets\Baz'] = 'OrnoTest\Assets\Baz';

        $this->assertInstanceOf('OrnoTest\Assets\Baz', $c['OrnoTest\Assets\Baz']);

        $this->assertTrue(isset($c['OrnoTest\Assets\Baz']));

        unset($c['OrnoTest\Assets\Baz']);

        $this->assertFalse(isset($c['OrnoTest\Assets\Baz']));
    }

    public function testContainerAcceptsArrayWithKey()
    {
        $c = new Container(null, ['di' => $this->configArray]);

        $foo = $c->get('OrnoTest\Assets\Foo');

        $this->assertInstanceOf('OrnoTest\Assets\Foo', $foo);
        $this->assertInstanceOf('OrnoTest\Assets\Bar', $foo->bar);
        $this->assertInstanceOf('OrnoTest\Assets\Baz', $foo->bar->baz);
        $this->assertInstanceOf('OrnoTest\Assets\BazInterface', $foo->bar->baz);

        $baz = $c->get('OrnoTest\Assets\Baz');
        $this->assertInstanceOf('OrnoTest\Assets\Baz', $foo->baz);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testContainerDoesntAcceptsArrayWithoutKey()
    {
        $c = new Container(null, $this->configArray);
    }

    public function testContainerAcceptsArrayAccess()
    {
        $config = $this->getMock('ArrayAccess', ['offsetGet', 'offsetSet', 'offsetUnset', 'offsetExists']);
        $config->expects($this->any())
               ->method('offsetGet')
               ->with($this->equalTo('di'))
               ->will($this->returnValue($this->configArray));

        $config->expects($this->any())
               ->method('offsetExists')
               ->with($this->equalTo('di'))
               ->will($this->returnValue(true));


        $c = new Container(null, $config);

        $foo = $c->get('OrnoTest\Assets\Foo');

        $this->assertInstanceOf('OrnoTest\Assets\Foo', $foo);
        $this->assertInstanceOf('OrnoTest\Assets\Bar', $foo->bar);
        $this->assertInstanceOf('OrnoTest\Assets\Baz', $foo->bar->baz);
        $this->assertInstanceOf('OrnoTest\Assets\BazInterface', $foo->bar->baz);

        $baz = $c->get('OrnoTest\Assets\Baz');
        $this->assertInstanceOf('OrnoTest\Assets\Baz', $foo->baz);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testContainerDoesntAcceptsInvalidConfigType()
    {
        $c = new Container(null, new \stdClass());
    }
}
