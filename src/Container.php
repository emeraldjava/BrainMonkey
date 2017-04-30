<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the BrainMonkey package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Monkey;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package BrainMonkey
 * @license http://opensource.org/licenses/MIT MIT
 */
final class Container
{

    /**
     * @var Container
     */
    private static $instance;

    /**
     * @var array
     */
    private $services = [];

    /**
     * Static instance lookup.
     *
     * @return static
     */
    public static function instance()
    {
        self::$instance or self::$instance = new static();

        return self::$instance;
    }

    /**
     * @return \Brain\Monkey\Expectation\ExpectationFactory
     */
    public function expectationFactory()
    {
        return $this->service(__FUNCTION__, new Expectation\ExpectationFactory());
    }

    /**
     * @return \Brain\Monkey\Hooks\HookRunningStack
     */
    public function hookRunningStack()
    {
        return $this->service(__FUNCTION__, new Hooks\HookRunningStack());
    }

    /**
     * @return \Brain\Monkey\Hooks\HookStorage
     */
    public function hookStorage()
    {
        return $this->service(__FUNCTION__, new Hooks\HookStorage());
    }

    /**
     * @return \Brain\Monkey\Hooks\HookExpectationExecutor
     */
    public function hookExpectationExecutor()
    {
        return $this->service(__FUNCTION__, new Hooks\HookExpectationExecutor(
            $this->hookRunningStack(),
            $this->expectationFactory()
        ));
    }

    /**
     * @return \Brain\Monkey\Expectation\FunctionStubFactory
     */
    public function functionStubFactory()
    {
        return $this->service(__FUNCTION__, new Expectation\FunctionStubFactory());
    }

    public function reset()
    {
        $this->expectationFactory()->reset();
        $this->hookRunningStack()->reset();
        $this->hookStorage()->reset();
        $this->functionStubFactory()->reset();
    }

    /**
     * @param string $id
     * @param mixed  $service
     * @return mixed
     */
    private function service($id, $service)
    {
        if ( ! array_key_exists($id, $this->services)) {
            $this->services[$id] = $service;
        }

        return $this->services[$id];
    }
}