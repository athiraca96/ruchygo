<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Vendor account required.',
            ], 403);
        }

        $vendorProfile = $user->vendorProfile;

        if (!$vendorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete your vendor onboarding first.',
                'onboarding_required' => true,
            ], 403);
        }

        if ($vendorProfile->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Your vendor account is pending approval.',
                'status' => 'pending',
            ], 403);
        }

        if ($vendorProfile->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Your vendor application was rejected.',
                'status' => 'rejected',
                'remarks' => $vendorProfile->admin_remarks,
            ], 403);
        }

        return $next($request);
    }
}
