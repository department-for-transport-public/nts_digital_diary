<?php

namespace App\Utility\AlphagovNotify;

use Alphagov\Notifications\Client as AlphagovClient;
use Http\Adapter\Guzzle6\Client as GuzzleClient;

class ClientFactory
{
    public function __invoke($apiKey)
    {
        return new AlphagovClient([
            'apiKey' => $apiKey,
            'httpClient' => new GuzzleClient,
        ]);
    }
}
