<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace Orno\Di\Definition;

use Orno\Di\ContainerInterface;

/**
 * ClassDefinition
 */
class ClassDefinition extends AbstractDefinition
{

    /**
     * @var string
     */
    protected $class;

    /**
     * Constructor
     *
     * @param string                      $alias
     * @param string                      $concrete
     * @param \Orno\Di\ContainerInterface $container
     */
    public function __construct($alias, $concrete, ContainerInterface $container)
    {
        parent::__construct($alias, $container);
        $this->class     = $concrete;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $args = [])
    {
        $resolved = $this->resolveArguments($args);

        $reflection = new \ReflectionClass($this->class);
        $object = $reflection->newInstanceArgs($resolved);

        return $this->invokeMethods($object);
    }

    /**
     * Invoke methods on resolved object
     *
     * @param  object $object
     * @return object
     */
    protected function invokeMethods($object)
    {
        foreach ($this->methods as $method) {
            $reflection = new \ReflectionMethod($object, $method['method']);

            $args = [];

            foreach ($method['arguments'] as $arg) {
                $args[] = ($this->container->isRegistered($arg)) ? $this->container->get($arg) : $arg;
            }

            $reflection->invokeArgs($object, $args);
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function createReflection()
    {
        $ref = new \ReflectionClass($this->class);
        return $ref->getConstructor();
    }
}
