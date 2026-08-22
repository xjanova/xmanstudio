<?php

namespace Tests\Feature;

use App\Services\WebsiteKnowledgeService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * `features` is stored as JSON and appears in two shapes across the catalogue:
 * a plain list of strings, and a list of {icon, title, description} objects.
 * imploding the second shape raised "Array to string conversion" and returned
 * HTTP 500 from /ai-chat for any question that matched such a product.
 */
class WebsiteKnowledgeFeatureListTest extends TestCase
{
    protected function featureList(mixed $features, int $limit = 5): string
    {
        $method = new ReflectionMethod(WebsiteKnowledgeService::class, 'featureList');
        $method->setAccessible(true);

        return $method->invoke(app(WebsiteKnowledgeService::class), $features, $limit);
    }

    public function test_it_joins_a_plain_string_list(): void
    {
        $this->assertSame(
            'รองรับ 4K, ทำงานออฟไลน์',
            $this->featureList(['รองรับ 4K', 'ทำงานออฟไลน์']),
        );
    }

    public function test_it_pulls_the_label_out_of_object_entries(): void
    {
        $this->assertSame(
            'Real-time Protection, Behavioral Analysis',
            $this->featureList([
                ['icon' => 'shield-check', 'title' => 'Real-time Protection', 'description' => 'Continuous monitoring'],
                ['icon' => 'cpu-chip', 'title' => 'Behavioral Analysis', 'description' => 'AI-powered detection'],
            ]),
        );
    }

    public function test_it_accepts_name_and_label_keys_too(): void
    {
        $this->assertSame(
            'ชื่อจาก name, ชื่อจาก label',
            $this->featureList([
                ['name' => 'ชื่อจาก name'],
                ['label' => 'ชื่อจาก label'],
            ]),
        );
    }

    public function test_it_skips_entries_with_no_usable_label(): void
    {
        $this->assertSame(
            'เก็บอันนี้',
            $this->featureList([
                ['icon' => 'only-an-icon'],
                'เก็บอันนี้',
                ['title' => ''],
            ]),
        );
    }

    public function test_it_honours_the_limit_and_tolerates_non_arrays(): void
    {
        $this->assertSame('a, b', $this->featureList(['a', 'b', 'c', 'd'], 2));
        $this->assertSame('', $this->featureList(null));
        $this->assertSame('', $this->featureList('not an array'));
    }
}
