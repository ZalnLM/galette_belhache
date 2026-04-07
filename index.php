<?php
declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');

$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Core/Flash.php';
require_once __DIR__ . '/app/Core/Csrf.php';
require_once __DIR__ . '/app/Core/Auth.php';
require_once __DIR__ . '/app/Core/View.php';
require_once __DIR__ . '/app/Core/Router.php';
require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Controllers/HomeController.php';
require_once __DIR__ . '/app/Controllers/QuoteRequestController.php';
require_once __DIR__ . '/app/Controllers/AdminController.php';

Database::getInstance();

$router = new Router();

$router->get('', [HomeController::class, 'index']);
$router->get('login', [AuthController::class, 'login']);
$router->post('login', [AuthController::class, 'storeLogin']);
$router->get('register', [AuthController::class, 'register']);
$router->post('register', [AuthController::class, 'storeRegister']);
$router->get('logout', [AuthController::class, 'logout']);

$router->get('mes-demandes', [QuoteRequestController::class, 'index']);
$router->post('demande-devis', [QuoteRequestController::class, 'store']);
$router->get('demande-devis/{id}', [QuoteRequestController::class, 'show']);
$router->post('demande-devis/{id}/message', [QuoteRequestController::class, 'storeMessage']);

$router->get('admin', [AdminController::class, 'dashboard']);
$router->get('admin/users', [AdminController::class, 'users']);
$router->post('admin/users/{id}/update', [AdminController::class, 'updateUser']);
$router->get('admin/recipes', [AdminController::class, 'recipes']);
$router->post('admin/recipes', [AdminController::class, 'storeRecipe']);
$router->get('admin/formulas', [AdminController::class, 'formulas']);
$router->post('admin/formulas', [AdminController::class, 'storeFormula']);
$router->get('admin/quote-requests', [AdminController::class, 'quoteRequests']);
$router->get('admin/quote-requests/{id}', [AdminController::class, 'showQuoteRequest']);
$router->post('admin/quote-requests/{id}/status', [AdminController::class, 'updateQuoteRequestStatus']);
$router->post('admin/quote-requests/{id}/message', [AdminController::class, 'storeQuoteRequestMessage']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
