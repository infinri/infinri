<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Index;

use Infinri\Core\Controller\AbstractController;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\App\ErrorHandler;
use Infinri\Core\Model\View\LayoutFactory;
use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Core\Helper\Logger;
use Throwable;

/**
 * CMS Homepage Controller
 */
class Index extends AbstractController
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
            Logger::info('CMS: Loading homepage');

            // Load homepage from database (url_key = 'home')
            $page = $this->pageRepository->getByUrlKey('home');

            if (!$page || !$page->getData('is_active')) {
                Logger::warning('CMS: Homepage not found or inactive');
                // Homepage missing - show 500 since this is a configuration error
                throw new \RuntimeException('Homepage not configured. Please run setup:upgrade to install default pages.');
            }

            // Render homepage layout with page data
            $html = $this->layoutFactory->render('cms_index_index', [
                'page' => $page,
            ]);

            $this->response->setBody($html);

            return $this->response;

        } catch (Throwable $e) {
            return $this->errorHandler->handle500($e, $this->response);
        }
    }
}
