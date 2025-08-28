<?php

namespace App\Security\Test;

use Exception;

class SessionDataDecoder
{
    public static function unserialize(?string $sessionData): array {
        $method = ini_get("session.serialize_handler");
        return match ($method) {
            "php" => self::unserializePhp($sessionData),
            default => throw new Exception("Unsupported session.serialize_handler: " . $method),
        };
    }

    public static function serialize(?array $sessionData): string {
        $method = ini_get("session.serialize_handler");
        return match ($method) {
            "php" => self::serializePhp($sessionData),
            default => throw new Exception("Unsupported session.serialize_handler: " . $method),
        };
    }

    private static function unserializePhp(?string $sessionData): array {
        $decodedData = [];
        $currentOffset = 0;

        while ($currentOffset < strlen((string) $sessionData)) {
            $fragment = substr((string) $sessionData, $currentOffset);

            if (!str_contains($fragment, "|")) {
                throw new Exception("Invalid session data, remaining");
            }
            $separatorPosition = strpos((string) $sessionData, "|", $currentOffset);
            $fragmentLength = $separatorPosition - $currentOffset;

            $name = substr((string) $sessionData, $currentOffset, $fragmentLength);
            $currentOffset += $fragmentLength + 1;

            $fragment = substr((string) $sessionData, $currentOffset);

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