<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlatformSettingsRequest;
use App\Services\PlatformSettingService;
use Illuminate\Http\JsonResponse;

class PlatformSettingController extends Controller
{
    public function __construct(
        protected PlatformSettingService $settingService
    ) {}

    public function index(): JsonResponse
    {
        $settings = $this->settingService->getAllSettings();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function update(UpdatePlatformSettingsRequest $request): JsonResponse
    {
        $this->settingService->setMultiple($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $this->settingService->getAllSettings(),
        ]);
    }
}
