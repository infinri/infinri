<?php
declare(strict_types=1);

namespace Infinri\Core\Controller;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;
use Infinri\Core\Security\CsrfGuard;
use Infinri\Core\Helper\Logger;

/**
 * Base class for all admin/backend controllers.
 * Provides common functionality for admin panel operations.
 *
 * @package Infinri\Core\Controller
 */
abstract class AbstractAdminController extends AbstractController
{
    protected LayoutFactory $layoutFactory;
    protected CsrfGuard $csrfGuard;

    public function __construct(
        Request       $request,
        Response      $response,
        LayoutFactory $layoutFactory,
        CsrfGuard     $csrfGuard
    ) {
        parent::__construct($request, $response);
        $this->layoutFactory = $layoutFactory;
        $this->csrfGuard = $csrfGuard;
    }

    /**
     * Render admin layout with given handle
     *
     * @param string $handle Layout handle (e.g., 'admin_users_index')
     * @param array $data Additional data to pass to layout
     * @return Response
     */
    protected function renderAdminLayout(string $handle, array $data = []): Response
    {
        $html = $this->layoutFactory->render($handle, $data);
        return $this->response->setBody($html);
    }

    /**
     * Validate CSRF token from request
     *
     * @param string $tokenId Token identifier
     * @param string|null $token Token value from request
     * @return bool True if valid
     */
    protected function validateCsrf(string $tokenId, ?string $token): bool
    {
        return $this->csrfGuard->validateToken($tokenId, $token);
    }

    /**
     * Require valid CSRF token or return 403 response
     *
     * @param string $tokenId Token identifier
     * @param string|null $token Token value from request
     * @return Response|null Returns Response with 403 if invalid, null if valid
     */
    protected function requireCsrf(string $tokenId, ?string $token): ?Response
    {
        if (!$this->validateCsrf($tokenId, $token)) {
            Logger::warning('CSRF validation failed', [
                'token_id' => $tokenId,
                'request_path' => $this->request->getPath()
            ]);

            return $this->response
                ->setForbidden()
                ->setBody('403 Forbidden - Invalid or missing CSRF token');
        }

        return null;
    }

    /**
     * Get CSRF token from request parameter
     *
     * @param string $paramName Parameter name (default: '_csrf_token')
     * @return string|null
     */
    protected function getCsrfTokenFromRequest(string $paramName = '_csrf_token'): ?string
    {
        $token = $this->request->getParam($paramName);
        return is_string($token) ? $token : null;
    }

    /**
     * Require POST request or redirect
     *
     * @param string $redirectRoute Route to redirect if not POST
     * @return Response|null Returns Response if not POST, null if valid POST
     */
    protected function requirePost(string $redirectRoute): ?Response
    {
        if (!$this->request->isPost()) {
            return $this->redirectToRoute($redirectRoute);
        }

        return null;
    }

    /**
     * Redirect to route with query parameters
     *
     * @param string $route Route path (e.g., '/admin/users/index')
     * @param array $params Query parameters
     * @param int $code HTTP status code (default: 302)
     * @return Response
     */
    protected function redirectToRoute(string $route, array $params = [], int $code = 302): Response
    {
        $url = $route;

        if (!empty($params)) {
            $queryString = http_build_query($params);
            $url .= (str_contains($route, '?') ? '&' : '?') . $queryString;
        }

        return $this->redirect($url, $code);
    }

    /**
     * Redirect with success message
     *
     * @param string $route Route path
     * @param string $message Success message (optional)
     * @return Response
     */
    protected function redirectWithSuccess(string $route, string $message = ''): Response
    {
        $params = ['success' => '1'];

        if (!empty($message)) {
            $params['message'] = $message;
        }

        return $this->redirectToRoute($route, $params);
    }

    /**
     * Redirect with error message
     *
     * @param string $route Route path
     * @param string $message Error message (optional)
     * @return Response
     */
    protected function redirectWithError(string $route, string $message = ''): Response
    {
        $params = ['error' => '1'];

        if (!empty($message)) {
            $params['message'] = $message;
        }

        return $this->redirectToRoute($route, $params);
    }

    /**
     * Return 403 Forbidden response
     *
     * @param string $message Optional error message
     * @return Response
     */
    protected function forbidden(string $message = '403 Forbidden'): Response
    {
        return $this->response
            ->setForbidden()
            ->setBody($message);
    }

    /**
     * Return 500 Internal Server Error response
     *
     * @param \Throwable $e Exception
     * @param bool $showDetails Show exception details (default: false for security)
     * @return Response
     */
    protected function serverError(\Throwable $e, bool $showDetails = false): Response
    {
        Logger::error('Admin controller error', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        $message = $showDetails
            ? '500 Internal Server Error - ' . $e->getMessage()
            : '500 Internal Server Error';

        return $this->response
            ->setServerError()
            ->setBody($message);
    }

    /**
     * Get integer parameter from request with default
     *
     * @param string $name Parameter name
     * @param int $default Default value
     * @return int
     */
    protected function getIntParam(string $name, int $default = 0): int
    {
        return (int)$this->request->getParam($name, $default);
    }

    /**
     * Get string parameter from request with default
     *
     * @param string $name Parameter name
     * @param string $default Default value
     * @return string
     */
    protected function getStringParam(string $name, string $default = ''): string
    {
        return (string)$this->request->getParam($name, $default);
    }

    /**
     * Get boolean parameter from request
     *
     * @param string $name Parameter name
     * @param bool $default Default value
     * @return bool
     */
    protected function getBoolParam(string $name, bool $default = false): bool
    {
        return (bool)$this->request->getParam($name, $default);
    }

    /**
     * Check if request has parameter
     *
     * @param string $name Parameter name
     * @return bool
     */
    protected function hasParam(string $name): bool
    {
        return $this->request->getParam($name) !== null;
    }
}
