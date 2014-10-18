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
     * Resolves all of the arguments.  If you do not send an array of arguments
     * it will use the Definition Arguments.
     *
     * @param  array $args The arguments to us instead of $this->arguments
     * @return array The resolved arguments.
     */
    protected function resolveArguments($args = [])
    {
        $args = (empty($args)) ? $this->arguments : $args;

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
}
