<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    /**
     * 相関 ID: クライアントの `X-Request-Id`（形式が妥当なときのみ採用）または UUID を付与し、
     * レスポンスヘッダと Request attributes に格納する（監査ログ等が参照可能）。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = $this->resolveRequestId($request);
        $request->attributes->set('request_id', $id);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);

        return $response;
    }

    private function resolveRequestId(Request $request): string
    {
        $header = $request->headers->get('X-Request-Id');
        if (is_string($header) && $header !== '') {
            $trimmed = trim($header);
            if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $trimmed) === 1) {
                return strtolower($trimmed);
            }
            if (preg_match('/^[a-zA-Z0-9._:-]{8,128}$/', $trimmed) === 1) {
                return $trimmed;
            }
        }

        return (string) Str::uuid();
    }
}
