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
 * An abstract base class for definition objects.
 */
abstract class AbstractDefinition implements DefinitionInterface
{
    use \Orno\Di\ParameterReflectionTrait;

    /**
     * @var \Orno\Di\ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $methods = [];

    /**
     * @var boolean
     */
    protected $autowired = false;

    /**
     * Constructor
     *
     * @param string                      $alias
     * @param \Orno\Di\ContainerInterface $container
     */
    public function __construct($alias, ContainerInterface $container)
    {
        $this->alias     = $alias;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function withArgument($arg)
    {
        $this->arguments[] = $arg;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withArguments(array $args)
    {
        foreach ($args as $arg) {
            $this->withArgument($arg);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethodCall($method, array $args = [])
    {
        $this->methods[] = [
            'method'    => $method,
            'arguments' => $args
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethodCalls(array $methods = [])
    {
        foreach ($methods as $method => $args) {
            $this->withMethodCall($method, $args);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function autowired()
    {
        $this->autowired = true;
        return $this;
    }

    /**
     * Resolves all of the arguments.  If you do not send an array of arguments
     * it will use the Definition Arguments.
     *
     * @param  array $args The arguments to us instead of $this->arguments
     * @return array The resolved arguments.
     */
    protected function resolveArguments($args = [])
    {
        $args = (empty($args)) ? $this->findArguments() : $args;

        $resolvedArguments = [];

        foreach ($args as $arg) {
            if (is_string($arg) && ($this->container->isRegistered($arg) || class_exists($arg))) {
                $resolvedArguments[] = $this->container->get($arg);
            } else {
                $resolvedArguments[] = $arg;
            }
        }

        return $resolvedArguments;
    }

    /**
     * Looks up the arguments for the definition. If the definition is autowired
     * this means using reflection, otherwise, simply return the definition
     * argument list.
     *
     * @return  array A list of argument aliases
     */
    protected function findArguments()
    {
        if (!$this->autowired) {
            return $this->arguments;
        }

        return $this->reflectArguments($this->createReflection());
    }

    /**
     * Create the Reflection object for the definition.
     *
     * @return  \ReflectionFunctionAbstract
     */
    abstract protected function createReflection();
}
