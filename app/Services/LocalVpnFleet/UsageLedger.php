<?php

namespace App\Services\LocalVpnFleet;

use App\Support\LocalVpnFleet\FleetClock;
use Illuminate\Support\Facades\DB;

/**
 * Adds the deltas a node reports to the daily counters, and reads them back
 * for the daily caps.
 *
 * Writes are increment-upserts done in a fixed number of statements per
 * batch, whatever the row count: INSERT-or-ignore the missing rows at zero,
 * read their ids, then one UPDATE … SET bytes = bytes + CASE id … END. Only
 * integers (cast here) go into the CASE, and it runs the same on MySQL and
 * SQLite.
 */
final class UsageLedger
{
    public const BYTES_PER_MB = 1_048_576;

    private const USER_TABLE = 'vpn_user_usage_daily';

    private const NODE_TABLE = 'vpn_node_usage_daily';

    private const CHUNK = 500;

    public function todayBytes(string $machineId, string $kind): int
    {
        return (int) DB::table(self::USER_TABLE)
            ->where('date', FleetClock::today())
            ->where('kind', $kind)
            ->where('machine_id', $machineId)
            ->value('bytes');
    }

    /**
     * @param  list<string>  $machineIds
     * @return array<string, int> machine_id => bytes today (absent = 0)
     */
    public function todayBytesFor(array $machineIds, string $kind): array
    {
        $bytes = [];
        foreach (array_chunk(array_values(array_unique(array_map('strval', $machineIds))), self::CHUNK) as $chunk) {
            $rows = DB::table(self::USER_TABLE)
                ->where('date', FleetClock::today())
                ->where('kind', $kind)
                ->whereIn('machine_id', $chunk)
                ->pluck('bytes', 'machine_id');
            foreach ($rows as $machineId => $value) {
                $bytes[$machineId] = (int) $value;
            }
        }

        return $bytes;
    }

    /**
     * @param  array<string, array{bytes: int, license_key_id: ?int}>  $perMachine
     */
    public function addUserUsage(string $date, string $kind, array $perMachine): void
    {
        $perMachine = array_filter($perMachine, fn (array $entry) => $entry['bytes'] > 0);
        $now = now();

        foreach (array_chunk($perMachine, self::CHUNK, true) as $chunk) {
            $machineIds = array_map('strval', array_keys($chunk));

            DB::table(self::USER_TABLE)->insertOrIgnore(array_map(fn (string $machineId) => [
                'date' => $date,
                'kind' => $kind,
                'machine_id' => $machineId,
                'bytes' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ], $machineIds));

            $ids = DB::table(self::USER_TABLE)
                ->where('date', $date)
                ->where('kind', $kind)
                ->whereIn('machine_id', $machineIds)
                ->pluck('id', 'machine_id');

            if ($ids->isEmpty()) {
                continue;
            }

            $bytesCase = '';
            $licenseCase = '';
            foreach ($ids as $machineId => $id) {
                $entry = $chunk[$machineId];
                $bytesCase .= ' WHEN ' . (int) $id . ' THEN ' . (int) $entry['bytes'];
                if ($entry['license_key_id'] !== null) {
                    $licenseCase .= ' WHEN ' . (int) $id . ' THEN ' . (int) $entry['license_key_id'];
                }
            }

            $update = [
                'bytes' => DB::raw("bytes + CASE id{$bytesCase} ELSE 0 END"),
                'updated_at' => $now,
            ];
            if ($licenseCase !== '') {
                $update['license_key_id'] = DB::raw("CASE id{$licenseCase} ELSE license_key_id END");
            }

            DB::table(self::USER_TABLE)->whereIn('id', $ids->values()->all())->update($update);
        }
    }

    public function addNodeUsage(int $nodeId, string $date, int $nicRx, int $nicTx, int $relayBytes, int $exitBytes, int $clients): void
    {
        $now = now();

        DB::table(self::NODE_TABLE)->insertOrIgnore([
            'node_id' => $nodeId,
            'date' => $date,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $clients = max(0, $clients);

        DB::table(self::NODE_TABLE)
            ->where('node_id', $nodeId)
            ->where('date', $date)
            ->update([
                'nic_rx' => DB::raw('nic_rx + ' . max(0, $nicRx)),
                'nic_tx' => DB::raw('nic_tx + ' . max(0, $nicTx)),
                'relay_bytes' => DB::raw('relay_bytes + ' . max(0, $relayBytes)),
                'exit_bytes' => DB::raw('exit_bytes + ' . max(0, $exitBytes)),
                'peak_clients' => DB::raw("CASE WHEN peak_clients < {$clients} THEN {$clients} ELSE peak_clients END"),
                'updated_at' => $now,
            ]);
    }
}
