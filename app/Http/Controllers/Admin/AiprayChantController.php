<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiprayChant;
use Illuminate\Http\Request;

class AiprayChantController extends Controller
{
    public function index()
    {
        $chants = AiprayChant::orderBy('sort_order')->paginate(30);
        return view('admin.aipray.chants.index', compact('chants'));
    }

    public function create()
    {
        return view('admin.aipray.chants.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'chant_id' => 'required|string|unique:aipray_chants,chant_id|max:100',
            'title_th' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'category' => 'required|in:daily,protection,meditation,merit,sutta,special',
            'lines' => 'required|json',
            'is_community' => 'boolean',
            'author' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['lines'] = json_decode($validated['lines'], true);
        $validated['is_active'] = true;

        AiprayChant::create($validated);

        return redirect()->route('admin.aipray.chants.index')->with('success', 'เพิ่มบทสวดแล้ว');
    }

    public function edit(AiprayChant $chant)
    {
        return view('admin.aipray.chants.edit', compact('chant'));
    }

    public function update(Request $request, AiprayChant $chant)
    {
        $validated = $request->validate([
            'title_th' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'category' => 'required|in:daily,protection,meditation,merit,sutta,special',
            'lines' => 'required|json',
            'is_community' => 'boolean',
            'is_active' => 'boolean',
            'author' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['lines'] = json_decode($validated['lines'], true);

        $chant->update($validated);

        return redirect()->route('admin.aipray.chants.index')->with('success', 'อัพเดทบทสวดแล้ว');
    }

    public function destroy(AiprayChant $chant)
    {
        $chant->delete();
        return redirect()->route('admin.aipray.chants.index')->with('success', 'ลบบทสวดแล้ว');
    }
}
