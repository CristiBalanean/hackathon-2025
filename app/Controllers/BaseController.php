<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

abstract class BaseController
{
    public function __construct(
        protected Twig $view,
    ) 
    {        
        $currentUserId = $_SESSION['user_id'] ?? null;
        $currentUserName = $_SESSION['username'] ?? null;

        $this->view->getEnvironment()->addGlobal('currentUserId', $currentUserId);
        $this->view->getEnvironment()->addGlobal('currentUserName', $currentUserName);
    }

    protected function render(Response $response, string $template, array $data = []): Response
    {
        return $this->view->render($response, $template, $data);
    }

    // TODO: add here any common controller logic and use in concrete controllers
}
