<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller {
    public function getCommission() {
        $rate = DB::table('settings')->where('key', 'commission_rate')->value('value');
        return response()->json(['commission_rate' => $rate]);
    }

    public function updateCommission(Request $request) {
        $request->validate(['commission_rate' => 'required|numeric|min:1|max:100']);

        DB::table('settings')
            ->where('key', 'commission_rate')
            ->update(['value' => $request->commission_rate]);

        return response()->json(['message' => 'Commission rate updated.', 'commission_rate' => $request->commission_rate]);
    }
}
