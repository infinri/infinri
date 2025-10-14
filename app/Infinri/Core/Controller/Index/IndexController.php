<?php
declare(strict_types=1);

namespace Infinri\Core\Controller\Index;

use Infinri\Core\Controller\AbstractController;
use Infinri\Core\App\Response;
use Infinri\Core\Block\Template;
use Infinri\Core\Block\Container;
use Infinri\Core\Block\Text;
use Infinri\Core\Model\View\TemplateResolver;
use Infinri\Core\Model\Module\ModuleManager;

/**
 * Homepage Controller
 */
class IndexController extends AbstractController
{
    public function __construct(
        \Infinri\Core\App\Request $request,
        \Infinri\Core\App\Response $response,
        private readonly ModuleManager $moduleManager
    ) {
        parent::__construct($request, $response);
    }

    /**
     * Homepage action
     *
     * @return Response
     */
    public function execute(): Response
    {
        // Create root container
        $page = new Container();
        $page->setName('root');
        $page->setData('htmlTag', 'html');
        $page->setData('htmlClass', 'no-js');
        
        // Create body container
        $body = new Container();
        $body->setName('body');
        $body->setData('htmlTag', 'body');
        $body->setData('htmlClass', 'page-home');
        
        // Create header
        $header = new Container();
        $header->setName('header');
        $header->setData('htmlTag', 'header');
        $header->setData('htmlClass', 'page-header');
        
        $headerText = new Text();
        $headerText->setText('<h1>Welcome to Infinri Framework</h1>');
        $header->addChild($headerText);
        
        // Create main content with template
        $content = new Template();
        $content->setName('content');
        $content->setTemplate('Infinri_Core::homepage.phtml');
        $content->setTemplateResolver(new TemplateResolver($this->moduleManager));
        $content->setData('title', 'Infinri Framework');
        $content->setData('message', 'A modern PHP MVC framework built with TDD');
        
        // Create footer
        $footer = new Container();
        $footer->setName('footer');
        $footer->setData('htmlTag', 'footer');
        $footer->setData('htmlClass', 'page-footer');
        
        $footerText = new Text();
        $footerText->setText('<p>&copy; 2025 Infinri Framework. Built with ❤️</p>');
        $footer->addChild($footerText);
        
        // Assemble page
        $body->addChild($header);
        $body->addChild($content);
        $body->addChild($footer);
        $page->addChild($body);
        
        // Render and return
        return $this->response->setBody($page->toHtml());
    }
}
