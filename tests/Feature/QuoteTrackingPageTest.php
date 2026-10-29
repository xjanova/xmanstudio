<?php

namespace Tests\Feature;

use App\Models\ProjectOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTrackingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_tracking_page_explains_what_to_search_by(): void
    {
        $this->get('/quote/track')
            ->assertOk()
            ->assertSee('ติดตาม', false)
            ->assertSee('QT-20260213-ABCD', false)
            ->assertSee('PRJ-20260213-ABCD', false)
            ->assertSee(route('quote.track.search'), false);
    }

    public function test_a_project_number_finds_the_project_and_its_progress(): void
    {
        $project = ProjectOrder::create([
            'project_name' => 'เว็บร้านค้าทดสอบ',
            'project_type' => 'web',
            'total_price' => 32100,
            'status' => 'in_progress',
            'progress_percent' => 45,
        ]);
        $project->features()->create(['name' => 'ระบบตะกร้าสินค้า', 'status' => 'in_progress', 'order' => 0]);

        $this->get(route('quote.track.search', ['query' => $project->project_number]))
            ->assertOk()
            ->assertSee($project->project_number, false)
            ->assertSee('เว็บร้านค้าทดสอบ', false)
            ->assertSee('aria-valuenow="45"', false)
            ->assertSee('ระบบตะกร้าสินค้า', false)
            ->assertSee('฿32,100', false);
    }

    public function test_an_unknown_reference_says_nothing_was_found(): void
    {
        $this->get(route('quote.track.search', ['query' => 'no-such-ref']))
            ->assertOk()
            ->assertSee('ไม่พบข้อมูลของ', false);
    }
}
