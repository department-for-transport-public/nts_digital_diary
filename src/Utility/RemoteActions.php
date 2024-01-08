<?php

namespace App\Utility;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;
use Throwable;

class RemoteActions
{
    const HMAC_TIMEOUT = 30;

    public static function run(?string $path): void
    {
        switch($path) {
            case '/_ah/warmup':
                if (!array_key_exists('HTTP_X_GOOG_IAP_JWT_ASSERTION', $_SERVER)
                    && ($_SERVER['HTTP_X_GOOGLE_INTERNAL_SKIPADMINCHECK'] ?? '') === 'true'
                ) {
                    //self::runProcess(['../bin/console', 'cache:clear']);
                    echo "Warmup successful";
                }
                exit;

            case '/_util/pre-install' :
                try {
                    self::denyAccessUnlessHmacPasses($_GET['hmac'] ?? '', $_GET['timestamp'] ?? 0, 'pre-install');
                    echo self::preInstall('../');
                } catch (Throwable $e) {
                    self::handleException($e);
                }
                exit;

            case '/_util/post-install' :
                try {
                    self::denyAccessUnlessHmacPasses($_GET['hmac'] ?? '', $_GET['timestamp'] ?? 0, 'post-install');
                    echo self::postInstall('../');
                } catch (Throwable $e) {
                    self::handleException($e);
                }
                exit;
        }
    }

    public static function preInstall($kernelDir = ''): string
    {
        // previous migration count checks have been removed (2021-08-25), check git history if reinstating
        $output = "";
        $lockStatus = self::runProcess(["{$kernelDir}bin/console", 'nts:maintenance-mode:status'], false);
        if (stripos($lockStatus, 'not active') !== false) {
            throw new HttpException(500, "{$output}Maintenance lock is NOT active! Activate the maintenance lock before re-deploying.\nPre-install checks have failed.\n");
        } else {
            $output .= "Maintenance mode is active\n";
        }
        $output .= "Pre-install checks have passed.\n";

        return $output;
    }

    public static function postInstall($kernelDir = ''): string
    {
        $output = self::runProcess(["{$kernelDir}bin/console", 'messenger:stop-workers']); // stop running messenger workers
        $output .= self::runProcess(["{$kernelDir}bin/console", 'doctrine:migrations:status']); // check migration status (don't automatically run migrations, as we sometimes seem to hit the old version)
        $output .= "Post-install script completed successfully.\n";
        return $output;
    }

    public static function denyAccessUnlessHmacPasses($hmac, $timestamp, $action): bool
    {
        $currentTime = time();
        if (
            abs($currentTime - $timestamp) <= self::HMAC_TIMEOUT
            && hash_equals(hash_hmac('sha256', "{$action}:{$timestamp}", "{$_ENV['APP_SECRET']}"), $hmac)
        ) {
            return true;
        }
        throw new AccessDeniedHttpException("HMAC: {$hmac}\nTime: {$currentTime}\nTimestamp: {$timestamp}\nAction: {$action}");
    }

    public static function runProcess($command, $addInformationalOutput = true): string
    {
        $output = "";
        $process = new Process($command);
        if ($addInformationalOutput) {
            $output .= "=== running command \"" . implode(" ", $command) . "\" ===\n";
        }
        $returnValue = $process->run(function ($type, $buffer) use (&$output) {
            $output .= $buffer;
        });
        if ($returnValue !== 0) {
            throw new HttpException(500, "HALT! There was a problem: {$returnValue}");
        }
        if ($addInformationalOutput) {
            $output .= "=== finished ===\n\n";
        }
        return $output;
    }

    public static function handleException(Throwable $originalException): void
    {
        if ($originalException instanceof HttpException) {
            header("HTTP/1.0 {$originalException->getStatusCode()}");
        } else {
            header("HTTP/1.0 500 Unhandled error");
        }
        echo $originalException->getMessage();
        exit;
    }
}