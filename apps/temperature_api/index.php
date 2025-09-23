<?php

require 'vendor/autoload.php';

use App\Database;
use App\KafkaProducer;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher;

// --- Config ---
$dbHost = getenv('DB_HOST');
$dbPort = getenv('DB_PORT');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASSWORD');
$kafkaBrokers = getenv('KAFKA_BROKERS');
$authToken = 'my-secret-token'; // Hardcoded token

// --- DI Container ---
$container = [
    'db' => function () use ($dbHost, $dbPort, $dbName, $dbUser, $dbPass) {
        return new Database($dbHost, $dbPort, $dbName, $dbUser, $dbPass);
    },
    'kafka_producer' => function () use ($kafkaBrokers) {
        return new KafkaProducer($kafkaBrokers, 'current_temp');
    }
];

// --- Routing ---
$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('POST', '/temperature', 'post_temperature');
    $r->addRoute('GET', '/temperature/{device_id:\d+}', 'get_temperature');
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

        // --- Auth ---
        if (!isset($_SERVER['HTTP_AUTHORIZATION']) || $_SERVER['HTTP_AUTHORIZATION'] !== 'Bearer ' . $authToken) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }

        // --- Handlers ---
        if ($handler === 'post_temperature') {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['device_id']) || !isset($data['temp'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing device_id or temp']);
                break;
            }

            $deviceId = (int)$data['device_id'];
            $temp = (float)$data['temp'];

            // Save to DB
            $db = $container['db']();
            $db->saveTemperature($deviceId, $temp);

            // Send to Kafka
            $kafkaProducer = $container['kafka_producer']();
            $kafkaProducer->sendMessage(json_encode(['device_id' => $deviceId, 'temp' => $temp]));

            http_response_code(200);
            echo json_encode(['status' => 'success']);
        }

        if ($handler === 'get_temperature') {
            $deviceId = (int)$vars['device_id'];
            $randomTemp = round(rand(100, 400) / 10, 1); // Random temp between 10.0 and 40.0

            http_response_code(200);
            echo json_encode(['device_id' => $deviceId, 'temp' => $randomTemp]);
        }

        break;
}
