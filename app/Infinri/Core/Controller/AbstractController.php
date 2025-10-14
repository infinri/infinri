<?php
declare(strict_types=1);

namespace Infinri\Core\Controller;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\Layout\Loader;
use Infinri\Core\Model\Layout\Merger;
use Infinri\Core\Model\Layout\Processor;
use Infinri\Core\Model\Layout\Builder;
use Infinri\Core\Model\Layout\Renderer;

/**
 * Abstract Controller
 * 
 * Base class for all controllers
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
     * Execute controller action
     *
     * @return Response
     */
    abstract public function execute(): Response;

    /**
     * Get request
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get response
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Redirect to URL
     *
     * @param string $url
     * @param int $code
     * @return Response
     */
    protected function redirect(string $url, int $code = 302): Response
    {
        return $this->response->setRedirect($url, $code);
    }

    /**
     * Return JSON response
     *
     * @param mixed $data
     * @return Response
     */
    protected function json(mixed $data): Response
    {
        return $this->response->setJson($data);
    }

    /**
     * Return 404 response
     *
     * @return Response
     */
    protected function notFound(): Response
    {
        return $this->response
            ->setNotFound()
            ->setBody('404 - Page Not Found');
    }

    /**
     * Set response body
     *
     * @param string $body
     * @return Response
     */
    protected function setBody(string $body): Response
    {
        return $this->response->setBody($body);
    }
}
