<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the BrainMonkey package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Monkey\Names;

/**
 * Provides a string representation for a callback.
 *
 * Callbacks are not checked for real callable capability, but only for syntax.
 * E.g. something like `new CallbackStringForm(['FooClass', 'foo_method'])` would not raise any
 * error even if the class is not available.
 * However, `new CallbackStringForm(['FooClass', 'foo-method'])` would raise an error for invalid
 * method name.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package BrainMonkey
 * @license http://opensource.org/licenses/MIT MIT
 */
final class CallbackStringForm
{

    /**
     * @var string
     */
    private $parsed;

    /**
     * @param callable $callback
     */
    public function __construct($callback)
    {
        $this->parsed = $this->parseCallback($callback);
    }

    /**
     * @param \Brain\Monkey\Names\CallbackStringForm $callback
     * @return bool
     */
    public function equals(CallbackStringForm $callback)
    {
        return (string)$this === (string)$callback;
    }

    /**
     * @return string
     * @throws \Brain\Monkey\Names\Exception\NotInvokableObjectAsCallback
     */
    public function __toString()
    {
        return $this->parsed;
    }

    /**
     * @param $callback
     * @return string
     * @throws \Brain\Monkey\Names\Exception\InvalidCallable
     * @throws \Brain\Monkey\Names\Exception\NotInvokableObjectAsCallback
     */
    private function parseCallback($callback)
    {
        if ( ! is_callable($callback, true)) {
            throw Exception\InvalidCallable::forCallable($callback);
        }

        if (is_string($callback)) {
            return $this->parseString($callback);
        }

        $is_object = is_object($callback);

        if ($is_object && ! is_callable($callback)) {
            throw new Exception\NotInvokableObjectAsCallback();
        }

        if ($is_object) {
            return $callback instanceof \Closure
                ? (new ClosureStringForm($callback))->name()
                : get_class($callback).'()';
        }

        list($object, $method) = $callback;

        $method_name = (new MethodName($method))->name();

        if (is_string($object)) {
            $class_name = (new ClassName($object))->fullyQualifiedName();

            $this->assertMethodCallable($class_name, $method_name, $callback);

            return "{$class_name}::{$method_name}()";
        }

        if ( ! is_callable([$object, $method_name])) {
            throw new Exception\NotInvokableObjectAsCallback();
        }

        $class_name = (new ClassName(get_class($object)))->fullyQualifiedName();

        return "{$class_name}->{$method_name}()";
    }

    /**
     * @param string $callback
     * @return bool|string
     * @throws \Brain\Monkey\Names\Exception\InvalidCallable
     * @throws \Brain\Monkey\Names\Exception\NotInvokableObjectAsCallback
     */
    private function parseString($callback)
    {
        $callback = trim($callback);

        // First check if this is a closure is string form, and just return it if so
        $closure = strpos($callback, 'function') === 0 && substr($callback, -1) === ')'
            ? $callback
            : '';
        $closure_normalized = $closure ? ClosureStringForm::normalizeString($callback) : '';
        if ($closure && ! $closure_normalized) {
            throw Exception\InvalidCallable::forCallable($callback);
        } elseif ($closure_normalized) {
            return $closure_normalized;
        }

        // If this is not a string in normalized form, we just check is a valid function name
        if (substr($callback, -2) !== '()') {
            return (new FunctionName($callback))->fullyQualifiedName();
        }

        // remove parenthesis
        $callback = substr($callback, 0, -2);

        $is_dynamic_method = substr_count($callback, '->') === 1;
        $is_static_method = substr_count($callback, '::') === 1;

        // If this is a normalized form of a static or dynamic method let's check that both class
        // and method names are fine
        if ($is_dynamic_method || $is_static_method) {
            $separator = $is_dynamic_method ? '->' : '::';
            list($class, $method) = explode($separator, $callback);
            $class_name = (new ClassName($class))->fullyQualifiedName();
            $method_name = (new MethodName($method))->name();
            $this->assertMethodCallable($class_name, $method, "{$callback}()");

            return "{$class_name}{$separator}{$method_name}()";
        }

        // Last chance is that the string is fully qualified name of an invokable object.
        $class_name = (new ClassName($callback))->fullyQualifiedName();
        // Check `__invoke` method existence only if class is available
        if (class_exists($class_name) && ! method_exists($class_name, '__invoke')) {
            throw new Exception\NotInvokableObjectAsCallback();
        }

        return "{$class_name}()";
    }

    /**
     * Ensure method existence only if class is available.
     *
     * @param string       $class_name
     * @param string       $method
     * @param string|array $callable
     * @throws \Brain\Monkey\Names\Exception\InvalidCallable
     * @throws \Brain\Monkey\Names\Exception\NotInvokableObjectAsCallback
     */
    private function assertMethodCallable($class_name, $method, $callable)
    {
        if (
            class_exists($class_name)
            && ! (method_exists($class_name, $method) || is_callable([$class_name, $method]))
        ) {
            throw Exception\InvalidCallable::forCallable($callable);
        }
    }
}