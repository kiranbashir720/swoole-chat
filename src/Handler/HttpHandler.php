<?php

declare(strict_types=1);

namespace App\Handler;

use App\Database\ConnectionPool;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use mysqli_sql_exception;

class HttpHandler
{
    public function __construct(private ConnectionPool $pool) {}

    public function handle(Request $request, Response $response): void
    {
        $response->header('Content-Type', 'application/json');

        $uri    = rtrim($request->server['request_uri'], '/');
        $method = $request->server['request_method'];

        if ($method !== 'GET') {
            $this->json($response, 405, ['error' => 'Method not allowed.']);
            return;
        }

        match (true) {
            $uri === '/users'                                                   => $this->listUsers($response),
            (bool) preg_match('#^/users/(\d+)$#', $uri, $m)                   => $this->getUser($response, (int) $m[1]),
            default                                                             => $this->json($response, 404, ['error' => 'Not found.']),
        };
    }

    private function listUsers(Response $response): void
    {
        $db = $this->pool->get();

        try {
            $stmt   = $db->prepare('SELECT id, username, email, created_at FROM users ORDER BY id DESC LIMIT 100');
            $stmt->execute();
            $result = $stmt->get_result();
            $users  = $result->fetch_all(MYSQLI_ASSOC);
            
            $this->json($response, 200, ['data' => $users]);

            $result->free();
            $stmt->close();
        } catch (mysqli_sql_exception) {
            $this->json($response, 500, ['error' => 'Failed to fetch users.']);
        } finally {
            $this->pool->release($db);
        }
    }

    private function getUser(Response $response, int $id): void
    {
        $db = $this->pool->get();

        try {
            $stmt = $db->prepare('SELECT id, username, email, created_at FROM users WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $result = $stmt->get_result();
            $user   = $result->fetch_assoc();

            $user
                ? $this->json($response, 200, ['data' => $user])
                : $this->json($response, 404, ['error' => 'User not found.']);

            $result->free();
            $stmt->close();
        } catch (mysqli_sql_exception) {
            $this->json($response, 500, ['error' => 'Failed to fetch user.']);
        } finally {
            $this->pool->release($db);
        }
    }

    private function json(Response $response, int $status, array $body): void
    {
        $response->status($status);
        $response->end(json_encode($body));
    }
}
