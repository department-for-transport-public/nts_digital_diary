<?php

namespace App\Security\Test;

use Exception;

class SessionDataDecoder
{
    public static function unserialize($sessionData) {
        $method = ini_get("session.serialize_handler");
        switch ($method) {
            case "php":
                return self::unserializePhp($sessionData);
            default:
                throw new Exception("Unsupported session.serialize_handler: " . $method);
        }
    }

    public static function serialize($sessionData): string {
        $method = ini_get("session.serialize_handler");
        switch ($method) {
            case "php":
                return self::serializePhp($sessionData);
            default:
                throw new Exception("Unsupported session.serialize_handler: " . $method);
        }
    }

    private static function unserializePhp(?string $sessionData) {
        $decodedData = [];
        $currentOffset = 0;

        while ($currentOffset < strlen($sessionData)) {
            $fragment = substr($sessionData, $currentOffset);

            if (!strstr($fragment, "|")) {
                throw new Exception("Invalid session data, remaining");
            }
            $separatorPosition = strpos($sessionData, "|", $currentOffset);
            $fragmentLength = $separatorPosition - $currentOffset;

            $name = substr($sessionData, $currentOffset, $fragmentLength);
            $currentOffset += $fragmentLength + 1;

            $fragment = substr($sessionData, $currentOffset);

            $decodedData[$name] = unserialize($fragment);
            $currentOffset += strlen(serialize($decodedData[$name]));
        }
        return $decodedData;
    }

    private static function serializePhp(?array $sessionData): string {
        if (empty($sessionData)) {
            return "";
        }

        $encoded = "";
        foreach($sessionData as $key => $value) {
            $encoded .= "$key|".serialize($value);
        }

        return $encoded;
    }
}