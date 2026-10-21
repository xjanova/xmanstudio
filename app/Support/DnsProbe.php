<?php

namespace App\Support;

/**
 * Ask one particular nameserver which nameservers it says a domain has.
 *
 * PHP's resolver only talks to the system's recursive resolver, which knows
 * the live delegation — not what Cloudflare has prepared for a domain that is
 * still pending there. Cloudflare answers a pending zone only on the pair it
 * assigned to it and refuses on every other one of its nameservers (checked
 * 2026-10-10), so asking the pair a customer pasted is the one way to know,
 * before the switch, that the site was added on Cloudflare and that the pair
 * is the right one. Pointing a domain at a pair that does not serve it takes
 * the customer's website and mail down.
 */
class DnsProbe
{
    public const OK = 'ok';

    /** The server does not serve this domain (REFUSED / NXDOMAIN / no NS). */
    public const NOT_SERVED = 'not_served';

    /** We could not ask — network, timeout, garbage. Decides nothing. */
    public const FAILED = 'failed';

    private const TYPE_NS = 2;

    public function __construct(private int $timeoutSeconds = 3) {}

    /**
     * @return array{status:string, nameservers:array<int,string>}
     */
    public function nameservers(string $domain, string $server): array
    {
        $ip = gethostbyname($server);

        // Every real Cloudflare nameserver resolves; one that does not is a
        // typo, which no amount of trying again will fix.
        if ($ip === $server || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['status' => self::NOT_SERVED, 'nameservers' => []];
        }

        $id = random_int(0, 0xFFFF);
        $socket = @stream_socket_client("udp://{$ip}:53", $errno, $errstr, $this->timeoutSeconds);

        if (! $socket) {
            return ['status' => self::FAILED, 'nameservers' => []];
        }

        stream_set_timeout($socket, $this->timeoutSeconds);

        try {
            fwrite($socket, self::query($id, $domain, self::TYPE_NS));
            $response = fread($socket, 4096);
        } finally {
            fclose($socket);
        }

        return self::parse((string) $response, $id);
    }

    /**
     * A plain DNS query packet. Recursion is not asked for: the server is
     * meant to answer from its own zones or not at all.
     */
    public static function query(int $id, string $domain, int $type): string
    {
        $packet = pack('nnnnnn', $id, 0x0000, 1, 0, 0, 0);

        foreach (explode('.', trim($domain, '.')) as $label) {
            $packet .= chr(strlen($label)) . $label;
        }

        return $packet . "\0" . pack('nn', $type, 1);
    }

    /**
     * @return array{status:string, nameservers:array<int,string>}
     */
    public static function parse(string $message, int $id): array
    {
        $failed = ['status' => self::FAILED, 'nameservers' => []];

        if (strlen($message) < 12) {
            return $failed;
        }

        $header = unpack('nid/nflags/nqd/nan', substr($message, 0, 8));

        // Not our answer, or not an answer at all.
        if ($header['id'] !== $id || ! ($header['flags'] & 0x8000)) {
            return $failed;
        }

        $rcode = $header['flags'] & 0x000F;

        if ($rcode === 3 || $rcode === 5) {
            return ['status' => self::NOT_SERVED, 'nameservers' => []];
        }

        if ($rcode !== 0) {
            return $failed;
        }

        try {
            $offset = 12;

            for ($i = 0; $i < $header['qd']; $i++) {
                self::readName($message, $offset);
                $offset += 4;
            }

            $found = [];

            for ($i = 0; $i < $header['an']; $i++) {
                self::readName($message, $offset);

                if ($offset + 10 > strlen($message)) {
                    return $failed;
                }

                $meta = unpack('ntype/nclass/Nttl/nlength', substr($message, $offset, 10));
                $offset += 10;
                $rdata = $offset;
                $offset += $meta['length'];

                if ($meta['type'] === self::TYPE_NS) {
                    $found[] = strtolower(self::readName($message, $rdata));
                }
            }
        } catch (\RuntimeException) {
            return $failed;
        }

        $found = array_values(array_unique($found));
        sort($found);

        // A server that answers without listing the zone's nameservers is not
        // serving it as the authority (a referral, or nothing at all).
        return $found === []
            ? ['status' => self::NOT_SERVED, 'nameservers' => []]
            : ['status' => self::OK, 'nameservers' => $found];
    }

    /**
     * A domain name at $offset, following compression pointers. Leaves
     * $offset just past the name as it appears at that spot.
     */
    private static function readName(string $message, int &$offset): string
    {
        $labels = [];
        $cursor = $offset;
        $jumped = false;
        $hops = 0;

        while (true) {
            if ($cursor >= strlen($message) || ++$hops > 128) {
                throw new \RuntimeException('malformed name');
            }

            $length = ord($message[$cursor]);

            if ($length === 0) {
                $cursor++;
                break;
            }

            if (($length & 0xC0) === 0xC0) {
                if ($cursor + 1 >= strlen($message)) {
                    throw new \RuntimeException('malformed pointer');
                }

                if (! $jumped) {
                    $offset = $cursor + 2;
                }

                $jumped = true;
                $cursor = (($length & 0x3F) << 8) | ord($message[$cursor + 1]);

                continue;
            }

            $labels[] = substr($message, $cursor + 1, $length);
            $cursor += $length + 1;
        }

        if (! $jumped) {
            $offset = $cursor;
        }

        return implode('.', $labels);
    }
}
