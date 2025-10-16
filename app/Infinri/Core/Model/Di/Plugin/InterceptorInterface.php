<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Di\Plugin;

/**
 * Interceptor Interface
 * 
 * Base interface for plugins that intercept method calls
 * Plugins can implement before/around/after methods
 */
interface InterceptorInterface
{
    /**
     * Example before method signature
     * 
     * Before methods are called before the target method
     * They can modify the arguments passed to the target method
     * 
     * Method name: before{MethodName}
     * 
     * @param object $subject Target object
     * @param mixed ...$arguments Original arguments
     * @return array|null Modified arguments or null to keep original
     */
    // public function beforeMethodName(object $subject, ...$arguments): ?array;

    /**
     * Example around method signature
     * 
     * Around methods wrap the target method execution
     * They receive a callable to execute the original method
     * 
     * Method name: around{MethodName}
     * 
     * @param object $subject Target object
     * @param callable $proceed Original method callable
     * @param mixed ...$arguments Method arguments
     * @return mixed Modified result
     */
    // public function aroundMethodName(object $subject, callable $proceed, ...$arguments): mixed;

    /**
     * Example after method signature
     * 
     * After methods are called after the target method
     * They can modify the result of the target method
     * 
     * Method name: after{MethodName}
     * 
     * @param object $subject Target object
     * @param mixed $result Original result
     * @param mixed ...$arguments Original arguments
     * @return mixed Modified result
     */
    // public function afterMethodName(object $subject, mixed $result, ...$arguments): mixed;
}
