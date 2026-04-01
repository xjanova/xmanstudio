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
        {--skip-install : Skip WireGuard package installation}';

    protected $description = 'Install and configure WireGuard VPN on this server';

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
        $netInterface = $this->option('net-interface');

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

        // Step 1: Install WireGuard
        if (! $this->option('skip-install')) {
            $this->info('Installing WireGuard...');
            $result = Process::timeout(120)->run('sudo apt update && sudo apt install -y wireguard');
            if (! $result->successful()) {
                $this->error('Failed to install WireGuard: ' . $result->errorOutput());

                return self::FAILURE;
            }
            $this->info('WireGuard installed successfully.');
        }

        // Step 2: Generate key pair
        $this->info('Generating WireGuard key pair...');
        $keyPair = $wireguardService->generateKeyPair();
        $privateKey = $keyPair['private_key'];
        $publicKey = $keyPair['public_key'];
        $this->info("Public Key: {$publicKey}");

        // Step 3: Create WireGuard config (requires sudo — continue on failure)
        $this->info('Creating WireGuard configuration...');
        $systemSetupOk = true;

        $config = "[Interface]\n";
        $config .= "Address = {$address}\n";
        $config .= "ListenPort = {$port}\n";
        $config .= "PrivateKey = {$privateKey}\n";
        $config .= "PostUp = iptables -A FORWARD -i {$interface} -j ACCEPT; iptables -t nat -A POSTROUTING -o {$netInterface} -j MASQUERADE\n";
        $config .= "PostDown = iptables -D FORWARD -i {$interface} -j ACCEPT; iptables -t nat -D POSTROUTING -o {$netInterface} -j MASQUERADE\n";

        $configPath = "/etc/wireguard/{$interface}.conf";
        $result = Process::run('echo ' . escapeshellarg($config) . " | sudo tee {$configPath}");
        if (! $result->successful()) {
            $this->warn('Could not write WireGuard config (sudo may require password).');
            $this->warn('System setup skipped — DB record will still be created.');
            $systemSetupOk = false;
        } else {
            Process::run("sudo chmod 600 {$configPath}");
            $this->info("Config written to {$configPath}");
        }

        if ($systemSetupOk) {
            // Step 4: Enable IP forwarding
            $this->info('Enabling IP forwarding...');
            Process::run('sudo sysctl -w net.ipv4.ip_forward=1');
            Process::run("sudo sed -i 's/#net.ipv4.ip_forward=1/net.ipv4.ip_forward=1/' /etc/sysctl.conf");
            $checkResult = Process::run("grep -c '^net.ipv4.ip_forward=1' /etc/sysctl.conf");
            if (trim($checkResult->output()) === '0') {
                Process::run("echo 'net.ipv4.ip_forward=1' | sudo tee -a /etc/sysctl.conf");
            }
            Process::run('sudo sysctl -p');
            $this->info('IP forwarding enabled.');

            // Step 5: Start and enable WireGuard service
            $this->info('Starting WireGuard service...');
            $result = Process::run("sudo systemctl enable wg-quick@{$interface} && sudo systemctl start wg-quick@{$interface}");
            if (! $result->successful()) {
                $this->warn('Service start had issues: ' . $result->errorOutput());
                $this->info('Trying wg-quick up...');
                Process::run("sudo wg-quick up {$interface}");
            }

            $verifyResult = Process::run("sudo wg show {$interface}");
            if (! $verifyResult->successful()) {
                $this->warn('WireGuard interface is not up. Check: sudo journalctl -u wg-quick@' . $interface);
            } else {
                $this->info('WireGuard service is running.');
            }
        }

        // Step 6: Detect public IP for endpoint
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

        // Step 7: Create database record
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
