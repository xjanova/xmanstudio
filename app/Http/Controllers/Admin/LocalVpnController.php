<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\VpnNetwork;
use App\Models\VpnNetworkMember;
use App\Models\VpnRelaySession;
use App\Models\VpnTrafficLog;
use Illuminate\Http\Request;

class LocalVpnController extends Controller
{
    /**
     * Dashboard with stats and charts.
     */
    public function dashboard()
    {
        $totalNetworks = VpnNetwork::count();
        $activeNetworks = VpnNetwork::where('is_active', true)->count();
        $onlineDevices = VpnNetworkMember::where('is_online', true)->count();
        $totalMembers = VpnNetworkMember::count();
        $activeSessions = VpnRelaySession::where('is_active', true)->count();
        $totalTrafficBytes = VpnTrafficLog::sum('bytes');

        // Chart data: last 30 days
        $chartData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $dayStart = now()->subDays($i)->startOfDay();
            $dayEnd = now()->subDays($i)->endOfDay();

            $chartData[] = [
                'date' => $date,
                'networks_created' => VpnNetwork::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'joins' => VpnTrafficLog::where('action', 'join')->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'traffic_bytes' => VpnTrafficLog::whereBetween('created_at', [$dayStart, $dayEnd])->sum('bytes'),
            ];
        }

        // Recent activity
        $recentActivity = VpnTrafficLog::with(['network', 'member'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.localvpn.dashboard', compact(
            'totalNetworks',
            'activeNetworks',
            'onlineDevices',
            'totalMembers',
            'activeSessions',
            'totalTrafficBytes',
            'chartData',
            'recentActivity'
        ));
    }

    /**
     * List all networks with search/filter.
     */
    public function networks(Request $request)
    {
        $query = VpnNetwork::with('owner')->withCount('members');

        if ($search = $request->get('search')) {
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->get('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->get('type') === 'public') {
            $query->where('is_public', true);
        } elseif ($request->get('type') === 'private') {
            $query->where('is_public', false);
        }

        $networks = $query->orderByDesc('created_at')->paginate(20)->appends($request->query());

        return view('admin.localvpn.networks', compact('networks'));
    }

    /**
     * Show network details with members.
     */
    public function showNetwork($id)
    {
        $network = VpnNetwork::with(['owner', 'members' => function ($q) {
            $q->orderByDesc('is_online')->orderByDesc('last_heartbeat_at');
        }])->findOrFail($id);

        $activeSessions = VpnRelaySession::where('network_id', $id)
            ->where('is_active', true)
            ->with(['sourceMember', 'targetMember'])
            ->get();

        $totalTraffic = VpnTrafficLog::where('network_id', $id)->sum('bytes');

        return view('admin.localvpn.show-network', compact('network', 'activeSessions', 'totalTraffic'));
    }

    /**
     * Toggle network active/inactive.
     */
    public function toggleNetwork($id)
    {
        $network = VpnNetwork::findOrFail($id);
        $network->is_active = ! $network->is_active;
        $network->save();

        $status = $network->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return back()->with('success', "เครือข่าย \"{$network->name}\" ถูก{$status}แล้ว");
    }

    /**
     * Delete a network.
     */
    public function deleteNetwork($id)
    {
        $network = VpnNetwork::findOrFail($id);
        $name = $network->name;

        // Log the deletion
        VpnTrafficLog::create([
            'network_id' => $network->id,
            'action' => 'network_delete',
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        $network->delete();

        return redirect()->route('admin.localvpn.networks')
            ->with('success', "เครือข่าย \"{$name}\" ถูกลบแล้ว");
    }

    /**
     * List all online members.
     */
    public function members(Request $request)
    {
        $query = VpnNetworkMember::with('network')
            ->where('is_online', true);

        if ($search = $request->get('search')) {
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('virtual_ip', 'like', "%{$search}%")
                    ->orWhere('public_ip', 'like', "%{$search}%");
            });
        }

        $members = $query->orderByDesc('last_heartbeat_at')->paginate(20)->appends($request->query());

        return view('admin.localvpn.members', compact('members'));
    }

    /**
     * Kick a member from their network.
     */
    public function kickMember($id)
    {
        $member = VpnNetworkMember::with('network')->findOrFail($id);
        $displayName = $member->display_name;
        $networkName = $member->network->name;

        // Log the action
        VpnTrafficLog::create([
            'network_id' => $member->network_id,
            'member_id' => $member->id,
            'action' => 'leave',
            'ip_address' => request()->ip(),
            'metadata' => ['reason' => 'kicked_by_admin'],
            'created_at' => now(),
        ]);

        // End any active relay sessions
        VpnRelaySession::where(function ($q) use ($member) {
            $q->where('source_member_id', $member->id)
                ->orWhere('target_member_id', $member->id);
        })->where('is_active', true)->update([
            'is_active' => false,
            'ended_at' => now(),
        ]);

        $member->delete();

        return back()->with('success', "สมาชิก \"{$displayName}\" ถูกเตะออกจากเครือข่าย \"{$networkName}\"");
    }

    /**
     * List active relay sessions.
     */
    public function sessions(Request $request)
    {
        $query = VpnRelaySession::with(['network', 'sourceMember', 'targetMember'])
            ->where('is_active', true);

        $sessions = $query->orderByDesc('started_at')->paginate(20)->appends($request->query());

        return view('admin.localvpn.sessions', compact('sessions'));
    }

    /**
     * Relay server settings page.
     */
    public function settings()
    {
        $settings = [
            'localvpn_max_networks_per_user' => Setting::getValue('localvpn_max_networks_per_user', '5'),
            'localvpn_max_members_per_network' => Setting::getValue('localvpn_max_members_per_network', '10'),
            'localvpn_heartbeat_interval' => Setting::getValue('localvpn_heartbeat_interval', '30'),
            'localvpn_session_timeout' => Setting::getValue('localvpn_session_timeout', '120'),
            'localvpn_data_relay_limit_mb' => Setting::getValue('localvpn_data_relay_limit_mb', '100'),
        ];

        return view('admin.localvpn.settings', compact('settings'));
    }

    /**
     * Save relay server settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'localvpn_max_networks_per_user' => 'required|integer|min:1|max:100',
            'localvpn_max_members_per_network' => 'required|integer|min:2|max:254',
            'localvpn_heartbeat_interval' => 'required|integer|min:5|max:300',
            'localvpn_session_timeout' => 'required|integer|min:30|max:3600',
            'localvpn_data_relay_limit_mb' => 'required|integer|min:1|max:10000',
        ]);

        foreach ($request->only([
            'localvpn_max_networks_per_user',
            'localvpn_max_members_per_network',
            'localvpn_heartbeat_interval',
            'localvpn_session_timeout',
            'localvpn_data_relay_limit_mb',
        ]) as $key => $value) {
            Setting::setValue($key, $value);
        }

        return back()->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * Show traffic logs with filtering.
     */
    public function trafficLogs(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $query = VpnTrafficLog::with(['network', 'member']);

        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }

        if ($networkId = $request->get('network_id')) {
            $query->where('network_id', $networkId);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $logs = $query->orderByDesc('created_at')->paginate(50)->appends($request->query());

        $networks = VpnNetwork::orderBy('name')->pluck('name', 'id');

        return view('admin.localvpn.traffic', compact('logs', 'networks'));
    }
}
