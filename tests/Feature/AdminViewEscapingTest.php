<?php

namespace Tests\Feature;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Text written by customers, shown inside the admin panel's JavaScript.
 *
 * Several admin buttons used to build their onclick / onsubmit handlers as
 * `'{{ $name }}'`. Blade's escaping is for HTML: the browser decodes &#039;
 * back into a quote before the handler runs, so a quote in a customer's name
 * ended the JavaScript string early — and the admin's click ran whatever came
 * after it. These handlers now take @js(), a complete JavaScript literal.
 *
 * The name below is harmless on purpose; what is checked is that it arrives
 * as one escaped literal and never as HTML-escaped text inside a JS string.
 */
class AdminViewEscapingTest extends TestCase
{
    use RefreshDatabase;

    private const NAME = 'O\'Brien "Q" </b>';

    /** What @js() makes of NAME. */
    private const LITERAL = "'O\\u0027Brien \\u0022Q\\u0022 \\u003C\\/b\\u003E'";

    public function test_the_kyc_approve_prompt_carries_the_name_as_a_literal(): void
    {
        $customer = $this->account('user', self::NAME);
        $kyc = KycVerification::create([
            'user_id' => $customer->id,
            'status' => KycVerification::STATUS_PENDING,
            'full_name_th' => 'ทดสอบ',
            'submitted_at' => now(),
        ]);

        $html = $this->actingAs($this->account('super_admin', 'Owner'))
            ->get(route('admin.kyc.show', $kyc->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString("ของ ' + " . self::LITERAL . " + '?", $html);
        $this->assertStringNotContainsString('ของ O&#039;Brien', $html);
    }

    public function test_the_line_user_editor_carries_names_as_literals(): void
    {
        $customer = $this->account('user', self::NAME);
        $customer->forceFill(['line_display_name' => self::NAME])->save();

        $html = $this->actingAs($this->account('super_admin', 'Owner'))
            ->get(route('admin.line-messaging.users'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            "openEditModal({$customer->id}, " . self::LITERAL . ", '', " . self::LITERAL . ')',
            $html
        );
        $this->assertStringNotContainsString("'O&#039;Brien", $html);
    }

    private function account(string $role, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => $role . User::count() . '@example.com',
            'password' => bcrypt('secret-password'),
            'is_active' => true,
            'role' => $role,
        ]);
    }
}
