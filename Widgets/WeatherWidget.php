<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Modules\Dashboard\Entities\DashboardWidget;

/** "Wetter" — the only widget backed by an external API rather than
 * FreeScout's own data. Open-Meteo (open-meteo.com), chosen specifically
 * because it needs no API key/registration at all — same reasoning this
 * project already applied when picking OpenStreetMap/Nominatim over
 * Google Maps for Calendar's mini-map. City is per-widget-instance
 * configuration (DashboardWidget.config->city, set via the gear icon —
 * see Support/WidgetRegistry.php's 'configurable' flag and
 * DashboardController::updateConfig()), not a global admin setting.
 * Responses are cached 30 minutes so the API isn't hit on every page
 * load. */
class WeatherWidget implements Widget
{
    const DEFAULT_CITY = 'Zürich';
    const CACHE_TTL = 1800;

    public static function isAvailable(User $user): bool
    {
        return true;
    }

    public static function render(User $user, string $size): string
    {
        $widgetRow = DashboardWidget::where('user_id', $user->id)->where('widget_key', 'weather')->first();
        $city = ($widgetRow && !empty($widgetRow->config['city'])) ? $widgetRow->config['city'] : self::DEFAULT_CITY;

        $data = self::fetch($city);
        if (!$data) {
            return '<div class="dash-empty-inline">'.e(__('Weather data unavailable.')).'</div>';
        }

        $current = $data['current'];
        [$type, $label] = self::codeInfo($current['weather_code']);
        $todayHigh = round($data['daily']['temperature_2m_max'][0]);
        $todayLow = round($data['daily']['temperature_2m_min'][0]);

        $html = '<div class="dash-weather-now">'
            .self::icon($type, 44)
            .'<div class="dash-weather-now-main">'
            .'<span class="dash-weather-temp">'.round($current['temperature_2m']).'&deg;</span>'
            .'<span class="dash-weather-label">'.e(__($label)).'</span>'
            .'</div>'
            .'<div class="dash-weather-hilo">'
            .'<span class="dash-weather-hi">'.$todayHigh.'&deg;</span>'
            .'<span class="dash-weather-lo">'.$todayLow.'&deg;</span>'
            .'</div>'
            .'</div>';
        $html .= '<div class="dash-weather-place">'.e($data['resolved_name']).'</div>';

        if ($size !== 'small') {
            $days = $size === 'large' ? 7 : 3;
            $minOfWeek = min($data['daily']['temperature_2m_min']);
            $maxOfWeek = max($data['daily']['temperature_2m_max']);
            $range = max(1, $maxOfWeek - $minOfWeek);

            $html .= '<div class="dash-weather-week">';
            for ($i = 0; $i < $days && $i < count($data['daily']['time']); $i++) {
                $date = Carbon::parse($data['daily']['time'][$i]);
                [$dayType] = self::codeInfo($data['daily']['weather_code'][$i]);
                $hi = round($data['daily']['temperature_2m_max'][$i]);
                $lo = round($data['daily']['temperature_2m_min'][$i]);
                $leftPct = round((($lo - $minOfWeek) / $range) * 100);
                $widthPct = max(6, round((($hi - $lo) / $range) * 100));

                $html .= '<div class="dash-weather-day-row">'
                    .'<span class="dash-weather-day-label">'.e($i === 0 ? __('Today') : $date->format('D')).'</span>'
                    .self::icon($dayType, 22)
                    .'<span class="dash-weather-range-track"><span class="dash-weather-range-fill" style="left:'.$leftPct.'%;width:'.$widthPct.'%"></span></span>'
                    .'<span class="dash-weather-day-lo">'.$lo.'&deg;</span>'
                    .'<span class="dash-weather-day-hi">'.$hi.'&deg;</span>'
                    .'</div>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    private static function fetch($city)
    {
        $cacheKey = 'dashboard_weather_'.md5(mb_strtolower($city));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($city) {
            try {
                $client = new Client(['timeout' => 5]);

                $geoBody = (string) $client->get('https://geocoding-api.open-meteo.com/v1/search', [
                    'query' => ['name' => $city, 'count' => 1, 'language' => 'de', 'format' => 'json'],
                ])->getBody();
                $geo = json_decode($geoBody, true);

                if (empty($geo['results'][0])) {
                    return null;
                }
                $result = $geo['results'][0];

                $weatherBody = (string) $client->get('https://api.open-meteo.com/v1/forecast', [
                    'query' => [
                        'latitude' => $result['latitude'],
                        'longitude' => $result['longitude'],
                        'current' => 'temperature_2m,weather_code',
                        'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
                        'timezone' => 'auto',
                        'forecast_days' => 7,
                    ],
                ])->getBody();
                $weather = json_decode($weatherBody, true);

                if (empty($weather['current']) || empty($weather['daily'])) {
                    return null;
                }

                $weather['resolved_name'] = $result['name'];

                return $weather;
            } catch (\Throwable $e) {
                \Helper::logException($e, 'Dashboard weather fetch failed ('.$city.'): ');

                return null;
            }
        });
    }

    /** WMO weather interpretation codes (the scheme Open-Meteo uses),
     * collapsed to the 7 icon types below plus an English base label
     * routed through __() like every other widget's UI text. */
    private static function codeInfo($code)
    {
        $map = [
            0 => ['sun', 'Clear sky'],
            1 => ['partly', 'Mostly clear'],
            2 => ['partly', 'Partly cloudy'],
            3 => ['cloud', 'Overcast'],
            45 => ['fog', 'Fog'],
            48 => ['fog', 'Fog'],
            51 => ['rain', 'Drizzle'],
            53 => ['rain', 'Drizzle'],
            55 => ['rain', 'Drizzle'],
            56 => ['rain', 'Freezing rain'],
            57 => ['rain', 'Freezing rain'],
            61 => ['rain', 'Rain'],
            63 => ['rain', 'Rain'],
            65 => ['rain', 'Rain'],
            66 => ['rain', 'Freezing rain'],
            67 => ['rain', 'Freezing rain'],
            71 => ['snow', 'Snow'],
            73 => ['snow', 'Snow'],
            75 => ['snow', 'Snow'],
            77 => ['snow', 'Snow'],
            80 => ['rain', 'Rain showers'],
            81 => ['rain', 'Rain showers'],
            82 => ['rain', 'Rain showers'],
            85 => ['snow', 'Snow showers'],
            86 => ['snow', 'Snow showers'],
            95 => ['storm', 'Thunderstorm'],
            96 => ['storm', 'Thunderstorm'],
            99 => ['storm', 'Thunderstorm'],
        ];

        return $map[$code] ?? ['cloud', 'Overcast'];
    }

    /** Small, self-contained inline SVGs — no vendored icon set, no
     * external image requests. Each uses real weather-appropriate colors
     * (not the widget's own accent) since a blue sun or grey rain cloud
     * would read as wrong regardless of the surrounding theme. */
    private static function icon($type, $s)
    {
        $svgs = [
            'sun' => '<circle cx="20" cy="20" r="8" fill="#f5a623"/><g stroke="#f5a623" stroke-width="2" stroke-linecap="round"><line x1="20" y1="2" x2="20" y2="7"/><line x1="20" y1="33" x2="20" y2="38"/><line x1="2" y1="20" x2="7" y2="20"/><line x1="33" y1="20" x2="38" y2="20"/><line x1="6" y1="6" x2="10" y2="10"/><line x1="30" y1="30" x2="34" y2="34"/><line x1="6" y1="34" x2="10" y2="30"/><line x1="30" y1="10" x2="34" y2="6"/></g>',
            'partly' => '<circle cx="15" cy="14" r="7" fill="#f5a623"/><path d="M11 30a6 6 0 0 1 1-11.9A8 8 0 0 1 27 17a6.5 6.5 0 0 1 -1 13H11z" fill="#90a4ae"/>',
            'cloud' => '<path d="M9 27a6.5 6.5 0 0 1 1-12.9A8.5 8.5 0 0 1 26 13a7 7 0 0 1 -1 14H9z" fill="#90a4ae"/>',
            'fog' => '<g stroke="#b0bec5" stroke-width="2.5" stroke-linecap="round"><line x1="6" y1="14" x2="34" y2="14"/><line x1="6" y1="20" x2="34" y2="20"/><line x1="6" y1="26" x2="34" y2="26"/><line x1="6" y1="32" x2="26" y2="32"/></g>',
            'rain' => '<path d="M9 20a6 6 0 0 1 1-11.9A8 8 0 0 1 25 7a6.5 6.5 0 0 1 -1 13H9z" fill="#90a4ae"/><g stroke="#4a90d9" stroke-width="2" stroke-linecap="round"><line x1="13" y1="24" x2="11" y2="32"/><line x1="20" y1="24" x2="18" y2="32"/><line x1="27" y1="24" x2="25" y2="32"/></g>',
            'snow' => '<path d="M9 20a6 6 0 0 1 1-11.9A8 8 0 0 1 25 7a6.5 6.5 0 0 1 -1 13H9z" fill="#90a4ae"/><g fill="#7ec8e3"><circle cx="13" cy="28" r="1.8"/><circle cx="20" cy="32" r="1.8"/><circle cx="27" cy="28" r="1.8"/></g>',
            'storm' => '<path d="M9 18a6 6 0 0 1 1-11.9A8 8 0 0 1 25 5a6.5 6.5 0 0 1 -1 13H9z" fill="#5c6bc0"/><path d="M21 21l-6 10h4l-2 8 7-11h-4z" fill="#f5a623"/>',
        ];
        $inner = $svgs[$type] ?? $svgs['cloud'];

        return '<svg class="dash-weather-icon" viewBox="0 0 40 40" width="'.$s.'" height="'.$s.'">'.$inner.'</svg>';
    }
}
