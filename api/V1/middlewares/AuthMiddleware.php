<?php

namespace API\V1\Middlewares;

use MiladRahimi\PhpRouter\Router;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;
use API\V1\Controllers\AuthController;
use Closure;

class AuthMiddleware 
{
    private $auth;

    public function handle(ServerRequestInterface $request, Closure $next)
    {
        $this->auth = new AuthController();

        if ($this->auth->validateRequest($request)) {            
            // Call the next middleware/controller
            return $next($request);
        }

        return new JsonResponse(['error' => 'You need to login first'], 401);
    }
}