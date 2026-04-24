<?php

namespace App\Http\Controllers;

use App\Models\WebSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebCustomizationController extends Controller
{
    public function edit()
    {
        return view('web-customization.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'primary_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'sidebar_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'login_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_sidebar_logo' => ['nullable', 'boolean'],
            'remove_login_logo' => ['nullable', 'boolean'],
        ]);

        WebSetting::setValue('primary_color', strtoupper($data['primary_color']));

        $currentSidebarLogo = WebSetting::getValue('sidebar_logo_path');
        $currentLoginLogo = WebSetting::getValue('login_logo_path');

        if ($request->boolean('remove_sidebar_logo') && ! empty($currentSidebarLogo)) {
            Storage::disk('public')->delete($currentSidebarLogo);
            WebSetting::setValue('sidebar_logo_path', null);
            $currentSidebarLogo = null;
        }

        if ($request->boolean('remove_login_logo') && ! empty($currentLoginLogo)) {
            Storage::disk('public')->delete($currentLoginLogo);
            WebSetting::setValue('login_logo_path', null);
            $currentLoginLogo = null;
        }

        if ($request->hasFile('sidebar_logo')) {
            if (! empty($currentSidebarLogo)) {
                Storage::disk('public')->delete($currentSidebarLogo);
            }

            $newSidebarLogo = $request->file('sidebar_logo')->store('branding', 'public');
            WebSetting::setValue('sidebar_logo_path', $newSidebarLogo);
        }

        if ($request->hasFile('login_logo')) {
            if (! empty($currentLoginLogo)) {
                Storage::disk('public')->delete($currentLoginLogo);
            }

            $newLoginLogo = $request->file('login_logo')->store('branding', 'public');
            WebSetting::setValue('login_logo_path', $newLoginLogo);
        }

        return redirect()->route('web-customization.edit')->with('status', 'Web customization berhasil disimpan.');
    }
}
