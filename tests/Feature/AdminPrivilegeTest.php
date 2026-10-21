<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Who may hand out power in the admin panel.
 *
 * The permission system lets the owner give a staff admin users.edit or
 * roles.edit to look after customers. Before these rules either permission was
 * also a way up: users.edit let the holder set their own role to super_admin,
 * and roles.edit let them add themselves to the super_admin role — which passes
 * every permission check there is.
 *
 * The rules:
 *   — only the owner (super_admin) changes anyone's role or role assignments
 *   — only the owner edits, switches off or deletes another admin's account
 *   — staff keep doing what the permission is for: looking after customers
 */
class AdminPrivilegeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $staff;

    private Role $manager;

    private Role $superRole;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = collect(['users.view', 'users.create', 'users.edit', 'users.delete', 'roles.view', 'roles.edit'])
            ->map(fn ($name) => Permission::create(['name' => $name, 'display_name' => $name, 'group' => explode('.', $name)[0]]));

        $this->superRole = Role::create([
            'name' => 'super_admin', 'display_name' => 'Super Admin', 'color' => '#ff0000', 'level' => 100, 'is_system' => true,
        ]);
        $this->manager = Role::create([
            'name' => 'manager', 'display_name' => 'Manager', 'color' => '#00ff00', 'level' => 50, 'is_system' => false,
        ]);
        $this->manager->permissions()->sync($permissions->pluck('id'));

        $this->owner = $this->account('super_admin');
        $this->staff = $this->account('admin');
        $this->staff->roles()->attach($this->manager->id);
    }

    // ────────────────────────────────────────────── the ways up that are closed

    public function test_staff_cannot_make_themselves_super_admin(): void
    {
        $this->actingAs($this->staff)
            ->put("/admin/users/{$this->staff->id}", $this->form($this->staff, ['role' => 'super_admin']))
            ->assertSessionHas('error');

        $this->assertSame('admin', $this->staff->fresh()->role);
    }

    public function test_staff_cannot_create_an_admin(): void
    {
        $this->actingAs($this->staff)->post('/admin/users', [
            'name' => 'Sock Puppet',
            'email' => 'puppet@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
            'role' => 'super_admin',
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['email' => 'puppet@example.com']);
    }

    public function test_staff_cannot_give_themselves_another_role(): void
    {
        $this->actingAs($this->staff)
            ->put("/admin/users/{$this->staff->id}", $this->form($this->staff, [
                'role_ids' => [$this->manager->id, $this->superRole->id],
            ]))
            ->assertSessionHas('error');

        $this->assertFalse($this->staff->fresh()->hasRole('super_admin'));
    }

    public function test_staff_cannot_join_the_super_admin_role(): void
    {
        $this->actingAs($this->staff)
            ->post("/admin/roles/{$this->superRole->id}/add-user", ['user_id' => $this->staff->id])
            ->assertSessionHas('error');

        $this->assertFalse($this->staff->fresh()->hasRole('super_admin'));
    }

    public function test_staff_cannot_widen_a_role(): void
    {
        $before = $this->manager->permissions()->count();
        $extra = Permission::create(['name' => 'wallets.adjust', 'display_name' => 'x', 'group' => 'wallets']);

        $this->actingAs($this->staff)->put("/admin/roles/{$this->manager->id}", [
            'name' => 'manager',
            'display_name' => 'Manager',
            'color' => '#00ff00',
            'level' => 50,
            'permissions' => $this->manager->permissions()->pluck('permissions.id')->push($extra->id)->all(),
        ])->assertSessionHas('error');

        $this->assertSame($before, $this->manager->permissions()->count());
    }

    public function test_staff_cannot_take_over_another_admin(): void
    {
        $peer = $this->account('admin');

        $this->actingAs($this->staff)
            ->put("/admin/users/{$peer->id}", $this->form($peer, [
                'password' => 'N3w-Passw0rd!!',
                'password_confirmation' => 'N3w-Passw0rd!!',
            ]))
            ->assertSessionHas('error');

        $this->assertTrue(Hash::check('secret-password', $peer->fresh()->password));

        $this->actingAs($this->staff)->post("/admin/users/{$peer->id}/toggle")->assertSessionHas('error');
        $this->assertTrue($peer->fresh()->is_active);

        $this->actingAs($this->staff)->delete("/admin/users/{$peer->id}")->assertSessionHas('error');
        $this->assertNotNull($peer->fresh());
    }

    public function test_bulk_actions_skip_admin_accounts_for_staff(): void
    {
        $peer = $this->account('admin');
        $customer = $this->account('user');

        $this->actingAs($this->staff)->post('/admin/users/bulk', [
            'action' => 'deactivate',
            'user_ids' => [$peer->id, $customer->id],
        ]);

        $this->assertTrue($peer->fresh()->is_active);
        $this->assertFalse($customer->fresh()->is_active);
    }

    // ────────────────────────────────────────────── what the permissions are for

    public function test_staff_still_look_after_customers(): void
    {
        $customer = $this->account('user');

        $this->actingAs($this->staff)
            ->put("/admin/users/{$customer->id}", $this->form($customer, ['name' => 'Renamed Customer']))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error');

        $this->assertSame('Renamed Customer', $customer->fresh()->name);
    }

    public function test_the_owner_still_hands_out_roles(): void
    {
        $customer = $this->account('user');

        $this->actingAs($this->owner)
            ->put("/admin/users/{$customer->id}", $this->form($customer, [
                'role' => 'admin',
                'role_ids' => [$this->manager->id],
            ]))
            ->assertSessionMissing('error');

        $this->assertSame('admin', $customer->fresh()->role);
        $this->assertTrue($customer->fresh()->hasRole('manager'));

        $this->actingAs($this->owner)
            ->post("/admin/roles/{$this->superRole->id}/add-user", ['user_id' => $customer->id])
            ->assertSessionMissing('error');

        $this->assertTrue($customer->fresh()->hasRole('super_admin'));
    }

    // ────────────────────────────────────────────── admin data behind an API token

    public function test_payment_sms_history_is_for_admins_only(): void
    {
        Sanctum::actingAs($this->account('user'));
        $this->getJson('/api/v1/sms-payment/notifications')->assertForbidden();

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/v1/sms-payment/notifications')->assertOk()->assertJsonPath('success', true);
    }

    // ────────────────────────────────────────────── the pages themselves

    public function test_admin_pages_are_neither_framed_nor_cached(): void
    {
        $response = $this->actingAs($this->owner)->get('/admin/users')->assertOk();

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    private function account(string $role): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $role . User::count() . '@example.com',
            'password' => bcrypt('secret-password'),
            'is_active' => true,
            'role' => $role,
        ]);
    }

    /** The edit form as the browser submits it: every field, current values unless overridden. */
    private function form(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'role_ids' => $user->roles()->pluck('roles.id')->all(),
            'is_active' => 1,
        ], $overrides);
    }
}
