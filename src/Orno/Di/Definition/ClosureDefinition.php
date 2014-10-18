<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace Orno\Di\Definition;

use Orno\Di\ContainerInterface;
use Orno\Di\Exception;

/**
 * ClosureDefinition
 */
class ClosureDefinition extends AbstractDefinition
{
    /**
     * @var string
     */
    protected $closure;

    /**
     * Constructor
     *
     * @param string                      $alias
     * @param \Closure                    $closure
     * @param \Orno\Di\ContainerInterface $container
     */
    public function __construct($alias, \Closure $closure, ContainerInterface $container)
    {
        parent::__construct($alias, $container);
        $this->closure   = $closure;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $args = [])
    {
        return call_user_func_array($this->closure, $this->resolveArguments($args));
    }

    /**
     * {@inheritdoc}
     */
    public function withMethodCall($method, array $args = [])
    {
        throw new Exception\UnbindableMethodCallException(
            sprintf('Cannot bind a method call to a Closure aliased as [%s]', $this->alias)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function withMethodCalls(array $methods = [])
    {
        throw new Exception\UnbindableMethodCallException(
            sprintf('Cannot bind method calls to a Closure aliased as [%s]', $this->alias)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createReflection()
    {
        return new \ReflectionFunction($this->closure);
    }
}
