<?php

declare(strict_types=1);

namespace Infinri\Core\App;

/**
 * Represents a matched route with controller, action, and parameters
 */
class Route
{
    public function __construct(
        public readonly string $controller,
        public readonly string $action,
        public readonly array  $params = []
    ) {}

    /**
     * Get fully qualified controller class name with placeholders replaced
     *
     * @param callable $sanitizer Function to sanitize parameter values
     * @return string
     */
    public function getControllerClass(callable $sanitizer): string
    {
        $controllerClass = $this->controller;

        foreach ($this->params as $key => $value) {
            // Sanitize and capitalize for class name
            $sanitizedValue = $sanitizer($value);
            $className = ucfirst($sanitizedValue);
            $controllerClass = str_replace(":{$key}", $className, $controllerClass);
        }

        return $controllerClass;
    }

    /**
     * Check if route has placeholder in controller name
     *
     * @return bool
     */
    public function hasPlaceholders(): bool
    {
        return str_contains($this->controller, ':');
    }
}
