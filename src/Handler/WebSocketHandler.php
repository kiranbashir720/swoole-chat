<?php

declare(strict_types=1);

namespace App\Handler;

use App\Database\ConnectionPool;
use App\Validator\InputValidator;
use OpenSwoole\WebSocket\Server;
use OpenSwoole\WebSocket\Frame;
use PDOException;

class WebSocketHandler
{
    public function __construct(private ConnectionPool $pool) {}

    public function onOpen(Server $server, $request): void
    {
        echo "[WS] Connected: fd={$request->fd}\n";
    }

    public function onMessage(Server $server, Frame $frame): void
    {
        $data = json_decode($frame->data, true);

        if (!is_array($data) || empty($data['type'])) {
            $this->send($server, $frame->fd, 'error', 'Invalid payload.');
            return;
        }

        match ($data['type']) {
            'create_user' => $this->createUser($server, $frame->fd, $data),
            default       => $this->send($server, $frame->fd, 'error', 'Unknown type.'),
        };
    }

    public function onClose(Server $server, int $fd): void
    {
        echo "[WS] Disconnected: fd={$fd}\n";
    }

    private function createUser(Server $server, int $fd, array $data): void
    {
        try {
            $username = InputValidator::username($data['username'] ?? '');
            $email    = InputValidator::email($data['email'] ?? '');
        } catch (\InvalidArgumentException $e) {
            $this->send($server, $fd, 'error', $e->getMessage());
            return;
        }

        $db = $this->pool->get();

        try {
            $stmt = $db->prepare('INSERT INTO users (username, email) VALUES (:username, :email)');
            $stmt->execute([':username' => $username, ':email' => $email]);

            $this->send($server, $fd, 'user_created', 'User created.', [
                'id'       => (int) $db->lastInsertId(),
                'username' => $username,
                'email'    => $email,
            ]);
        } catch (PDOException $e) {
            $message = str_contains($e->getMessage(), 'Duplicate')
                ? 'Username or email already exists.'
                : 'Failed to create user.';

            $this->send($server, $fd, 'error', $message);
        } finally {
            $this->pool->release($db);
        }
    }

    private function send(Server $server, int $fd, string $type, string $message, array $data = []): void
    {
        $server->push($fd, json_encode(array_filter([
            'type'    => $type,
            'message' => $message,
            'data'    => $data ?: null,
        ])));
    }
}
