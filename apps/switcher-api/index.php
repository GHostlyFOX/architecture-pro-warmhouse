<?php

require 'vendor/autoload.php';

use App\Database;
use App\AuthService;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher;

// --- Config ---
$dbHost = getenv('DB_HOST');
$dbPort = getenv('DB_PORT');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASSWORD');
$monolithApiUrl = getenv('MONOLITH_API_URL') ?: 'http://monolith:8080';

// --- DI Container ---
$container = [
    'db' => function () use ($dbHost, $dbPort, $dbName, $dbUser, $dbPass) {
        return new Database($dbHost, $dbPort, $dbName, $dbUser, $dbPass);
    },
    'auth_service' => function () use ($monolithApiUrl) {
        return new AuthService($monolithApiUrl);
    }
];

// --- Routing ---
$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('POST', '/switchers/{id:\d+}/on', 'turn_on');
    $r->addRoute('POST', '/switchers/{id:\d+}/off', 'turn_off');
    $r->addRoute('GET', '/switchers/{id:\d+}', 'get_state');
});

// --- Request Handling ---
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
    case Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
    case Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        $deviceId = (int)$vars['id'];

        // --- Auth ---
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }

        $authService = $container['auth_service']();
        $userId = $authService->getUserId($authHeader);

        if ($userId === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            break;
        }

        // --- Ownership Check ---
        $db = $container['db']();
        if (!$db->checkSwitcherOwnership($deviceId, $userId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            break;
        }

        // --- Handlers ---
        if ($handler === 'turn_on' || $handler === 'turn_off') {
            $newState = ($handler === 'turn_on');
            $db->setSwitcherState($deviceId, $newState);
            http_response_code(200);
            echo json_encode(['status' => $newState ? 'on' : 'off']);
        }

        if ($handler === 'get_state') {
            $state = $db->getSwitcherState($deviceId);
            http_response_code(200);
            echo json_encode(['status' => $state ? 'on' : 'off']);
        }

        break;
}
