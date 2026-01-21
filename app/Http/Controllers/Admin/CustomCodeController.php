<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomCodeController extends Controller
{
    public function index()
    {
        $settings = [
            'custom_code_head' => Setting::getValue('custom_code_head', ''),
            'custom_code_body_start' => Setting::getValue('custom_code_body_start', ''),
            'custom_code_body_end' => Setting::getValue('custom_code_body_end', ''),
        ];

        return view('admin.custom-code.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'custom_code_head' => 'nullable|string|max:65535',
            'custom_code_body_start' => 'nullable|string|max:65535',
            'custom_code_body_end' => 'nullable|string|max:65535',
        ], [
            'custom_code_head.max' => 'โค้ดส่วน Head ต้องไม่เกิน 65,535 ตัวอักษร',
            'custom_code_body_start.max' => 'โค้ดส่วน Body Start ต้องไม่เกิน 65,535 ตัวอักษร',
            'custom_code_body_end.max' => 'โค้ดส่วน Body End ต้องไม่เกิน 65,535 ตัวอักษร',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('admin.custom-code.index')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Sanitize and validate the code
            $headCode = $this->sanitizeCode($request->input('custom_code_head', ''));
            $bodyStartCode = $this->sanitizeCode($request->input('custom_code_body_start', ''));
            $bodyEndCode = $this->sanitizeCode($request->input('custom_code_body_end', ''));

            // Save settings
            Setting::setValue(
                'custom_code_head',
                $headCode,
                'string',
                'custom_code',
                'โค้ดที่ใส่ใน <head> เช่น Google Analytics, Meta tags',
                false
            );

            Setting::setValue(
                'custom_code_body_start',
                $bodyStartCode,
                'string',
                'custom_code',
                'โค้ดที่ใส่หลัง <body> เช่น Google Tag Manager noscript',
                false
            );

            Setting::setValue(
                'custom_code_body_end',
                $bodyEndCode,
                'string',
                'custom_code',
                'โค้ดที่ใส่ก่อน </body> เช่น Chat widgets, tracking pixels',
                false
            );

            return redirect()
                ->route('admin.custom-code.index')
                ->with('success', 'บันทึกโค้ดเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            return redirect()
                ->route('admin.custom-code.index')
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * Basic sanitization to prevent breaking the page
     * Allow common tracking scripts but remove potentially harmful code
     */
    private function sanitizeCode(?string $code): string
    {
        if (empty($code)) {
            return '';
        }

        // Remove PHP tags to prevent code injection
        $code = preg_replace('/<\?php.*?\?>/is', '', $code);
        $code = preg_replace('/<\?=.*?\?>/is', '', $code);
        $code = preg_replace('/<\?.*?\?>/is', '', $code);

        // Remove Blade directives
        $code = preg_replace('/@(php|endphp|if|else|endif|foreach|endforeach|for|endfor|while|endwhile|include|extends|section|yield|component|slot|push|stack|env|production|auth|guest|can|cannot|switch|case|break|default|isset|empty|unless|endunless|error|enderror|verbatim|endverbatim|json|once|endonce|selected|checked|disabled|readonly|required|class|style|aware|props|inject|dd|dump)(\s|\(|$)/i', '', $code);

        return trim($code);
    }

    public function clear(Request $request)
    {
        $field = $request->input('field');

        $allowedFields = ['custom_code_head', 'custom_code_body_start', 'custom_code_body_end'];

        if (!in_array($field, $allowedFields)) {
            return redirect()
                ->route('admin.custom-code.index')
                ->with('error', 'ไม่พบฟิลด์ที่ต้องการล้าง');
        }

        try {
            Setting::setValue($field, '', 'string', 'custom_code');

            $fieldNames = [
                'custom_code_head' => 'Head Code',
                'custom_code_body_start' => 'Body Start Code',
                'custom_code_body_end' => 'Body End Code',
            ];

            return redirect()
                ->route('admin.custom-code.index')
                ->with('success', 'ล้าง '.$fieldNames[$field].' เรียบร้อยแล้ว');

        } catch (\Exception $e) {
            return redirect()
                ->route('admin.custom-code.index')
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }
}
