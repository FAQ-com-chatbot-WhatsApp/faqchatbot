<?php
declare(strict_types=1);

namespace App\Api\Controllers;

class HealthController
{
    public function ping(): array
    {
        return [
            'status' => 'ok',
            'time' => date('c'),
            'app' => $_ENV['APP_NAME'] ?? 'WhatsBot'
        ];
    }
}
