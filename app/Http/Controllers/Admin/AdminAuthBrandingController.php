<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AuthBranding;
use Illuminate\Http\Request;

class AdminAuthBrandingController extends Controller
{
    public function edit()
    {
        return view('admin.auth-branding.edit', [
            'branding' => AuthBranding::allForAdmin(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'bg_color' => 'nullable|string|max:20',
            'tagline' => 'nullable|string|max:255',
            'login_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'register_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'bg_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_bg_image' => 'nullable|boolean',
        ]);

        AuthBranding::saveSetting(AuthBranding::BG_COLOR, $validated['bg_color'] ?? '#1e3a5f');
        AuthBranding::saveSetting(AuthBranding::PANEL_TAGLINE, $validated['tagline'] ?? '');

        if ($request->hasFile('login_logo')) {
            AuthBranding::storeUploadedLogo($request->file('login_logo'), AuthBranding::LOGIN_LOGO);
        }

        if ($request->hasFile('register_logo')) {
            AuthBranding::storeUploadedLogo($request->file('register_logo'), AuthBranding::REGISTER_LOGO);
        }

        if ($request->boolean('remove_bg_image')) {
            AuthBranding::removeBackgroundImage();
        } elseif ($request->hasFile('bg_image')) {
            AuthBranding::storeUploadedBackground($request->file('bg_image'));
        }

        return redirect()
            ->route('admin.auth-branding.edit')
            ->with('success', __('Login and register page branding saved.'));
    }
}
