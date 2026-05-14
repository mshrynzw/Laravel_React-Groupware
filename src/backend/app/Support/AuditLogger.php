<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(Request $request, string $event, ?Model $target = null, array $payload = []): void
    {
        $requestId = $request->attributes->get('request_id');
        if (is_string($requestId) && $requestId !== '') {
            $payload = array_merge(['request_id' => $requestId], $payload);
        }

        AuditLog::create([
            'actor_user_id' => $request->user()?->id,
            'event' => $event,
            'auditable_type' => $target ? $target::class : null,
            'auditable_id' => $target?->getKey(),
            'payload' => $payload,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
