<?php

namespace App\Providers;

use App\Models\WebSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $defaultPrimaryColor = '#751204';
        $defaultSidebarLogo = 'src/img/gapuraWhite.png';
        $defaultLoginLogo = 'src/img/gapuraIcon.png';

        $settings = [
            'primaryColor' => $defaultPrimaryColor,
            'sidebarLogoPath' => $defaultSidebarLogo,
            'loginLogoPath' => $defaultLoginLogo,
        ];

        try {
            if (Schema::hasTable('web_settings')) {
                $items = WebSetting::query()
                    ->whereIn('key', ['primary_color', 'sidebar_logo_path', 'login_logo_path'])
                    ->pluck('value', 'key');

                $settings['primaryColor'] = $this->isValidHexColor($items['primary_color'] ?? null)
                    ? strtoupper($items['primary_color'])
                    : $defaultPrimaryColor;

                $settings['sidebarLogoPath'] = $items['sidebar_logo_path'] ?? $defaultSidebarLogo;
                $settings['loginLogoPath'] = $items['login_logo_path'] ?? $defaultLoginLogo;
            }
        } catch (Throwable) {
        }

        $primaryRgb = $this->hexToRgb($settings['primaryColor']);

        View::share('webCustomization', [
            'primaryColor' => $settings['primaryColor'],
            'primaryRgb' => $primaryRgb,
            'primaryDark' => $this->adjustHexBrightness($settings['primaryColor'], -20),
            'sidebarLogoUrl' => $this->toAssetUrl($settings['sidebarLogoPath'], $defaultSidebarLogo),
            'loginLogoUrl' => $this->toAssetUrl($settings['loginLogoPath'], $defaultLoginLogo),
        ]);
    }

    private function isValidHexColor(?string $color): bool
    {
        return is_string($color) && (bool) preg_match('/^#[A-Fa-f0-9]{6}$/', $color);
    }

    private function hexToRgb(string $hex): string
    {
        $clean = ltrim($hex, '#');

        $r = hexdec(substr($clean, 0, 2));
        $g = hexdec(substr($clean, 2, 2));
        $b = hexdec(substr($clean, 4, 2));

        return $r.', '.$g.', '.$b;
    }

    private function adjustHexBrightness(string $hex, int $steps): string
    {
        $steps = max(-255, min(255, $steps));
        $clean = ltrim($hex, '#');

        $r = max(0, min(255, hexdec(substr($clean, 0, 2)) + $steps));
        $g = max(0, min(255, hexdec(substr($clean, 2, 2)) + $steps));
        $b = max(0, min(255, hexdec(substr($clean, 4, 2)) + $steps));

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    private function toAssetUrl(?string $path, string $defaultPath): string
    {
        if (! is_string($path) || trim($path) === '') {
            return asset($defaultPath);
        }

        if (str_starts_with($path, 'branding/')) {
            return asset('storage/'.$path);
        }

        return asset($path);
    }
}
