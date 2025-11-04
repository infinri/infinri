<?php

declare(strict_types=1);

namespace Infinri\Core\Controller;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;

/**
 * Base class for all controllers.
 */
abstract class AbstractController
{
    /**
     * @var Request HTTP request
     */
    protected Request $request;

    /**
     * @var Response HTTP response
     */
    protected Response $response;

    public function __construct(
        Request $request,
        Response $response
    ) {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Execute controller action.
     */
    abstract public function execute(): Response;

    /**
     * Get request.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get response.
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Redirect to URL.
     */
    protected function redirect(string $url, int $code = 302): Response
    {
        return $this->response->setRedirect($url, $code);
    }

    /**
     * Return JSON response.
     *
     * @throws \JsonException
     */
    protected function json(mixed $data): Response
    {
        return $this->response->setJson($data);
    }

    /**
     * Return 404 response.
     */
    protected function notFound(): Response
    {
        return $this->response
            ->setNotFound()
            ->setBody('404 - Page Not Found');
    }

    /**
     * Set response body.
     */
    protected function setBody(string $body): Response
    {
        return $this->response->setBody($body);
    }
}
