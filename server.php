<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

// Load .env file
foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#')) continue;
    [$key, $value] = explode('=', $line, 2);
    putenv(trim($key) . '=' . trim($value));
}

use App\Database\ConnectionPool;
use App\Handler\HttpHandler;
use App\Handler\WebSocketHandler;
use OpenSwoole\WebSocket\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\WebSocket\Frame;

$config = require __DIR__ . '/config/database.php';

$pool      = new ConnectionPool($config);
$wsHandler = new WebSocketHandler($pool);
$httpHandler = new HttpHandler($pool);

$server = new Server('0.0.0.0', 9502);

$server->on('start', function () {
    echo "[Server] Running on http://0.0.0.0:9502\n";
    echo "[Server] WebSocket on ws://0.0.0.0:9502\n";
});

$server->on('open', fn(Server $s, $req) => $wsHandler->onOpen($s, $req));

$server->on('message', fn(Server $s, Frame $f) => $wsHandler->onMessage($s, $f));

$server->on('close', fn(Server $s, int $fd) => $wsHandler->onClose($s, $fd));

$server->on('request', fn(Request $req, Response $res) => $httpHandler->handle($req, $res));

$server->start();
