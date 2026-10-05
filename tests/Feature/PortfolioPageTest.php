<?php

namespace Tests\Feature;

use App\Support\PortfolioContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The portfolio shows real work only: sites that are live, pictured with real
 * captures that ship with the repo, linked to the live site.
 */
class PortfolioPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_project_is_shown_with_a_link_to_its_live_site(): void
    {
        $page = $this->withoutVite()->get(route('portfolio'))->assertOk();

        foreach (array_merge(PortfolioContent::featured(), PortfolioContent::more()) as $project) {
            $page->assertSee($project['name']);
            $page->assertSee('href="' . $project['url'] . '"', false);
            $this->assertStringStartsWith('https://', $project['url'], $project['id']);
        }

        // labels with "&" (Blockchain & DEX) must not be escaped twice on the way into x-bi
        $page->assertDontSee('&amp;amp;', false);

        foreach (PortfolioContent::featured() as $project) {
            $page->assertSee('id="case-' . $project['id'] . '"', false);
            $page->assertSee('href="#case-' . $project['id'] . '"', false);
        }
    }

    public function test_every_picture_is_a_file_that_ships_with_the_site(): void
    {
        $files = PortfolioContent::files();

        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            $this->assertFileExists(public_path('artwork/portfolio/' . $file));
        }
        $this->assertSame(count($files), count(array_unique($files)), 'a picture is listed twice');
    }

    public function test_the_made_up_projects_are_gone(): void
    {
        $page = $this->withoutVite()->get(route('portfolio'))->assertOk();

        foreach (['DeFi Lending Platform', 'Food Delivery App', 'NFT Marketplace', 'Smart Farm Dashboard'] as $placeholder) {
            $page->assertDontSee($placeholder);
        }
    }

    public function test_the_page_leads_to_a_quote(): void
    {
        $this->withoutVite()->get(route('portfolio'))
            ->assertOk()
            ->assertSee('href="' . route('quote.index') . '"', false)
            ->assertSee('href="' . route('contact.show') . '"', false);
    }
}
