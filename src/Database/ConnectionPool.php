<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
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
            $this->pool->enqueue($this->createConnection());
        }
    }

    private function createConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->config['host'],
            $this->config['port'],
            $this->config['dbname']
        );

        try {
            $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('DB connection failed: ' . $e->getMessage());
        }

        return $pdo;
    }

    public function get(): PDO
    {
        if ($this->pool->isEmpty()) {
            return $this->createConnection();
        }

        return $this->pool->dequeue();
    }

    public function release(PDO $connection): void
    {
        $this->pool->enqueue($connection);
    }
}
