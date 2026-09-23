<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Low-level client for the upstream registrar's API.
 *
 * This class knows about HTTP and nothing about our business. It does not
 * price anything, does not touch wallets and does not decide what to show a
 * customer — see DomainRegistrarService for that. Keeping the split means the
 * day we buy from somewhere else, only this file and one service are rewritten.
 *
 * Two things it does take responsibility for:
 *
 * The rate limit is 90 requests per minute for the whole account, shared
 * across every machine using the token. Blowing through it returns 429 and,
 * repeated, gets the server's IP temporarily blocked — which would take the
 * whole site's domain features down, not just one request. So the limiter
 * here is deliberately stricter than the documented ceiling and refuses
 * locally rather than finding out from upstream.
 *
 * Nothing it logs may contain the token, and nothing it returns to a caller
 * is safe to show a customer verbatim: upstream error strings name the
 * registrar. Callers translate; they never pass the body through.
 */
class HostingerApiService
{
    protected string $baseUrl = 'https://developers.hostinger.com';

    /**
     * Ours, not theirs. Their ceiling is 90/min; we stop at 70 so that a burst
     * from a background job cannot starve a customer who is mid-checkout.
     */
    protected const RATE_LIMIT_PER_MINUTE = 70;

    protected const RATE_LIMIT_KEY = 'hostinger-api';

    /**
     * Why the last call came back null, when it did.
     *
     * Most callers only need "it failed". The purchase paths need more: a
     * request that never left (no token, our own limiter, upstream's 429) bought
     * nothing and can be refunded on the spot, but one that timed out in transit
     * may have gone through upstream with our card — refunding that one hands
     * the customer the product and their money back. 'transport' (the request
     * left and no answer came) and 'server_error' (upstream fell over, maybe
     * after taking the order) mean "we do not know"; 'unreachable' (DNS or
     * connection refused) never got as far as upstream.
     *
     * @var 'unconfigured'|'rate_limited'|'throttled_upstream'|'unreachable'|'transport'|'server_error'|'http'|null
     */
    protected ?string $lastFailure = null;

    /** HTTP status of the last response, or null when no response came back. */
    protected ?int $lastStatus = null;

    protected string $apiToken;

    public function __construct()
    {
        $this->apiToken = (string) Setting::getValue('hostinger_api_token', '');
    }

    public function isConfigured(): bool
    {
        return $this->apiToken !== '';
    }

    /**
     * True when the last call may have reached upstream without us hearing
     * the answer — the one failure a purchase must not refund blindly.
     */
    public function lastOutcomeUnknown(): bool
    {
        return in_array($this->lastFailure, ['transport', 'server_error'], true);
    }

    public function lastFailure(): ?string
    {
        return $this->lastFailure;
    }

    /**
     * Did the last lookup answer "there is no such thing" — as opposed to not
     * answering at all?
     *
     * The difference decides refunds. A 404 is upstream saying the domain or
     * machine does not exist; a timeout, our own rate limiter or a 5xx says
     * nothing about it, and refunding on that is refunding something that may
     * well have been bought.
     */
    public function lastWasNotFound(): bool
    {
        return $this->lastStatus === 404;
    }

    // ---------------------------------------------------------------- domains

    /**
     * Which of these TLDs is the name still free on?
     *
     * @param  array<int,string>  $tlds  without leading dots
     * @return array<int,array<string,mixed>>|null null on failure
     */
    public function checkAvailability(string $name, array $tlds, bool $withAlternatives = false): ?array
    {
        return $this->post('/api/domains/v1/availability', [
            'domain' => $name,
            'tlds' => array_values($tlds),
            'with_alternatives' => $withAlternatives,
        ]);
    }

    /**
     * Names suggested from a plain-language description of the business.
     *
     * @return array<int,array<string,mixed>>|null
     */
    public function suggestFromDescription(string $description, array $tlds = ['com']): ?array
    {
        return $this->post('/api/domains/v1/availability/alternatives-from-description', [
            'description' => $description,
            'tlds' => array_values($tlds),
        ]);
    }

    /**
     * Names in the neighbourhood of one the customer already likes.
     *
     * @return array<int,array<string,mixed>>|null
     */
    public function suggestFromDomain(string $domain, array $tlds = ['com']): ?array
    {
        return $this->post('/api/domains/v1/availability/alternatives-from-domain', [
            'domain' => $domain,
            'tlds' => array_values($tlds),
        ]);
    }

    /**
     * Register a domain. The registrant is whoever owns $whoisId.
     *
     * A 202 here means the payment is still clearing and the domain is NOT
     * registered yet — the caller must poll, and must not resend.
     *
     * @param  array<string,mixed>  $additionalDetails  TLD-specific registrant data
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function purchaseDomain(
        string $domain,
        string $itemId,
        int $whoisId,
        array $additionalDetails = [],
        ?int $paymentMethodId = null,
    ): ?array {
        $payload = [
            'domain' => $domain,
            'item_id' => $itemId,
            'domain_contacts' => [
                'owner_id' => $whoisId,
                'admin_id' => $whoisId,
                'billing_id' => $whoisId,
                'tech_id' => $whoisId,
            ],
        ];

        if ($additionalDetails !== []) {
            $payload['additional_details'] = $additionalDetails;
        }

        if ($paymentMethodId) {
            $payload['payment_method_id'] = $paymentMethodId;
        }

        return $this->request('post', '/api/domains/v1/portfolio', $payload, withStatus: true);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getDomain(string $domain): ?array
    {
        return $this->get("/api/domains/v1/portfolio/{$domain}");
    }

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function listDomains(): ?array
    {
        return $this->get('/api/domains/v1/portfolio');
    }

    /**
     * @param  array<int,string>  $nameservers
     */
    public function updateNameservers(string $domain, array $nameservers): bool
    {
        $payload = [];
        foreach (array_values($nameservers) as $i => $ns) {
            $payload['ns' . ($i + 1)] = $ns;
        }

        return $this->put("/api/domains/v1/portfolio/{$domain}/nameservers", $payload) !== null;
    }

    public function enablePrivacyProtection(string $domain): bool
    {
        return $this->put("/api/domains/v1/portfolio/{$domain}/privacy-protection", []) !== null;
    }

    public function disablePrivacyProtection(string $domain): bool
    {
        return $this->delete("/api/domains/v1/portfolio/{$domain}/privacy-protection") !== null;
    }

    /**
     * The code the customer needs to move the domain to another registrar.
     * We hand it over on request, always — see DomainContact's migration.
     */
    public function getAuthCode(string $domain): ?string
    {
        $result = $this->get("/api/domains/v1/portfolio/{$domain}/auth-code");

        return $result['auth_code'] ?? $result['code'] ?? null;
    }

    public function enableDomainLock(string $domain): bool
    {
        return $this->put("/api/domains/v1/portfolio/{$domain}/domain-lock", []) !== null;
    }

    public function disableDomainLock(string $domain): bool
    {
        return $this->delete("/api/domains/v1/portfolio/{$domain}/domain-lock") !== null;
    }

    /**
     * Register a domain that is paid for but not registered.
     *
     * This is what a 202 from purchaseDomain() leaves behind: once upstream's
     * payment clears, the domain lands in the portfolio as `pending_setup` and
     * stays there — paid for with our card, registered to nobody — until this
     * is called. No new order is placed and nothing is charged.
     *
     * @param  array<string,mixed>  $additionalDetails
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function completeDomainSetup(string $domain, int $whoisId, array $additionalDetails = []): ?array
    {
        $payload = [
            'domain_contacts' => [
                'owner_id' => $whoisId,
                'admin_id' => $whoisId,
                'billing_id' => $whoisId,
                'tech_id' => $whoisId,
            ],
        ];

        if ($additionalDetails !== []) {
            $payload['additional_details'] = $additionalDetails;
        }

        return $this->request('post', "/api/domains/v1/portfolio/{$domain}/setup", $payload, withStatus: true);
    }

    /**
     * Where the domain redirects to, if anywhere.
     *
     * @return array<string,mixed>|null null when there is no forwarding (or the call failed)
     */
    public function getForwarding(string $domain): ?array
    {
        return $this->get("/api/domains/v1/forwarding/{$domain}");
    }

    /**
     * Point the whole domain at another URL. Creates the forwarding when there
     * is none and replaces it when there is — the caller does not have to know
     * which, and asking first would cost a call from a 90-a-minute budget.
     *
     * @param  '301'|'302'  $type
     */
    public function saveForwarding(string $domain, string $url, string $type): bool
    {
        $body = ['redirect_type' => $type, 'redirect_url' => $url];

        $updated = $this->request('put', "/api/domains/v1/forwarding/{$domain}", $body, withStatus: true);

        if ($updated !== null && $updated['status_code'] < 300) {
            return true;
        }

        return $this->post('/api/domains/v1/forwarding', ['domain' => $domain] + $body) !== null;
    }

    public function deleteForwarding(string $domain): bool
    {
        return $this->delete("/api/domains/v1/forwarding/{$domain}") !== null;
    }

    // ----------------------------------------------------------------- whois

    /**
     * Mirror a registrant upstream and get back the handle used at purchase.
     *
     * @param  array<string,mixed>  $whoisDetails
     * @return int|null the remote profile id
     */
    public function createWhoisProfile(string $tld, string $country, array $whoisDetails, array $tldDetails = []): ?int
    {
        $payload = [
            'tld' => $tld,
            'country' => strtoupper($country),
            'entity_type' => 'individual',
            'whois_details' => $whoisDetails,
        ];

        if ($tldDetails !== []) {
            $payload['tld_details'] = $tldDetails;
        }

        $result = $this->post('/api/domains/v1/whois', $payload);

        $id = $result['id'] ?? null;

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function listWhoisProfiles(): ?array
    {
        return $this->get('/api/domains/v1/whois');
    }

    public function deleteWhoisProfile(int $whoisId): bool
    {
        return $this->delete("/api/domains/v1/whois/{$whoisId}") !== null;
    }

    // --------------------------------------------------------------- billing

    /**
     * The upstream price list. Prices come back in cents.
     *
     * @return array<int,array<string,mixed>>|null
     */
    public function getCatalog(?string $category = null): ?array
    {
        $query = $category ? ['category' => $category] : [];

        return $this->get('/api/billing/v1/catalog', $query);
    }

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function getPaymentMethods(): ?array
    {
        return $this->get('/api/billing/v1/payment-methods');
    }

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function getSubscriptions(): ?array
    {
        return $this->get('/api/billing/v1/subscriptions');
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function renewSubscription(string $subscriptionId, ?int $paymentMethodId = null): ?array
    {
        $payload = $paymentMethodId ? ['payment_method_id' => $paymentMethodId] : [];

        return $this->request('post', "/api/billing/v1/subscriptions/{$subscriptionId}/renew", $payload, withStatus: true);
    }

    public function setAutoRenewal(string $subscriptionId, bool $enabled): bool
    {
        return $enabled
            ? $this->request('patch', "/api/billing/v1/subscriptions/{$subscriptionId}/auto-renewal/enable", []) !== null
            : $this->delete("/api/billing/v1/subscriptions/{$subscriptionId}/auto-renewal/disable") !== null;
    }

    // ------------------------------------------------------------------- dns

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function getDnsRecords(string $domain): ?array
    {
        return $this->get("/api/dns/v1/zones/{$domain}");
    }

    /**
     * @param  array<int,array<string,mixed>>  $zone
     */
    public function updateDnsRecords(string $domain, array $zone, bool $overwrite = true): bool
    {
        return $this->put("/api/dns/v1/zones/{$domain}", [
            'overwrite' => $overwrite,
            'zone' => array_values($zone),
        ]) !== null;
    }

    /**
     * Dry-run a zone before writing it. Cheap insurance against handing a
     * customer a broken mail setup because one MX row had a typo.
     *
     * @param  array<int,array<string,mixed>>  $zone
     * @return array{ok:bool,errors:array<int,mixed>}
     */
    public function validateDnsRecords(string $domain, array $zone): array
    {
        $result = $this->request('post', "/api/dns/v1/zones/{$domain}/validate", [
            'zone' => array_values($zone),
        ], withStatus: true);

        if ($result === null) {
            return ['ok' => false, 'errors' => []];
        }

        return [
            'ok' => $result['status_code'] >= 200 && $result['status_code'] < 300,
            'errors' => $result['body']['errors'] ?? $result['body'] ?? [],
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $filters  [{name, type}]
     */
    public function deleteDnsRecords(string $domain, array $filters): bool
    {
        return $this->request('delete', "/api/dns/v1/zones/{$domain}", [
            'filters' => array_values($filters),
        ]) !== null;
    }

    public function resetDnsZone(string $domain): bool
    {
        return $this->request('post', "/api/dns/v1/zones/{$domain}/reset", []) !== null;
    }

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function getDnsSnapshots(string $domain): ?array
    {
        return $this->get("/api/dns/v1/snapshots/{$domain}");
    }

    public function restoreDnsSnapshot(string $domain, string $snapshotId): bool
    {
        return $this->request('post', "/api/dns/v1/snapshots/{$domain}/{$snapshotId}/restore", []) !== null;
    }

    // ------------------------------------------------------------------- vps

    /**
     * Every virtual machine on the account — ours and the ones we sold.
     *
     * @return array<int,array<string,mixed>>|null
     */
    public function listVirtualMachines(): ?array
    {
        return $this->get('/api/vps/v1/virtual-machines');
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getVirtualMachine(int $vmId): ?array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vmId}");
    }

    /**
     * Buy a VPS and install it in one call.
     *
     * 200 carries both the order and the machine. 202 means upstream's payment
     * is still clearing and the machine was NOT set up: it appears later in
     * `initial` state and has to be set up with setupVirtualMachine(). Never
     * resend on a 202 — the order already exists.
     *
     * @param  array<string,mixed>  $setup  template_id, data_center_id, hostname, password, public_key…
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function purchaseVirtualMachine(string $itemId, array $setup, ?int $paymentMethodId = null): ?array
    {
        $payload = ['item_id' => $itemId, 'setup' => $setup];

        if ($paymentMethodId) {
            $payload['payment_method_id'] = $paymentMethodId;
        }

        return $this->request('post', '/api/vps/v1/virtual-machines', $payload, withStatus: true);
    }

    /**
     * Install a machine that was paid for but never set up (state `initial`).
     *
     * @param  array<string,mixed>  $setup
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function setupVirtualMachine(int $vmId, array $setup): ?array
    {
        return $this->request('post', "/api/vps/v1/virtual-machines/{$vmId}/setup", $setup, withStatus: true);
    }

    /**
     * start, stop or restart. Stopping does not stop billing — nothing here does.
     *
     * @param  'start'|'stop'|'restart'  $action
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function powerVirtualMachine(int $vmId, string $action): ?array
    {
        return $this->request('post', "/api/vps/v1/virtual-machines/{$vmId}/{$action}", [], withStatus: true);
    }

    /**
     * Reinstall the operating system. Destroys everything on the disk and any
     * snapshot with it.
     *
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function recreateVirtualMachine(int $vmId, int $templateId, ?string $password = null): ?array
    {
        $payload = ['template_id' => $templateId];

        if ($password !== null && $password !== '') {
            $payload['password'] = $password;
        }

        return $this->request('post', "/api/vps/v1/virtual-machines/{$vmId}/recreate", $payload, withStatus: true);
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function setVirtualMachineHostname(int $vmId, string $hostname): ?array
    {
        return $this->request('put', "/api/vps/v1/virtual-machines/{$vmId}/hostname", ['hostname' => $hostname], withStatus: true);
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function setVirtualMachineRootPassword(int $vmId, string $password): ?array
    {
        return $this->request('put', "/api/vps/v1/virtual-machines/{$vmId}/root-password", ['password' => $password], withStatus: true);
    }

    /**
     * Reverse DNS for one of the machine's addresses.
     *
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function setVirtualMachinePtr(int $vmId, int $ipAddressId, string $domain): ?array
    {
        return $this->request('post', "/api/vps/v1/virtual-machines/{$vmId}/ptr/{$ipAddressId}", ['domain' => $domain], withStatus: true);
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function deleteVirtualMachinePtr(int $vmId, int $ipAddressId): ?array
    {
        return $this->request('delete', "/api/vps/v1/virtual-machines/{$vmId}/ptr/{$ipAddressId}", [], withStatus: true);
    }

    /**
     * The resolvers the machine itself uses to look names up (not the
     * nameservers of any domain).
     *
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function setVirtualMachineNameservers(int $vmId, string $ns1, ?string $ns2 = null): ?array
    {
        return $this->request('put', "/api/vps/v1/virtual-machines/{$vmId}/nameservers", array_filter([
            'ns1' => $ns1,
            'ns2' => $ns2,
        ]), withStatus: true);
    }

    /**
     * Password of the control panel a panel template installs. Ignored
     * upstream when the machine runs a plain OS.
     *
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function setVirtualMachinePanelPassword(int $vmId, string $password): ?array
    {
        return $this->request('put', "/api/vps/v1/virtual-machines/{$vmId}/panel-password", ['password' => $password], withStatus: true);
    }

    /**
     * Boot the rescue image; the machine's own disk is mounted at /mnt and the
     * given password is root's for the rescue system only.
     *
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function startVirtualMachineRecovery(int $vmId, string $rootPassword): ?array
    {
        return $this->request('post', "/api/vps/v1/virtual-machines/{$vmId}/recovery", ['root_password' => $rootPassword], withStatus: true);
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function stopVirtualMachineRecovery(int $vmId): ?array
    {
        return $this->request('delete', "/api/vps/v1/virtual-machines/{$vmId}/recovery", [], withStatus: true);
    }

    /**
     * What has been done to the machine, newest first — one page.
     *
     * @return array<int,array<string,mixed>>|null
     */
    public function getVirtualMachineActions(int $vmId, int $page = 1): ?array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vmId}/actions", ['page' => max(1, $page)]);
    }

    /**
     * SSH keys installed on this machine. Per machine, so it never lists keys
     * of other customers' servers in the same account.
     *
     * @return array<int,array<string,mixed>>|null
     */
    public function getVirtualMachinePublicKeys(int $vmId): ?array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vmId}/public-keys");
    }

    /**
     * Store a public key on the ACCOUNT. On its own it reaches no machine —
     * attachPublicKeys() puts it on one.
     *
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function createPublicKey(string $name, string $key): ?array
    {
        return $this->request('post', '/api/vps/v1/public-keys', ['name' => $name, 'key' => $key], withStatus: true);
    }

    /**
     * @param  array<int,int>  $keyIds
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function attachPublicKeys(int $vmId, array $keyIds): ?array
    {
        return $this->request('post', "/api/vps/v1/public-keys/attach/{$vmId}", ['ids' => array_values(array_map('intval', $keyIds))], withStatus: true);
    }

    /**
     * Firewalls live on the account, not on a machine: one is created per
     * rented server and activated on it. Only one can be active per machine.
     *
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function createFirewall(string $name): ?array
    {
        return $this->request('post', '/api/vps/v1/firewall', ['name' => $name], withStatus: true);
    }

    /**
     * One page of the account's firewalls (every customer's and the owner's).
     *
     * @return array<int,array<string,mixed>>|null
     */
    public function listFirewalls(int $page = 1): ?array
    {
        return $this->get('/api/vps/v1/firewall', ['page' => max(1, $page)]);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getFirewall(int $firewallId): ?array
    {
        return $this->get("/api/vps/v1/firewall/{$firewallId}");
    }

    /**
     * Replace every rule at once, and push them to the machines using it.
     * Everything not accepted by a rule is dropped.
     *
     * @param  array<int,array{protocol:string,port:string,source:string,source_detail:string}>  $rules
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function replaceFirewallRules(int $firewallId, array $rules, bool $sync = true): ?array
    {
        return $this->request('put', "/api/vps/v1/firewall/{$firewallId}/rules", [
            'rules' => array_values($rules),
            'sync' => $sync,
        ], withStatus: true);
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function activateFirewall(int $firewallId, int $vmId): ?array
    {
        return $this->request('post', "/api/vps/v1/firewall/{$firewallId}/activate/{$vmId}", [], withStatus: true);
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function deactivateFirewall(int $firewallId, int $vmId): ?array
    {
        return $this->request('post', "/api/vps/v1/firewall/{$firewallId}/deactivate/{$vmId}", [], withStatus: true);
    }

    /**
     * Last malware scan on the machine, or null when the scanner is not
     * installed (or upstream would not say).
     *
     * @return array<string,mixed>|null
     */
    public function getMalwareScan(int $vmId): ?array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vmId}/monarx");
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function installMalwareScanner(int $vmId): ?array
    {
        return $this->request('post', "/api/vps/v1/virtual-machines/{$vmId}/monarx", [], withStatus: true);
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function uninstallMalwareScanner(int $vmId): ?array
    {
        return $this->request('delete', "/api/vps/v1/virtual-machines/{$vmId}/monarx", [], withStatus: true);
    }

    /**
     * CPU, RAM, disk, traffic and uptime between two moments — each series a
     * map of unix timestamp => value.
     *
     * @return array<string,mixed>|null
     */
    public function getVirtualMachineMetrics(int $vmId, \DateTimeInterface $from, \DateTimeInterface $to): ?array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vmId}/metrics", [
            'date_from' => $from->format('Y-m-d\TH:i:s\Z'),
            'date_to' => $to->format('Y-m-d\TH:i:s\Z'),
        ]);
    }

    /**
     * The single snapshot a machine can hold, or null when it has none.
     *
     * @return array<string,mixed>|null
     */
    public function getVirtualMachineSnapshot(int $vmId): ?array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vmId}/snapshot");
    }

    /**
     * Take a snapshot. There is only ever one: this overwrites the last.
     *
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function createVirtualMachineSnapshot(int $vmId): ?array
    {
        return $this->request('post', "/api/vps/v1/virtual-machines/{$vmId}/snapshot", [], withStatus: true);
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function restoreVirtualMachineSnapshot(int $vmId): ?array
    {
        return $this->request('post', "/api/vps/v1/virtual-machines/{$vmId}/snapshot/restore", [], withStatus: true);
    }

    /**
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function deleteVirtualMachineSnapshot(int $vmId): ?array
    {
        return $this->request('delete', "/api/vps/v1/virtual-machines/{$vmId}/snapshot", [], withStatus: true);
    }

    /**
     * The weekly automatic backups.
     *
     * @return array<int,array<string,mixed>>|null
     */
    public function getVirtualMachineBackups(int $vmId): ?array
    {
        return $this->get("/api/vps/v1/virtual-machines/{$vmId}/backups");
    }

    /**
     * Overwrite the whole disk with a backup.
     *
     * @return array{status_code:int,body:array<string,mixed>}|null
     */
    public function restoreVirtualMachineBackup(int $vmId, int $backupId): ?array
    {
        return $this->request('post', "/api/vps/v1/virtual-machines/{$vmId}/backups/{$backupId}/restore", [], withStatus: true);
    }

    /**
     * Operating systems and one-click apps a machine can be installed with.
     *
     * @return array<int,array<string,mixed>>|null
     */
    public function getVpsTemplates(): ?array
    {
        return $this->get('/api/vps/v1/templates');
    }

    /**
     * @return array<int,array<string,mixed>>|null
     */
    public function getVpsDataCenters(): ?array
    {
        return $this->get('/api/vps/v1/data-centers');
    }

    // --------------------------------------------------------------- plumbing

    /**
     * @param  array<string,mixed>  $query
     * @return array<mixed>|null
     */
    protected function get(string $path, array $query = []): ?array
    {
        return $this->request('get', $path, $query);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<mixed>|null
     */
    protected function post(string $path, array $payload): ?array
    {
        return $this->request('post', $path, $payload);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<mixed>|null
     */
    protected function put(string $path, array $payload): ?array
    {
        return $this->request('put', $path, $payload);
    }

    /**
     * @return array<mixed>|null
     */
    protected function delete(string $path): ?array
    {
        return $this->request('delete', $path, []);
    }

    /**
     * The single exit point to the network.
     *
     * Returns null for every failure mode — unconfigured, rate-limited,
     * non-2xx, thrown. Callers that need to tell 202 from 200 (the purchase
     * paths, where the difference is "registered" versus "paid for but not
     * registered") pass withStatus and get the code back with the body.
     *
     * @param  array<string,mixed>  $payload
     * @return array<mixed>|null
     */
    protected function request(string $method, string $path, array $payload = [], bool $withStatus = false): ?array
    {
        $this->lastFailure = null;
        $this->lastStatus = null;

        if (! $this->isConfigured()) {
            Log::warning('[HostingerApi] no API token configured', ['path' => $path]);
            $this->lastFailure = 'unconfigured';

            return null;
        }

        if (RateLimiter::tooManyAttempts(self::RATE_LIMIT_KEY, self::RATE_LIMIT_PER_MINUTE)) {
            Log::warning('[HostingerApi] local rate limit hit, refusing to call', [
                'path' => $path,
                'available_in' => RateLimiter::availableIn(self::RATE_LIMIT_KEY),
            ]);
            $this->lastFailure = 'rate_limited';

            return null;
        }

        RateLimiter::hit(self::RATE_LIMIT_KEY, 60);

        $isRead = strtolower($method) === 'get';

        try {
            $response = $this->client(retry: $isRead)->send(strtoupper($method), $this->baseUrl . $path, [
                $isRead ? 'query' : 'json' => $payload,
            ]);
        } catch (\Throwable $e) {
            // The message can contain the URL but never the Authorization
            // header, so this is safe to log as-is.
            Log::error('[HostingerApi] transport error', [
                'path' => $path,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            $this->lastFailure = $this->neverReachedServer($e) ? 'unreachable' : 'transport';

            return null;
        }

        $this->lastStatus = $response->status();

        if ($response->status() === 429) {
            Log::warning('[HostingerApi] upstream 429', [
                'path' => $path,
                'retry_after' => $response->header('Retry-After'),
            ]);

            // Burn the local budget so the next caller backs off too, rather
            // than each of them discovering the block separately.
            RateLimiter::hit(self::RATE_LIMIT_KEY, 60, self::RATE_LIMIT_PER_MINUTE);
            $this->lastFailure = 'throttled_upstream';

            return null;
        }

        if (! $response->successful()) {
            Log::error('[HostingerApi] request failed', [
                'path' => $path,
                'method' => $method,
                'status' => $response->status(),
                'body' => $this->loggableBody($path, $response->body()),
            ]);
            // A 5xx is upstream falling over mid-request, which can be after
            // it placed the order: as unknown as a timeout, not a refusal.
            $this->lastFailure = $response->serverError() ? 'server_error' : 'http';

            return $withStatus
                ? ['status_code' => $response->status(), 'body' => $this->decode($response)]
                : null;
        }

        return $withStatus
            ? ['status_code' => $response->status(), 'body' => $this->decode($response)]
            : $this->decode($response);
    }

    /**
     * How much of a failed response is safe to write to the log.
     *
     * A validation failure typically echoes the rejected payload back, and on
     * the WHOIS endpoints that payload is the customer's name, address and
     * phone number. Logs are copied into alerts, shipped off the box and kept
     * for months, so those paths get the status only — the operator can
     * reproduce the call if they need the detail, whereas a home address in
     * last week's log cannot be un-written.
     */
    protected function loggableBody(string $path, string $body): string
    {
        foreach (['/whois', '/portfolio'] as $sensitive) {
            if (str_contains($path, $sensitive)) {
                return '[redacted: response on a path that carries registrant data]';
            }
        }

        // The VPS calls that take a root password. A validation error echoes
        // the rejected fields back, and a password in last week's log is a
        // password in every copy of that log.
        if (str_starts_with($path, '/api/vps/v1/virtual-machines')
            && preg_match('#/virtual-machines(/\d+/(setup|recreate|root-password|panel-password|recovery))?$#', $path)) {
            return '[redacted: response on a path that carries a root password]';
        }

        return mb_substr($body, 0, 1000);
    }

    /**
     * List endpoints wrap their rows in `data`; single resources do not.
     * Callers should not have to care which.
     *
     * @return array<mixed>
     */
    protected function decode(Response $response): array
    {
        $json = $response->json();

        if (! is_array($json)) {
            return [];
        }

        if (array_key_exists('data', $json) && is_array($json['data'])) {
            return $json['data'];
        }

        return $json;
    }

    /**
     * Did this transport failure happen before the request reached upstream?
     *
     * cURL reports a DNS failure (6) or a refused connection (7) before a byte
     * was sent — nothing can have been bought. Every other failure — above all
     * a timeout (28) or an empty reply (52) — may have arrived after upstream
     * took the order, and counts as "we do not know".
     */
    protected function neverReachedServer(\Throwable $e): bool
    {
        for ($cause = $e; $cause !== null; $cause = $cause->getPrevious()) {
            if (method_exists($cause, 'getHandlerContext')) {
                $errno = (int) ($cause->getHandlerContext()['errno'] ?? 0);

                if ($errno === 6 || $errno === 7) {
                    return true;
                }
            }

            $message = strtolower($cause->getMessage());

            if (str_contains($message, 'could not resolve host') || str_contains($message, 'failed to connect')) {
                return true;
            }
        }

        return false;
    }

    protected function client(bool $retry = false): PendingRequest
    {
        $client = Http::withToken($this->apiToken)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->connectTimeout(10);

        // Only reads are retried. The client counts a timeout AFTER the request
        // went out as a connection failure too, so retrying a POST replays an
        // order upstream may already have taken: a second server bought with
        // our card, or "domain not available" back for the one we just got —
        // and a refund to a customer who has it. A 4xx is never retried either.
        if ($retry) {
            $client->retry(2, 500, function ($exception, $request) {
                return $exception instanceof ConnectionException;
            }, throw: false);
        }

        return $client;
    }
}
