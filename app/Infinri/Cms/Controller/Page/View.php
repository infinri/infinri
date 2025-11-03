<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Page;

use Infinri\Core\Controller\AbstractController;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\App\ErrorHandler;
use Infinri\Core\Model\View\LayoutFactory;
use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Core\Helper\Logger;
use Throwable;

/**
 * CMS Page View Controller
 */
class View extends AbstractController
{
    private LayoutFactory $layoutFactory;
    private PageRepository $pageRepository;
    private ErrorHandler $errorHandler;

    public function __construct(
        Request        $request,
        Response       $response,
        LayoutFactory  $layoutFactory,
        PageRepository $pageRepository,
        ErrorHandler   $errorHandler
    ) {
        parent::__construct($request, $response);
        $this->layoutFactory = $layoutFactory;
        $this->pageRepository = $pageRepository;
        $this->errorHandler = $errorHandler;
    }

    public function execute(): Response
    {
        try {
            // Get URL key from request parameter (from URL rewrite) or path
            $urlKey = $this->request->getParam('key');

            if (!$urlKey) {
                // Fallback to path if no 'key' parameter (e.g., '/about' => 'about')
                $path = trim($this->request->getPath(), '/');
                $urlKey = $path ?: 'home';
            }

            Logger::info('CMS: Looking up page by URL key', [
                'url_key' => $urlKey,
                'from_param' => $this->request->getParam('key') ? 'yes' : 'no'
            ]);

            // Load page by URL key
            $page = $this->pageRepository->getByUrlKey($urlKey);

            if (!$page || !$page->getData('is_active')) {
                // Page not found - use error handler for consistent 404 handling
                return $this->errorHandler->handle404($this->response, $urlKey);
            }

            // Render page layout with page data
            $html = $this->layoutFactory->render('cms_page_view', [
                'page' => $page,
            ]);

            $this->response->setBody($html);

            return $this->response;

        } catch (Throwable $e) {
            return $this->errorHandler->handle500($e, $this->response);
        }
    }
}
