<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDevice;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * Display a listing of devices
     */
    public function index(Request $request)
    {
        $query = ProductDevice::with('product', 'license')->latest();

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter suspicious only
        if ($request->filled('suspicious')) {
            $query->where('is_suspicious', true);
        }

        // Search by machine_id or machine_name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('machine_id', 'like', "%{$search}%")
                  ->orWhere('machine_name', 'like', "%{$search}%")
                  ->orWhere('first_ip', 'like', "%{$search}%")
                  ->orWhere('last_ip', 'like', "%{$search}%");
            });
        }

        $devices = $query->paginate(20)->withQueryString();

        // Get products for filter dropdown
        $products = Product::where('requires_license', true)->get();

        // Stats
        $stats = [
            'total' => ProductDevice::count(),
            'licensed' => ProductDevice::where('status', ProductDevice::STATUS_LICENSED)->count(),
            'trial' => ProductDevice::where('status', ProductDevice::STATUS_TRIAL)->count(),
            'blocked' => ProductDevice::where('status', ProductDevice::STATUS_BLOCKED)->count(),
            'suspicious' => ProductDevice::where('is_suspicious', true)->count(),
        ];

        return view('admin.devices.index', compact('devices', 'products', 'stats'));
    }

    /**
     * Show device details
     */
    public function show(ProductDevice $device)
    {
        $device->load('product', 'license');

        // Get related devices
        $relatedByIp = $device->findRelatedByIp();
        $relatedByHardware = $device->findRelatedByHardware();

        return view('admin.devices.show', compact('device', 'relatedByIp', 'relatedByHardware'));
    }

    /**
     * Block a device
     */
    public function block(Request $request, ProductDevice $device)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $device->block($request->reason);

        return redirect()
            ->back()
            ->with('success', "Device '{$device->machine_name}' ถูกบล็อกแล้ว");
    }

    /**
     * Unblock a device
     */
    public function unblock(ProductDevice $device)
    {
        $device->unblock();

        return redirect()
            ->back()
            ->with('success', "ปลดบล็อก Device '{$device->machine_name}' แล้ว");
    }

    /**
     * Clear suspicious flag
     */
    public function clearSuspicious(ProductDevice $device)
    {
        $device->update([
            'is_suspicious' => false,
            'abuse_reason' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', "ลบ flag suspicious สำหรับ Device '{$device->machine_name}' แล้ว");
    }

    /**
     * Reset trial for device
     */
    public function resetTrial(Request $request, ProductDevice $device)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:30',
        ]);

        $device->update([
            'status' => ProductDevice::STATUS_TRIAL,
            'trial_expires_at' => now()->addDays($request->days),
            'is_suspicious' => false,
            'abuse_reason' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', "รีเซ็ต Trial สำหรับ Device '{$device->machine_name}' เป็น {$request->days} วัน");
    }

    /**
     * Delete a device
     */
    public function destroy(ProductDevice $device)
    {
        $machineName = $device->machine_name ?? $device->machine_id;
        $device->delete();

        return redirect()
            ->route('admin.devices.index')
            ->with('success', "ลบ Device '{$machineName}' แล้ว");
    }

    /**
     * Bulk block devices
     */
    public function bulkBlock(Request $request)
    {
        $request->validate([
            'device_ids' => 'required|array',
            'device_ids.*' => 'exists:product_devices,id',
            'reason' => 'required|string|max:500',
        ]);

        ProductDevice::whereIn('id', $request->device_ids)->update([
            'status' => ProductDevice::STATUS_BLOCKED,
            'is_suspicious' => true,
            'abuse_reason' => $request->reason,
        ]);

        return redirect()
            ->back()
            ->with('success', sprintf('บล็อก %d devices สำเร็จ', count($request->device_ids)));
    }
}
