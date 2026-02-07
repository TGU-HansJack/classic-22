<?php
if (PHP_SAPI !== 'cli') {
    exit('ws-server.php must run in CLI mode');
}

if (!defined('__TYPECHO_ROOT_DIR__')) {
    if (!defined('__DIR__')) {
        define('__DIR__', dirname(__FILE__));
    }

    $rootDir = dirname(__DIR__, 3);
    $config = $rootDir . DIRECTORY_SEPARATOR . 'config.inc.php';

    if (!is_file($config)) {
        fwrite(STDERR, "Missing config.inc.php: {$config}\n");
        exit(1);
    }

    require_once $config;
}

require_once __DIR__ . '/functions.php';

\Widget\Init::alloc();
\Widget\Options::alloc()->to($options);

set_time_limit(0);
error_reporting(E_ALL);

$host = isset($argv[1]) && trim((string) $argv[1]) !== ''
    ? trim((string) $argv[1])
    : (string) (classic22LinuxDoGetOption($options, 'liveWsHost', '127.0.0.1'));

$port = isset($argv[2]) && (int) $argv[2] > 0
    ? (int) $argv[2]
    : (int) (classic22LinuxDoGetOption($options, 'liveWsPort', '9527'));
if ($port <= 0) {
    $port = 9527;
}

$server = @stream_socket_server("tcp://{$host}:{$port}", $errno, $errstr);
if (!$server) {
    echo "WS server start failed: {$errstr} ({$errno})\n";
    exit(1);
}

stream_set_blocking($server, false);

$clients = [];

function classic22_ws_frame_text(string $payload): string
{
    $len = strlen($payload);
    $frame = chr(0x81);

    if ($len < 126) {
        $frame .= chr($len);
    } elseif ($len <= 0xFFFF) {
        $frame .= chr(126) . pack('n', $len);
    } else {
        $hi = intdiv($len, 4294967296);
        $lo = $len % 4294967296;
        $frame .= chr(127) . pack('N2', $hi, $lo);
    }

    return $frame . $payload;
}

function classic22_ws_decode_frames(string $buffer): array
{
    $frames = [];
    $offset = 0;
    $total = strlen($buffer);

    while ($offset + 2 <= $total) {
        $b1 = ord($buffer[$offset]);
        $b2 = ord($buffer[$offset + 1]);
        $opcode = $b1 & 0x0F;
        $masked = ($b2 & 0x80) !== 0;
        $len = $b2 & 0x7F;
        $header = 2;

        if ($len === 126) {
            if ($offset + 4 > $total) {
                break;
            }
            $len = unpack('n', substr($buffer, $offset + 2, 2))[1];
            $header = 4;
        } elseif ($len === 127) {
            if ($offset + 10 > $total) {
                break;
            }
            $parts = unpack('Nhigh/Nlow', substr($buffer, $offset + 2, 8));
            $len = ((int) $parts['high']) * 4294967296 + ((int) $parts['low']);
            $header = 10;
        }

        $maskLen = $masked ? 4 : 0;
        $need = $header + $maskLen + $len;
        if ($offset + $need > $total) {
            break;
        }

        $mask = $masked ? substr($buffer, $offset + $header, 4) : '';
        $payload = substr($buffer, $offset + $header + $maskLen, $len);

        if ($masked) {
            $decoded = '';
            for ($i = 0; $i < $len; $i++) {
                $decoded .= chr(ord($payload[$i]) ^ ord($mask[$i % 4]));
            }
            $payload = $decoded;
        }

        $frames[] = [
            'opcode' => $opcode,
            'payload' => $payload,
        ];

        $offset += $need;
    }

    return [$frames, substr($buffer, $offset)];
}

function classic22_ws_counts(array $clients): array
{
    $counts = [];
    foreach ($clients as $client) {
        if (!is_array($client) || empty($client['path'])) {
            continue;
        }
        $path = classic22LiveNormalizePath((string) $client['path']);
        if (!isset($counts[$path])) {
            $counts[$path] = 0;
        }
        $counts[$path]++;
    }
    return $counts;
}

echo "WS server started at ws://{$host}:{$port}\n";

while (true) {
    $read = [$server];
    foreach ($clients as $client) {
        if (isset($client['conn']) && is_resource($client['conn'])) {
            $read[] = $client['conn'];
        }
    }

    $write = null;
    $except = null;
    @stream_select($read, $write, $except, 1);

    foreach ($read as $sock) {
        if ($sock === $server) {
            $conn = @stream_socket_accept($server, 0);
            if (!$conn) {
                continue;
            }

            stream_set_blocking($conn, false);
            $id = (int) $conn;
            $clients[$id] = [
                'conn' => $conn,
                'handshake' => false,
                'buffer' => '',
                'path' => '/',
            ];
            continue;
        }

        $id = (int) $sock;
        if (!isset($clients[$id])) {
            continue;
        }

        $chunk = @fread($sock, 8192);
        if ($chunk === '' || $chunk === false) {
            if (feof($sock)) {
                @fclose($sock);
                unset($clients[$id]);
            }
            continue;
        }

        $clients[$id]['buffer'] .= $chunk;

        if (!$clients[$id]['handshake']) {
            if (strpos($clients[$id]['buffer'], "\r\n\r\n") === false) {
                continue;
            }

            $req = $clients[$id]['buffer'];
            $clients[$id]['buffer'] = '';

            if (!preg_match('/Sec-WebSocket-Key:\s*(.+)\r\n/i', $req, $match)) {
                @fclose($sock);
                unset($clients[$id]);
                continue;
            }

            $key = trim($match[1]);
            $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

            $headers = "HTTP/1.1 101 Switching Protocols\r\n"
                . "Upgrade: websocket\r\n"
                . "Connection: Upgrade\r\n"
                . "Sec-WebSocket-Accept: {$accept}\r\n\r\n";

            @fwrite($sock, $headers);
            $clients[$id]['handshake'] = true;
            continue;
        }

        [$frames, $left] = classic22_ws_decode_frames($clients[$id]['buffer']);
        $clients[$id]['buffer'] = $left;

        foreach ($frames as $frame) {
            $opcode = (int) ($frame['opcode'] ?? 0);
            $payload = (string) ($frame['payload'] ?? '');

            if ($opcode === 0x8) {
                @fclose($sock);
                unset($clients[$id]);
                break;
            }

            if ($opcode !== 0x1) {
                continue;
            }

            $decoded = json_decode($payload, true);
            if (is_array($decoded) && (($decoded['type'] ?? '') === 'subscribe')) {
                $clients[$id]['path'] = classic22LiveNormalizePath((string) ($decoded['path'] ?? $decoded['page'] ?? '/'));
            }

            $counts = classic22_ws_counts($clients);
            foreach ($clients as $cid => $target) {
                if (!isset($target['conn']) || !is_resource($target['conn']) || empty($target['handshake'])) {
                    continue;
                }
                $path = classic22LiveNormalizePath((string) ($target['path'] ?? '/'));
                $msg = json_encode([
                    'type' => 'online',
                    'path' => $path,
                    'count' => (int) ($counts[$path] ?? 0),
                    'transport' => 'websocket',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (is_string($msg) && $msg !== '') {
                    @fwrite($target['conn'], classic22_ws_frame_text($msg));
                }
            }
        }
    }
}
