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

    /**
     * Creates a new switcher in the database.
     * The default status is FALSE (off).
     */
    public function createSwitcher(int $deviceId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO switchers (device_id, user_id) VALUES (:device_id, :user_id) ON CONFLICT (device_id) DO NOTHING'
        );
        $stmt->execute(['device_id' => $deviceId, 'user_id' => $userId]);
    }

    /**
     * Checks if a user owns a specific switcher.
     */
    public function checkSwitcherOwnership(int $deviceId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM switchers WHERE device_id = :device_id AND user_id = :user_id'
        );
        $stmt->execute(['device_id' => $deviceId, 'user_id' => $userId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Sets the state of a switcher (on/off).
     */
    public function setSwitcherState(int $deviceId, bool $state): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE switchers SET status = :status, updated_at = NOW() WHERE device_id = :device_id'
        );
        $stmt->execute(['status' => $state, 'device_id' => $deviceId]);
    }

    /**
     * Gets the current state of a switcher.
     */
    public function getSwitcherState(int $deviceId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT status FROM switchers WHERE device_id = :device_id'
        );
        $stmt->execute(['device_id' => $deviceId]);
        return (bool)$stmt->fetchColumn();
    }
}
