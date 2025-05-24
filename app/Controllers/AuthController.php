<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        // Validate inputs first (add your length and pattern checks here)
        $errors = [];

        if (strlen($username) < 4) {
            $errors['username'] = 'Username must be at least 4 characters';
        }
        if (strlen($password) < 8 || !preg_match('/\d/', $password)) {
            $errors['password'] = 'Password must be at least 8 characters and contain at least 1 number';
        }

        if (!empty($errors)) {
            return $this->render($response, 'auth/register.twig', [
                'errors' => $errors,
                'username' => $username,
            ]);
        }

        try {
            $this->authService->register($username, $password);
            $this->logger->info("User registered: {$username}");
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Username already taken') {
                $errors['username'] = 'Username already taken';
            } else {
                $errors['general'] = 'An error occurred during registration. Please try again.';
            }

            return $this->render($response, 'auth/register.twig', [
                'errors' => $errors,
                'username' => $username,
            ]);
        }

        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user login, handle login failures

        $data = (array)$request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if($this->authService->attempt($username, $password))
        {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        return $this->render($response, 'auth/login.twig', ['error' => 'Invalid username or password']);
    }

    public function logout(Request $request, Response $response): Response
    {
        // TODO: handle logout by clearing session data and destroying session

        session_unset();
        session_destroy();

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
