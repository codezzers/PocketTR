<?php

declare(strict_types=1);

namespace pocketmine;

class QueryManager{

    public static function query(string $host, int $port, int $timeout = 2){
        $socket = @fsockopen('udp://' . $host, $port, $errno, $errstr, $timeout);

        if($errno || $socket === false){
            return ['error' => $errstr];
        }

        stream_set_timeout($socket, $timeout);
        stream_set_blocking($socket, true);

        $randInt = mt_rand(1, 999999999);
        $reqPacket = "\x01";
        $reqPacket .= pack('Q*', $randInt);
        $reqPacket .= "\x00\xff\xff\x00\xfe\xfe\xfe\xfe\xfd\xfd\xfd\xfd\x12\x34\x56\x78"; // magic string
        $reqPacket .= pack('Q*', 0);

        fwrite($socket, $reqPacket, strlen($reqPacket));

        $response = fread($socket, 4096);

        fclose($socket);

        if (empty($response) || $response === false) {
            return ['error' => 'server do not answer'];
        }
        if (substr($response, 0, 1) !== "\x1C") {
            return ['error' => 'error'];
        }

        $serverInfo = substr($response, 35);
        $serverInfo = preg_replace("#§.#", "", $serverInfo);
        $serverInfo = explode(';', $serverInfo);

        return = [
            'motd' => $serverInfo[1],
            'num' => $serverInfo[4],
            'max' => $serverInfo[5],
            'version' => $serverInfo[3],
        ];
    }
}