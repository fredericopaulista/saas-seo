<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all();
        
        foreach ($settings as $setting) {
            if ($setting->key === 'ASAAS_API_KEY' && !empty($setting->value)) {
                try {
                    $setting->value = \Illuminate\Support\Facades\Crypt::decryptString($setting->value);
                } catch (\Exception $e) {
                    // Ignore decryption failures (e.g., if token changes or legacy plain text exists)
                    $setting->value = '';
                }
            }
        }

        return response()->json($settings->groupBy('group'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
            'settings.*.group' => 'required|string',
        ]);

        foreach ($request->settings as $setting) {
            $value = $setting['value'];

            if ($setting['key'] === 'ASAAS_API_KEY' && !empty($value)) {
                $value = \Illuminate\Support\Facades\Crypt::encryptString($value);
            }

            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $value, 
                    'group' => $setting['group']
                ]
            );
        }

        return response()->json(['message' => 'Configurações atualizadas com sucesso.']);
    }
}
