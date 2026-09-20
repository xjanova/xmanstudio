<?php

namespace Tests\Feature;

use App\Services\DomainRegistrarService;
use Tests\TestCase;

/**
 * Telling "we cannot pay" apart from "you cannot have that domain".
 *
 * We buy from the registrar with our own card and bill the customer from
 * their wallet afterwards. So a refusal has two very different meanings:
 *
 *   — the name is taken, or the registry will not accept it: one order, the
 *     customer tries another name, nothing is wrong with us;
 *   — our card was declined or the account is out of credit: EVERY order
 *     will fail the same way until somebody tops up, and the only sign is a
 *     string of refunded customers who quietly go elsewhere.
 *
 * The second needs a CRITICAL alert. Until this existed both produced a
 * Log::warning and nothing else.
 *
 * Over-matching is the safe direction here: a false alarm costs one message,
 * a miss costs every sale until someone happens to notice.
 */
class DomainRegistrarRefusalTest extends TestCase
{
    /** @dataProvider paymentRefusals */
    public function test_a_money_refusal_is_recognised(int $status, array $body): void
    {
        $this->assertTrue(
            DomainRegistrarService::looksLikePaymentProblem($status, $body),
            'this refusal stops every sale and has to page the owner'
        );
    }

    public static function paymentRefusals(): array
    {
        return [
            '402 alone' => [402, []],
            'insufficient funds' => [400, ['message' => 'Insufficient funds on the account']],
            'card declined' => [400, ['error' => 'Card was declined']],
            'no balance' => [403, ['detail' => 'Account balance too low']],
            'billing problem' => [400, ['message' => 'Billing profile is invalid']],
            'payment required' => [400, ['errors' => ['payment' => ['method missing']]]],
            'nested' => [500, ['data' => ['reason' => 'charge failed at provider']]],
        ];
    }

    /** @dataProvider domainRefusals */
    public function test_a_domain_refusal_is_not_mistaken_for_one(int $status, array $body): void
    {
        $this->assertFalse(
            DomainRegistrarService::looksLikePaymentProblem($status, $body),
            'this is about the name, and paging the owner for it would train them to ignore alerts'
        );
    }

    public static function domainRefusals(): array
    {
        return [
            'already taken' => [409, ['message' => 'Domain is not available']],
            'bad name' => [422, ['errors' => ['domain' => ['invalid characters']]]],
            'tld not supported' => [400, ['message' => 'Unsupported extension']],
            'whois rejected' => [422, ['message' => 'Registrant details rejected by registry']],
            'rate limited' => [429, ['message' => 'Too many requests']],
            'empty' => [500, []],
        ];
    }
}
