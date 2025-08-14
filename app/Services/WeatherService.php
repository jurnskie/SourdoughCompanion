<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private const CACHE_DURATION = 1800; // 30 minutes
    private const OPENMETEO_API = 'https://api.open-meteo.com/v1/forecast';
    private const OPENWEATHER_API = 'https://api.openweathermap.org/data/2.5/weather';
    private const GEOLOCATION_API = 'http://ip-api.com/json';

    public function getCurrentWeather(?string $location = null, ?float $lat = null, ?float $lon = null): array
    {
        $cacheKey = 'weather_' . md5($location . $lat . $lon);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($location, $lat, $lon) {
            // Get coordinates
            $coordinates = $this->getCoordinates($location, $lat, $lon);
            
            if (!$coordinates) {
                return $this->getDefaultWeather();
            }
            
            // Try Open-Meteo first (free, no API key required)
            $weather = $this->fetchFromOpenMeteo($coordinates['lat'], $coordinates['lon']);
            
            if (!$weather) {
                // Fallback to OpenWeatherMap if API key is available
                $weather = $this->fetchFromOpenWeather($coordinates['lat'], $coordinates['lon']);
            }
            
            return $weather ?: $this->getDefaultWeather();
        });
    }

    private function getCoordinates(?string $location, ?float $lat, ?float $lon): ?array
    {
        // Use provided coordinates if available
        if ($lat && $lon) {
            return ['lat' => $lat, 'lon' => $lon, 'location' => $location ?: 'Custom Location'];
        }
        
        // Geocode location if provided
        if ($location) {
            return $this->geocodeLocation($location);
        }
        
        // Auto-detect location via IP
        return $this->detectLocationByIP();
    }

    private function geocodeLocation(string $location): ?array
    {
        try {
            // Use Open-Meteo geocoding API
            $response = Http::timeout(10)->get('https://geocoding-api.open-meteo.com/v1/search', [
                'name' => $location,
                'count' => 1,
                'language' => 'en',
                'format' => 'json'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['results'])) {
                    $result = $data['results'][0];
                    return [
                        'lat' => $result['latitude'],
                        'lon' => $result['longitude'],
                        'location' => $result['name'] . ', ' . ($result['country'] ?? '')
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Geocoding failed', ['location' => $location, 'error' => $e->getMessage()]);
        }

        return null;
    }

    private function detectLocationByIP(): ?array
    {
        try {
            $response = Http::timeout(10)->get(self::GEOLOCATION_API);
            
            if ($response->successful()) {
                $data = $response->json();
                if ($data['status'] === 'success') {
                    return [
                        'lat' => $data['lat'],
                        'lon' => $data['lon'],
                        'location' => $data['city'] . ', ' . $data['country']
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('IP geolocation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function fetchFromOpenMeteo(float $lat, float $lon): ?array
    {
        try {
            $response = Http::timeout(15)->get(self::OPENMETEO_API, [
                'latitude' => $lat,
                'longitude' => $lon,
                'current' => 'temperature_2m,relative_humidity_2m',
                'timezone' => 'auto'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $current = $data['current'] ?? [];
                
                return [
                    'temperature' => round($current['temperature_2m'] ?? 22),
                    'humidity' => $current['relative_humidity_2m'] ?? 50,
                    'location' => null, // Will be filled by caller
                    'source' => 'Open-Meteo',
                    'updated_at' => now()->toISOString(),
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Open-Meteo API failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function fetchFromOpenWeather(float $lat, float $lon): ?array
    {
        $apiKey = config('services.openweather.key');
        
        if (!$apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(15)->get(self::OPENWEATHER_API, [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $apiKey,
                'units' => 'metric'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'temperature' => round($data['main']['temp'] ?? 22),
                    'humidity' => $data['main']['humidity'] ?? 50,
                    'location' => null, // Will be filled by caller
                    'source' => 'OpenWeatherMap',
                    'updated_at' => now()->toISOString(),
                ];
            }
        } catch (\Exception $e) {
            Log::warning('OpenWeatherMap API failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function getDefaultWeather(): array
    {
        return [
            'temperature' => 22,
            'humidity' => 50,
            'location' => 'Default (Kitchen)',
            'source' => 'Manual Input',
            'updated_at' => now()->toISOString(),
        ];
    }

    public function getHumidityLevel(int $humidity): string
    {
        if ($humidity < 40) {
            return 'dry';
        } elseif ($humidity > 70) {
            return 'humid';
        }
        
        return 'normal';
    }

    public function clearCache(): void
    {
        Cache::forget('weather_*');
    }
}