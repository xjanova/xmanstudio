<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuideScreenshotController extends Controller
{
    private const STEPS = [
        1 => 'ดาวน์โหลด APK',
        2 => 'อนุญาตติดตั้ง (Unknown Sources)',
        3 => 'ติดตั้งแอพ',
        4 => 'เปิด Accessibility Service',
        5 => 'เริ่มทดลองฟรี 24 ชม.',
        6 => 'Activate License',
    ];

    /**
     * Show guide screenshots management page.
     */
    public function index(Product $product)
    {
        $screenshots = $this->getScreenshots($product);

        return view('admin.guide-screenshots.index', [
            'product' => $product,
            'steps' => self::STEPS,
            'screenshots' => $screenshots,
        ]);
    }

    /**
     * Upload a screenshot for a specific step.
     */
    public function upload(Request $request, Product $product)
    {
        $request->validate([
            'step' => 'required|integer|min:1|max:6',
            'screenshot' => 'required|image|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        $step = $request->input('step');
        $dir = "guide-screenshots/{$product->slug}";

        // Delete old screenshot for this step
        $this->deleteStepScreenshot($product, $step);

        // Store new screenshot
        $ext = $request->file('screenshot')->getClientOriginalExtension();
        $filename = "step-{$step}.{$ext}";
        $request->file('screenshot')->storeAs($dir, $filename, 'public');

        return back()->with('success', "อัพโหลดรูปขั้นตอนที่ {$step} สำเร็จ");
    }

    /**
     * Delete a screenshot for a specific step.
     */
    public function destroy(Product $product, int $step)
    {
        $this->deleteStepScreenshot($product, $step);

        return back()->with('success', "ลบรูปขั้นตอนที่ {$step} แล้ว (จะกลับไปใช้ภาพ mockup)");
    }

    /**
     * Get all screenshots for a product's guide.
     */
    private function getScreenshots(Product $product): array
    {
        $screenshots = [];
        $dir = "guide-screenshots/{$product->slug}";

        for ($i = 1; $i <= 6; $i++) {
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $path = "{$dir}/step-{$i}.{$ext}";
                if (Storage::disk('public')->exists($path)) {
                    $screenshots[$i] = $path;
                    break;
                }
            }
        }

        return $screenshots;
    }

    /**
     * Delete all versions of a step screenshot.
     */
    private function deleteStepScreenshot(Product $product, int $step): void
    {
        $dir = "guide-screenshots/{$product->slug}";

        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $path = "{$dir}/step-{$step}.{$ext}";
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
