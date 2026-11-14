<?php

namespace App\Support\Auth;

/**
 * The apps allowed to "Sign in with XMAN ID".
 *
 * Each client has its own shared secret and its own exact-match redirect allowlist. A code is bound
 * to the client that asked for it, so mlm.xman4289.com cannot redeem a code minted for aixman and
 * the other way round, even though both trust the same user session here.
 *
 * aixman is the default — the client assumed when a request names none, which is every request the
 * X-DREAMER app and ai.xman4289.com have ever sent. Its settings stay at services.aixman.sso_* so
 * nothing about those logins changed. Every other client lives at services.xman_id.clients.<id>.
 */
final class XmanIdClients
{
    public const DEFAULT = 'aixman';

    /**
     * @return array{id:string,secret:string,redirect_uris:array<int,string>}|null null for an unknown id
     */
    public static function find(?string $id): ?array
    {
        $id = trim((string) $id);
        if ($id === '') {
            $id = self::DEFAULT;
        }

        // Ids are config keys: no dots, nothing that could walk into another part of config().
        if (! preg_match('/^[a-z0-9_-]{1,32}$/', $id)) {
            return null;
        }

        if ($id === self::DEFAULT) {
            return [
                'id' => $id,
                'secret' => (string) config('services.aixman.sso_secret'),
                'redirect_uris' => self::uris(config('services.aixman.sso_redirect_uris')),
            ];
        }

        $clients = config('services.xman_id.clients');
        $client = is_array($clients) ? ($clients[$id] ?? null) : null;
        if (! is_array($client)) {
            return null;
        }

        return [
            'id' => $id,
            'secret' => (string) ($client['secret'] ?? ''),
            'redirect_uris' => self::uris($client['redirect_uris'] ?? []),
        ];
    }

    /** @return array<int,string> every configured client secret, for Redact */
    public static function secrets(): array
    {
        $secrets = [(string) config('services.aixman.sso_secret')];
        foreach ((array) config('services.xman_id.clients', []) as $client) {
            if (is_array($client)) {
                $secrets[] = (string) ($client['secret'] ?? '');
            }
        }

        return array_values(array_filter($secrets, fn ($s) => $s !== ''));
    }

    /** @return array<int,string> */
    private static function uris(mixed $configured): array
    {
        return is_array($configured) ? array_values(array_filter($configured, fn ($u) => is_string($u) && $u !== '')) : [];
    }
}
