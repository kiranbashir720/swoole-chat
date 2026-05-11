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
            $stmt   = mysqli_prepare($db, 'SELECT id, username, email, created_at FROM users ORDER BY id DESC LIMIT 100');
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $users  = mysqli_fetch_all($result, MYSQLI_ASSOC);
            
            $this->json($response, 200, ['data' => $users]);

            mysqli_free_result($result);
            mysqli_stmt_close($stmt);
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
            $stmt = mysqli_prepare($db, 'SELECT id, username, email, created_at FROM users WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $user   = mysqli_fetch_assoc($result);

            $user
                ? $this->json($response, 200, ['data' => $user])
                : $this->json($response, 404, ['error' => 'User not found.']);

            mysqli_free_result($result);
            mysqli_stmt_close($stmt);
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
