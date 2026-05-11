<?php

declare(strict_types=1);

namespace App\Database;

use mysqli;
use RuntimeException;
use SplQueue;

class ConnectionPool
{
    private SplQueue $pool;
    private int $size;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->size   = $config['pool_size'];
        $this->pool   = new SplQueue();

        $this->initialize();
    }

    private function initialize(): void
    {
        for ($i = 0; $i < $this->size; $i++) {
            try {
                $this->pool->enqueue($this->createConnection());
            } catch (RuntimeException $e) {
                echo "[Database Error] Pre-initialization failed: " . $e->getMessage() . "\n";
            }
        }
    }

    private function createConnection(): mysqli
    {
        try {
            $mysqli = new mysqli(
                $this->config['host'],
                $this->config['username'],
                $this->config['password'],
                $this->config['dbname'],
                (int) $this->config['port']
            );

            $mysqli->set_charset('utf8mb4');

            return $mysqli;
        } catch (\mysqli_sql_exception $e) {
            throw new RuntimeException('DB connection failed: ' . $e->getMessage());
        }
    }

    public function get(): mysqli
    {
        if ($this->pool->isEmpty()) {
            return $this->createConnection();
        }

        $db = $this->pool->dequeue();

        // Check if the connection is still alive
        if (!$db->ping()) {
            // If dead, close and create a new one
            $db->close();
            $db = $this->createConnection();
        }

        return $db;
    }

    public function release(mysqli $connection): void
    {
        $this->pool->enqueue($connection);
    }
}
