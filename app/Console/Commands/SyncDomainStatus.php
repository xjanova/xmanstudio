<?php

namespace App\Console\Commands;

use App\Models\DomainRegistration;
use App\Services\DomainRegistrarService;
use App\Services\HostingerApiService;
use App\Support\Alerts\BusinessAlerts;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Keep each domain's row in step with the registrar, once a day.
 *
 * Two gaps it closes:
 *
 *   expiry   — nothing ever set a domain to `expired`. One that ran out kept
 *              saying "active" on the customer's page, with a DNS editor that
 *              edited nothing, until somebody looked. Domains at or past their
 *              date are checked upstream and marked for what they are; a
 *              domain renewed somewhere we did not see gets its new date.
 *
 *   renewals — a domain with no subscription id can never be renewed from
 *              the wallet (the renew call needs it). The link is looked for,
 *              and one that cannot be found is put in front of the admin
 *              while there is still time, not on the day it lapses.
 */
class SyncDomainStatus extends Command
{
    protected $signature = 'domains:sync-status
                            {--id= : Only this registration}
                            {--dry : Report without changing anything}';

    protected $description = 'Mark expired domains and link renewable subscriptions';

    /** Look this far ahead: a domain about to lapse is worth one call to be sure of its date. */
    protected const LOOKAHEAD_DAYS = 3;

    /** Keep checking an expired domain this long after its date — the registry's grace and redemption. */
    protected const WATCH_EXPIRED_DAYS = 45;

    public function handle(HostingerApiService $api, DomainRegistrarService $registrar): int
    {
        if (! $api->isConfigured()) {
            $this->warn('No registrar API token configured — nothing to sync.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry');

        $expiring = DomainRegistration::registrations()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(self::LOOKAHEAD_DAYS))
            ->where(fn ($q) => $q->where('status', DomainRegistration::STATUS_ACTIVE)
                // An expired one is watched for a while in case it is renewed
                // by hand upstream; long gone, it is only a call wasted a day.
                ->orWhere(fn ($q) => $q->where('status', DomainRegistration::STATUS_EXPIRED)
                    ->where('expires_at', '>', now()->subDays(self::WATCH_EXPIRED_DAYS))))
            ->when($this->option('id'), fn ($q, $id) => $q->where('id', $id))
            // Least recently checked first: the same fifty must not take every
            // run while the rest are never looked at.
            ->orderByRaw('last_polled_at IS NOT NULL')
            ->orderBy('last_polled_at')
            ->limit(50)
            ->get();

        $marked = $extended = 0;

        foreach ($expiring as $domain) {
            $details = $api->getDomain($domain->domain);

            if (! $dry) {
                $domain->forceFill(['last_polled_at' => now()])->saveQuietly();
            }

            if (! is_array($details) || $details === []) {
                continue;
            }

            $status = strtolower((string) ($details['status'] ?? ''));
            $expires = null;

            try {
                $expires = isset($details['expires_at']) ? Carbon::parse($details['expires_at']) : null;
            } catch (\Throwable) {
            }

            // Renewed — by us on a day this missed, or by the owner by hand.
            if ($expires && $domain->expires_at && $expires->gt($domain->expires_at->copy()->addDay())) {
                $this->line(sprintf('  %s — expiry moved to %s', $domain->domain, $expires->format('Y-m-d')));

                if (! $dry) {
                    $domain->forceFill([
                        'expires_at' => $expires,
                        'status' => DomainRegistration::STATUS_ACTIVE,
                        'renewal_notice_sent_at' => null,
                        'expiry_reminders_sent' => null,
                    ])->save();
                }

                $extended++;

                continue;
            }

            $lapsed = in_array($status, ['expired', 'deleted'], true)
                || ($domain->expires_at && $domain->expires_at->isPast() && $status !== 'active');

            if ($lapsed && $domain->status !== DomainRegistration::STATUS_EXPIRED) {
                $this->warn(sprintf('  %s — expired (registrar says "%s")', $domain->domain, $status ?: 'unknown'));

                if (! $dry) {
                    $domain->update(['status' => DomainRegistration::STATUS_EXPIRED]);
                    BusinessAlerts::domainExpired($domain);
                }

                $marked++;
            }
        }

        // Every live domain must be renewable from the wallet.
        $unlinked = DomainRegistration::registrations()
            ->where('status', DomainRegistration::STATUS_ACTIVE)
            ->whereNull('remote_subscription_id')
            ->when($this->option('id'), fn ($q, $id) => $q->where('id', $id))
            ->get();

        $stillUnlinked = [];

        foreach ($unlinked as $domain) {
            $found = $dry ? null : $registrar->linkSubscription($domain);

            if ($found) {
                $this->info(sprintf('  %s — linked to subscription %s', $domain->domain, $found));
                // Linking it is not enough: it must not renew on our card.
                $api->setAutoRenewal($found, false);
            } else {
                $stillUnlinked[] = $domain->domain;
            }
        }

        if ($stillUnlinked !== []) {
            $this->warn('Not renewable from the wallet (no subscription link): ' . implode(', ', $stillUnlinked));

            if (! $dry) {
                BusinessAlerts::domainsWithoutSubscription($stillUnlinked);
            }
        }

        $this->info(sprintf('%s %d marked expired, %d with a later expiry, %d without a subscription link.', $dry ? '[dry run]' : 'Done:', $marked, $extended, count($stillUnlinked)));

        return self::SUCCESS;
    }
}
