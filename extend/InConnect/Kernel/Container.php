<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/5/4
 * Time: 14:28
 */

namespace InConnect\Kernel;




class Container implements \ArrayAccess
{
    protected $values = array();
    private $factories;
    private $protected;
    private $frozen = array();
    private $raw = array();
    private $keys = array();


    public function __construct($values = [])
    {
        $this->factories = new \SplObjectStorage();
        $this->protected = new \SplObjectStorage();

        foreach ($values as $key => $value) {
            $this->offsetSet($key,$value);
        }
    }

    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
        return isset($this->keys[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @throws \Exception
     */
    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
        if (!isset($this->keys[$offset])) {
            throw new \Exception(sprintf('Identifier "%s" is not defined.', $offset));
        }

        if (
            isset($this->raw[$offset])
            || !\is_object($this->values[$offset])
            || isset($this->protected[$this->values[$offset]])
            || !\method_exists($this->values[$offset], '__invoke')
        ) {
            return $this->values[$offset];
        }

        if (isset($this->factories[$this->values[$offset]])) {
            return $this->values[$offset]($this);
        }

        $raw = $this->values[$offset];
        $val = $this->values[$offset] = $raw($this);
        $this->raw[$offset] = $raw;

        $this->frozen[$offset] = true;
        return $val;

    }

    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
        $this->values[$offset] = $value;
        $this->keys[$offset] = true;
    }

    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
        if (isset($this->keys[$offset])) {
            if (\is_object($this->values[$offset])) {
                unset($this->factories[$this->values[$offset]], $this->protected[$this->values[$offset]]);
            }
            unset($this->values[$offset], $this->frozen[$offset], $this->raw[$offset], $this->keys[$offset]);
        }
    }

    public function factory($callable)
    {
        if (!\method_exists($callable, '__invoke')) {
            die('Service definition is not a Closure or invokable object.');
        }

        $this->factories->attach($callable);

        return $callable;
    }

    public function protect($callable)
    {
        if (!\method_exists($callable, '__invoke')) {
//            throw new ExpectedInvokableException('Callable is not a Closure or invokable object.');
        }

        $this->protected->attach($callable);

        return $callable;
    }

    public function raw($id)
    {
        if (!isset($this->keys[$id])) {
        }

        if (isset($this->raw[$id])) {
            return $this->raw[$id];
        }

        return $this->values[$id];
    }

    public function extend($id, $callable)
    {
        if (!isset($this->keys[$id])) {
//            throw new UnknownIdentifierException($id);
        }

        if (isset($this->frozen[$id])) {
//            throw new FrozenServiceException($id);
        }

        if (!\is_object($this->values[$id]) || !\method_exists($this->values[$id], '__invoke')) {
//            throw new InvalidServiceIdentifierException($id);
        }

        if (isset($this->protected[$this->values[$id]])) {
            @\trigger_error(\sprintf('How Pimple behaves when extending protected closures will be fixed in Pimple 4. Are you sure "%s" should be protected?', $id), \E_USER_DEPRECATED);
        }

        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
//            throw new ExpectedInvokableException('Extension service definition is not a Closure or invokable object.');
        }

        $factory = $this->values[$id];

        $extended = function ($c) use ($callable, $factory) {
            return $callable($factory($c), $c);
        };

        if (isset($this->factories[$factory])) {
            $this->factories->detach($factory);
            $this->factories->attach($extended);
        }

        return $this[$id] = $extended;
    }


    /**
     * Returns all defined value names.
     *
     * @return array An array of value names
     */
    public function keys()
    {
        return \array_keys($this->values);
    }

    public function register(ServiceProviderInterface $provider, array $values = array())
    {
        $provider->register($this);
        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
        return $this;
    }
}