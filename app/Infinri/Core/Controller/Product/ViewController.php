<?php
declare(strict_types=1);

namespace Infinri\Core\Controller\Product;

use Infinri\Core\Controller\AbstractController;
use Infinri\Core\App\Response;

/**
 * Product View Controller
 */
class ViewController extends AbstractController
{
    /**
     * View product action
     *
     * @return Response
     */
    public function execute(): Response
    {
        $productId = $this->request->getParam('id', 'unknown');
        
        $html = sprintf(
            <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Product %s - Infinri Framework</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #2563eb; }
        .product-id { background: #f0f0f0; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Product View</h1>
    <div class="product-id">
        <strong>Product ID:</strong> %s
    </div>
    <p>This demonstrates URL parameter extraction from routes.</p>
    <p>Route pattern: <code>/product/:id</code></p>
    <p><a href="/">← Back to Home</a></p>
</body>
</html>
HTML,
            htmlspecialchars($productId),
            htmlspecialchars($productId)
        );
        
        return $this->response->setBody($html);
    }
}
