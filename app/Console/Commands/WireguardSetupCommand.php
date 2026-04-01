<?php

namespace App\Console\Commands;

use App\Models\WireguardServer;
use App\Services\WireguardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

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
        {--public-key= : Pre-generated public key (skip key generation)}
        {--private-key= : Pre-generated private key (skip key generation)}
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

        // Use pre-generated keys or generate new ones
        $privateKey = $this->option('private-key');
        $publicKey = $this->option('public-key');

        if ($privateKey && $publicKey) {
            $this->info('Using provided key pair.');
            $this->info("Public Key: {$publicKey}");
        } else {
            $this->info('Generating WireGuard key pair...');
            $keyPair = $wireguardService->generateKeyPair();
            $privateKey = $keyPair['private_key'];
            $publicKey = $keyPair['public_key'];
            $this->info("Public Key: {$publicKey}");
        }

        // Output config for external setup (workflow will use this)
        if (! $this->option('db-only')) {
            $this->info("Private Key: {$privateKey}");
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
}
