<?php

namespace Modules\Doctor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ProviderSetting;
use Illuminate\Http\Request;

class DoctorSettingsController extends Controller
{
    private const DEFAULTS = [
        'emailNotifications' => true,
        'smsNotifications' => true,
        'pushNotifications' => true,
        'appointmentReminders' => true,
        'marketingEmails' => false,
    ];

    public function show(Request $request)
    {
        $settings = ProviderSetting::where('user_id', $request->user()->id)->first();

        return ApiResponse::data($settings?->settings ?? self::DEFAULTS);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'emailNotifications' => 'nullable|boolean',
            'smsNotifications' => 'nullable|boolean',
            'pushNotifications' => 'nullable|boolean',
            'appointmentReminders' => 'nullable|boolean',
            'marketingEmails' => 'nullable|boolean',
        ]);

        $current = ProviderSetting::where('user_id', $request->user()->id)->first();
        $merged = array_merge($current?->settings ?? self::DEFAULTS, array_filter($validated, fn ($v) => $v !== null));

        ProviderSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['role' => 'doctor', 'settings' => $merged]
        );

        return ApiResponse::message('Settings updated successfully');
    }
}
