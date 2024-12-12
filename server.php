<?php

$host = '127.0.0.1';
$port = 8080;
$server = stream_socket_server("tcp://$host:$port", $errno, $errorMessage);

if (!$server) {
    die("Failed to create socket: $errorMessage\n");
}

echo "Server running on $host:$port\n";

while ($client = @stream_socket_accept($server)) {
    $request = fread($client, 5000);
    if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $request, $matches)) {
        $key = trim($matches[1]);
        $acceptKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";

        fwrite($client, $headers);
        echo "WebSocket connection established\n";

        while (!feof($client)) {
            $data = fread($client, 2048);
            if ($data) {
                $message = unmask($data);
                echo "Received: " . $message . "\n";
                
                // Respond to client
                $response = "Server received: " . $message;
                fwrite($client, frame($response));
            }
        }

        fclose($client);
    }
}

function unmask($payload) {
    $length = ord($payload[1]) & 127;

    if ($length == 126) {
        $masks = substr($payload, 4, 4);
        $data = substr($payload, 8);
    } elseif ($length == 127) {
        $masks = substr($payload, 10, 4);
        $data = substr($payload, 14);
    } else {
        $masks = substr($payload, 2, 4);
        $data = substr($payload, 6);
    }

    $text = '';
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }
    return $text;
}

function frame($message) {
    $length = strlen($message);
    if ($length <= 125) {
        return chr(129) . chr($length) . $message;
    } elseif ($length <= 65535) {
        return chr(129) . chr(126) . pack('n', $length) . $message;
    } else {
        return chr(129) . chr(127) . pack('Q', $length) . $message;
    }
}
