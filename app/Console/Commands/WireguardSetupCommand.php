<?php

namespace App\Console\Commands;

use App\Models\WireguardServer;
use App\Services\WireguardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Input\StreamableInputInterface;

class WireguardSetupCommand extends Command
{
    protected $signature = 'wireguard:setup
        {--name=wg-server-1 : Server display name}
        {--country=SG : Two-letter country code}
        {--country-name=Singapore : Country display name}
        {--port=51820 : WireGuard listen port}
        {--interface=wg0 : WireGuard interface name}
        {--address=10.20.0.1/24 : Server WireGuard address (CIDR)}
        {--dns=1.1.1.1, 8.8.8.8 : DNS servers for clients}
        {--max-clients=250 : Maximum number of clients}
        {--net-interface=eth0 : Network interface for NAT masquerade}
        {--public-key= : Pre-generated public key (needs --private-key-stdin)}
        {--private-key-stdin : Read the pre-generated private key from stdin (skip key generation)}
        {--skip-install : Skip WireGuard package installation}
        {--db-only : Only create database record, skip all system setup}';

    protected $description = 'Register a WireGuard VPN server in the database';

    public function handle(WireguardService $wireguardService): int
    {
        $name = $this->option('name');
        $countryCode = strtoupper($this->option('country'));
        $countryName = $this->option('country-name');
        $port = (int) $this->option('port');
        $interface = $this->option('interface');
        $address = $this->option('address');
        $dns = $this->option('dns');
        $maxClients = (int) $this->option('max-clients');

        // Check for duplicate server
        $existing = WireguardServer::where('name', $name)
            ->orWhere(function ($q) use ($address, $port) {
                $q->where('address', $address)->where('listen_port', $port);
            })
            ->first();

        if ($existing) {
            $this->warn("Server already exists: {$existing->name} (ID: {$existing->id})");
            $this->warn('Use a different name/address/port, or delete the existing record first.');

            return self::FAILURE;
        }

        $this->info("Setting up WireGuard server: {$name}");
        $this->info("Interface: {$interface}, Port: {$port}, Address: {$address}");

        // Use pre-generated keys or generate new ones. The private key comes in on
        // stdin because an option would put it on the command line, where ps shows it.
        if ($this->option('private-key-stdin')) {
            $publicKey = $this->option('public-key');
            $privateKey = $this->readPrivateKey();

            // Never fall back to generating a pair here: the caller's interface is
            // already running on this key, so a new pair would silently not match it.
            if (! $publicKey || ! $this->isWireguardKey($privateKey)) {
                $this->error('--private-key-stdin needs --public-key and one WireGuard private key on stdin.');

                return self::FAILURE;
            }

            $this->info('Using provided key pair.');
            $this->info("Public Key: {$publicKey}");
        } else {
            $this->info('Generating WireGuard key pair...');
            $keyPair = $wireguardService->generateKeyPair();
            $privateKey = $keyPair['private_key'];
            $publicKey = $keyPair['public_key'];
            $this->info("Public Key: {$publicKey}");

            // For someone at a terminal setting the server up by hand. The workflow
            // passes --db-only, because its output is a public Actions log.
            if (! $this->option('db-only')) {
                $this->info("Private Key: {$privateKey}");
            }
        }

        // Detect public IP for endpoint
        $this->info('Detecting public IP...');
        $ipResult = Process::timeout(10)->run('curl -s https://ifconfig.me');
        $publicIp = trim($ipResult->output());
        if (empty($publicIp) || ! filter_var($publicIp, FILTER_VALIDATE_IP)) {
            $ipResult = Process::timeout(10)->run('curl -s https://api.ipify.org');
            $publicIp = trim($ipResult->output());
        }

        if (empty($publicIp) || ! filter_var($publicIp, FILTER_VALIDATE_IP)) {
            $this->warn('Could not detect public IP. Using placeholder.');
            $publicIp = '0.0.0.0';
        }

        $endpoint = "{$publicIp}:{$port}";
        $this->info("Endpoint: {$endpoint}");

        // Create database record
        $this->info('Creating database record...');
        $server = WireguardServer::create([
            'name' => $name,
            'country_code' => $countryCode,
            'country_name' => $countryName,
            'endpoint' => $endpoint,
            'public_key' => $publicKey,
            'private_key' => $privateKey,
            'address' => $address,
            'dns' => $dns,
            'listen_port' => $port,
            'max_clients' => $maxClients,
            'is_active' => true,
            'is_healthy' => true,
            'last_health_check_at' => now(),
        ]);

        $this->info("Server created with ID: {$server->id}");

        $this->newLine();
        $this->info('WireGuard setup complete!');
        $this->table(
            ['Property', 'Value'],
            [
                ['Server Name', $name],
                ['Interface', $interface],
                ['Address', $address],
                ['Port', $port],
                ['Endpoint', $endpoint],
                ['Public Key', $publicKey],
                ['Country', "{$countryName} ({$countryCode})"],
                ['Max Clients', $maxClients],
                ['Database ID', $server->id],
            ]
        );

        return self::SUCCESS;
    }

    private function readPrivateKey(): string
    {
        $stream = $this->input instanceof StreamableInputInterface ? $this->input->getStream() : null;

        return trim((string) fgets($stream ?? STDIN));
    }

    private function isWireguardKey(string $key): bool
    {
        return strlen((string) base64_decode($key, true)) === 32;
    }
}
