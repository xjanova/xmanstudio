<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VpnNode;
use App\Services\LocalVpnFleet\NodeSync;
use App\Services\LocalVpnFleet\SyncReplayed;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * POST /api/v1/localvpn/node/sync — each relay-fleet node, every 10 s
 * (docs/RELAY_FLEET.md, "Node sync").
 *
 *   X-Node-Id:        the node's numeric id
 *   X-Node-Time:      unix seconds
 *   X-Node-Signature: hex HMAC-SHA256(node_secret, "<time>.<raw body>")
 *
 * Refused: a bad signature (401 unauthorized), a time more than 300 s off
 * (401 stale-time, with server_time so the node can see its clock is wrong),
 * a time not later than the last accepted one (409 replayed).
 */
class LocalVpnNodeController extends Controller
{
    public const MAX_SKEW_SECONDS = 300;

    private const MAX_BODY_BYTES = 2_000_000;

    public function sync(Request $request, NodeSync $sync): JsonResponse
    {
        $raw = $request->getContent();
        if (strlen($raw) > self::MAX_BODY_BYTES) {
            return $this->refuse('too-large', 413);
        }

        $nodeId = (string) $request->header('X-Node-Id', '');
        $time = (string) $request->header('X-Node-Time', '');
        $signature = strtolower((string) $request->header('X-Node-Signature', ''));

        if (! preg_match('/^\d{1,10}$/', $nodeId)
            || ! preg_match('/^\d{1,12}$/', $time)
            || ! preg_match('/^[0-9a-f]{64}$/', $signature)) {
            return $this->refuse('unauthorized', 401);
        }

        $node = VpnNode::find((int) $nodeId);
        $secret = null;
        if ($node !== null) {
            try {
                $secret = $node->secret;
            } catch (DecryptException) {
                $secret = null;
            }
        }

        // Same work whether or not the node exists.
        $expected = hash_hmac('sha256', $time . '.' . $raw, is_string($secret) && $secret !== '' ? $secret : random_bytes(32));
        if ($node === null || ! is_string($secret) || $secret === '' || ! hash_equals($expected, $signature)) {
            return $this->refuse('unauthorized', 401);
        }

        $now = time();
        $sentAt = (int) $time;
        if (abs($sentAt - $now) > self::MAX_SKEW_SECONDS) {
            return response()->json(['ok' => false, 'error' => 'stale-time', 'server_time' => $now], 401);
        }
        if ($sentAt <= (int) $node->last_sync_time) {
            return $this->refuse('replayed', 409);
        }

        $body = json_decode($raw, true, 32);
        if (! is_array($body)) {
            return $this->refuse('bad-body', 422);
        }

        // The shape only; usage rows are sanitised one by one in NodeSync so
        // one odd row cannot throw away a whole sync's deltas.
        $validator = Validator::make($body, [
            'agent' => ['nullable', 'string', 'max:255'],
            'roles' => ['nullable', 'array', 'max:8'],
            'wg_public_key' => ['nullable', 'string', 'max:64'],
            'wg_port' => ['nullable', 'integer', 'between:1,65535'],
            'relay_port' => ['nullable', 'integer', 'between:1,65535'],
            'nic' => ['nullable', 'array'],
            'relay' => ['nullable', 'array'],
            'relay.usage' => ['nullable', 'array', 'max:' . NodeSync::MAX_USAGE_ROWS],
            'exit' => ['nullable', 'array'],
            'exit.usage' => ['nullable', 'array', 'max:' . NodeSync::MAX_USAGE_ROWS],
            'load1' => ['nullable', 'numeric'],
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'error' => 'bad-body', 'fields' => $validator->errors()->keys()], 422);
        }

        try {
            return response()->json($sync->apply($node, $sentAt, $body));
        } catch (SyncReplayed) {
            return $this->refuse('replayed', 409);
        }
    }

    private function refuse(string $error, int $status): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $error], $status);
    }
}
