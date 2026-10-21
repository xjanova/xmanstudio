<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GithubSetting;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2026-10-18 brainx-cloud stopped syncing: the browser autofilled the admin's login e-mail into the
 * Repository field and the password into the Token field, so the stored repo was
 * "xjanova/xjanovax@gmail.com" — GitHub answered 401 for the token and 404 for the repo every 10 minutes.
 */
class GithubSettingsAutofillTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::create(['name' => 'Apps', 'slug' => 'apps', 'is_active' => true]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'BrainX Cloud',
            'slug' => 'brainx-cloud',
            'description' => 'x',
            'price' => 399,
            'stock' => 999,
            'is_active' => true,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function save(Product $product, array $fields)
    {
        return $this->actingAs($this->admin())
            ->from(route('admin.products.versions.index', $product))
            ->post(route('admin.products.versions.github', $product), $fields + [
                'github_owner' => 'xjanova',
                'github_repo' => 'BrainX',
                'github_token' => '',
                'asset_pattern' => '*.exe',
                'is_active' => '1',
                'auto_sync' => '1',
            ]);
    }

    public function test_an_autofilled_email_and_password_are_refused(): void
    {
        $product = $this->product();

        $this->save($product, ['github_repo' => 'xjanovax@gmail.com', 'github_token' => 'MyLoginPass123'])
            ->assertSessionHasErrors(['github_repo', 'github_token']);

        $this->assertFalse(GithubSetting::where('product_id', $product->id)->exists());
    }

    public function test_a_real_repo_with_no_token_or_a_real_token_is_saved(): void
    {
        $product = $this->product();

        $this->save($product, [])->assertSessionHasNoErrors();
        $this->assertSame('BrainX', $product->fresh()->githubSetting->github_repo);

        $this->save($product, ['github_token' => 'github_pat_11ABCDEF0_abcXYZ123'])->assertSessionHasNoErrors();
        $this->assertSame('github_pat_11ABCDEF0_abcXYZ123', $product->fresh()->githubSetting->github_token_decrypted);

        // '********' = keep the stored token
        $this->save($product, ['github_token' => '********'])->assertSessionHasNoErrors();
        $this->assertSame('github_pat_11ABCDEF0_abcXYZ123', $product->fresh()->githubSetting->github_token_decrypted);
    }

    public function test_the_form_tells_the_browser_not_to_autofill_and_shows_the_errors(): void
    {
        $product = $this->product();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.products.versions.index', $product))
            ->post(route('admin.products.versions.github', $product), [
                'github_owner' => 'xjanova', 'github_repo' => 'a@b.com', 'github_token' => '', 'asset_pattern' => '*.exe',
            ])
            ->assertRedirect(route('admin.products.versions.index', $product));

        $this->actingAs($admin)->get(route('admin.products.versions.index', $product))
            ->assertOk()
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee('Repository ต้องเป็นชื่อ repo บน GitHub', false);
    }
}
