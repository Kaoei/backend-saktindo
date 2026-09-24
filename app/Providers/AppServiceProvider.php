<?php

namespace App\Providers;

use App\Models\WebSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
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
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        $defaultPrimaryColor = '#751204';
        $defaultSidebarLogo = 'src/img/gapuraWhite.png';
        $defaultLoginLogo = 'src/img/gapuraIcon.png';

        $customization = Cache::remember('backend_web_customization', 86400, function () use ($defaultPrimaryColor, $defaultSidebarLogo, $defaultLoginLogo) {
            $settings = [
                'primaryColor' => $defaultPrimaryColor,
                'sidebarLogoPath' => $defaultSidebarLogo,
                'loginLogoPath' => $defaultLoginLogo,
            ];

            try {
                if (Schema::hasTable('web_settings')) {
                    $items = WebSetting::allCached();

                    $settings['primaryColor'] = $this->isValidHexColor($items['primary_color'] ?? null)
                        ? strtoupper($items['primary_color'])
                        : $defaultPrimaryColor;

                    $settings['sidebarLogoPath'] = $items['sidebar_logo_path'] ?? $defaultSidebarLogo;
                    $settings['loginLogoPath'] = $items['login_logo_path'] ?? $defaultLoginLogo;
                }
            } catch (Throwable) {
            }

            $primaryRgb = $this->hexToRgb($settings['primaryColor']);

            return [
                'primaryColor' => $settings['primaryColor'],
                'primaryRgb' => $primaryRgb,
                'primaryDark' => $this->adjustHexBrightness($settings['primaryColor'], -20),
                'sidebarLogoUrl' => $this->toAssetUrl($settings['sidebarLogoPath'], $defaultSidebarLogo),
                'loginLogoUrl' => $this->toAssetUrl($settings['loginLogoPath'], $defaultLoginLogo),
            ];
        });

        View::share('webCustomization', $customization);
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
            return '/'.ltrim($defaultPath, '/');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $cleanPath = ltrim(str_replace('public/', '', $path), '/');

        if (str_starts_with($cleanPath, 'branding/')) {
            return '/storage/'.$cleanPath;
        }

        return '/'.ltrim($cleanPath, '/');
    }
}
