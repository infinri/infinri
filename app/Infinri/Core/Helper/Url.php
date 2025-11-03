<?php

declare(strict_types=1);

namespace Infinri\Core\Helper;

use Infinri\Core\Model\Url\Builder;

/**
 * Template helper for generating URLs
 * Provides convenient functions for use in PHTML templates
 */
class Url
{
    /**
     * URL Builder
     *
     * @var Builder
     */
    private Builder $builder;

    /**
     * Constructor
     *
     * @param Builder|null $builder URL Builder instance
     */
    public function __construct(?Builder $builder = null)
    {
        $this->builder = $builder ?? new Builder();
    }

    /**
     * Generate URL
     *
     * @param string $path Path or route name
     * @param array $params Route parameters
     * @param array $query Query string parameters
     * @return string Generated URL
     */
    public function url(string $path, array $params = [], array $query = []): string
    {
        return $this->builder->build($path, $params, $query);
    }

    /**
     * Generate route URL
     *
     * @param string $routeName Route name
     * @param array $params Route parameters
     * @param array $query Query string parameters
     * @return string Generated URL
     */
    public function route(string $routeName, array $params = [], array $query = []): string
    {
        return $this->builder->route($routeName, $params, $query);
    }

    /**
     * Generate absolute URL
     *
     * @param string $path Path or route name
     * @param array $params Route parameters
     * @param array $query Query string parameters
     * @return string Absolute URL
     */
    public function absolute(string $path, array $params = [], array $query = []): string
    {
        return $this->builder->absolute($path, $params, $query);
    }

    /**
     * Generate secure (HTTPS) URL
     *
     * @param string $path Path or route name
     * @param array $params Route parameters
     * @param array $query Query string parameters
     * @return string Secure URL
     */
    public function secure(string $path, array $params = [], array $query = []): string
    {
        return $this->builder->secure($path, $params, $query);
    }

    /**
     * Get current URL
     *
     * @return string Current URL
     */
    public function current(): string
    {
        return $this->builder->current();
    }

    /**
     * Get previous URL
     *
     * @param string|null $default Default URL
     * @return string Previous URL
     */
    public function previous(?string $default = null): string
    {
        return $this->builder->previous($default);
    }

    /**
     * Get base URL
     *
     * @return string Base URL
     */
    public function base(): string
    {
        return $this->builder->getBaseUrl();
    }

    /**
     * Get URL Builder instance
     *
     * @return Builder URL Builder
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }
}
