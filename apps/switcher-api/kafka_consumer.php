<?php

require 'vendor/autoload.php';

use App\Database;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;

echo "Starting Kafka Consumer for 'new_device' topic...\n";

// --- Config ---
$dbHost = getenv('DB_HOST');
$dbPort = getenv('DB_PORT');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASSWORD');
$kafkaBrokers = getenv('KAFKA_BROKERS');
$topicName = 'new_device';

// --- Database Connection ---
$db = new Database($dbHost, $dbPort, $dbName, $dbUser, $dbPass);
echo "Database connection established.\n";

// --- Kafka Consumer ---
$conf = new Conf();
$conf->set('group.id', 'switcher-api-consumer');
$conf->set('metadata.broker.list', $kafkaBrokers);
$conf->set('auto.offset.reset', 'earliest');

$consumer = new KafkaConsumer($conf);
$consumer->subscribe([$topicName]);

echo "Subscribed to topic '$topicName'. Waiting for messages...\n";

while (true) {
    $message = $consumer->consume(120 * 1000); // 120 seconds timeout
    switch ($message->err) {
        case RD_KAFKA_RESP_ERR_NO_ERROR:
            echo "Received message: " . $message->payload . "\n";
            $data = json_decode($message->payload, true);

            if (isset($data['device_type']) && $data['device_type'] === 'switcher') {
                if (isset($data['device_id']) && isset($data['user_id'])) {
                    try {
                        $db->createSwitcher((int)$data['device_id'], (int)$data['user_id']);
                        echo "Successfully registered new switcher with device_id: " . $data['device_id'] . "\n";
                    } catch (Exception $e) {
                        echo "Failed to create switcher: " . $e->getMessage() . "\n";
                    }
                } else {
                    echo "Message is missing device_id or user_id.\n";
                }
            } else {
                // Ignore messages that are not for switchers
                echo "Ignoring message with device_type: " . ($data['device_type'] ?? 'unknown') . "\n";
            }
            break;
        case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            // No more messages; will wait for more
            break;
        case RD_KAFKA_RESP_ERR__TIMED_OUT:
            // Timed out
            break;
        default:
            echo "Kafka error: " . $message->errstr() . "\n";
            break;
    }
}
