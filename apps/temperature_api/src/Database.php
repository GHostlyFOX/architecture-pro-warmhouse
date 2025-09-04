<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private PDO $pdo;

    public function __construct(string $host, string $port, string $dbname, string $user, string $password)
    {
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        try {
            $this->pdo = new PDO($dsn, $user, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function saveTemperature(int $deviceId, float $temperature): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO temperature_logs (device_id, temperature) VALUES (:device_id, :temperature)'
        );
        $stmt->execute(['device_id' => $deviceId, 'temperature' => $temperature]);
    }
}
