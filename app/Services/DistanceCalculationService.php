<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DistanceCalculationService
{
    /**
     * Local known distances for city districts / municipalities around Banská Bystrica
     */
    protected const LOCAL_FALLBACKS = [
        'banská bystrica|podlavice' => 6.0,
        'podlavice|banská bystrica' => 6.0,
        'banská bystrica|šalková' => 10.0,
        'šalková|banská bystrica' => 10.0,
        'banská bystrica|radvaň' => 4.0,
        'radvaň|banská bystrica' => 4.0,
        'banská bystrica|sasová' => 5.0,
        'sasová|banská bystrica' => 5.0,
        'banská bystrica|kremnička' => 7.0,
        'kremnička|banská bystrica' => 7.0,
        'banská bystrica|rakytovce' => 8.0,
        'rakytovce|banská bystrica' => 8.0,
    ];

    /**
     * Calculate road distance between two locations in Slovakia.
    /**
     * Calculate road distance between locations in Slovakia.
     * Supports single destination (A -> B) and multi-stop circuits (A -> B -> C -> A).
     * Primary source: vzdialenosti.sk
     * Fallback: OSRM OpenStreetMap routing or local district table.
     */
    public function getDistance(string $from, string $to): array
    {
        $from = trim($from);
        $to = trim($to);

        if (empty($from) || empty($to)) {
            return [
                'success' => false,
                'distance_km' => 0.0,
                'round_trip_km' => 0.0,
                'source' => 'empty_input',
            ];
        }

        // Check for multiple destinations separated by comma, semicolon, plus, or arrows
        $rawDests = preg_split('/[,;+]+|\s+(?:->|–>|→|&rarr;)\s+/', $to);
        $destinations = array_values(array_filter(array_map('trim', $rawDests)));

        if (count($destinations) > 1) {
            return $this->getMultiStopDistance($from, $destinations);
        }

        return $this->getSingleSegmentDistance($from, $to);
    }

    /**
     * Calculate multi-stop circuit distance (A -> B -> C -> ... -> A)
     */
    public function getMultiStopDistance(string $from, array $destinations): array
    {
        $cleanFrom = $this->cleanLocationName($from);
        $cleanDests = array_values(array_filter(array_map([$this, 'cleanLocationName'], $destinations)));

        if (empty($cleanDests)) {
            return [
                'success' => false,
                'distance_km' => 0.0,
                'round_trip_km' => 0.0,
                'source' => 'empty_input',
            ];
        }

        if (count($cleanDests) === 1) {
            return $this->getSingleSegmentDistance($cleanFrom, $cleanDests[0]);
        }

        $totalOneWayKm = 0.0;
        $current = $cleanFrom;
        $segments = [];

        foreach ($cleanDests as $dest) {
            $seg = $this->getSingleSegmentDistance($current, $dest);
            $dist = $seg['success'] ? (float)$seg['distance_km'] : 0.0;
            $totalOneWayKm += $dist;
            $segments[] = [
                'from' => $current,
                'to' => $dest,
                'km' => $dist,
                'source' => $seg['source'] ?? 'unknown',
            ];
            $current = $dest;
        }

        // Return leg from last destination back to base
        $returnSeg = $this->getSingleSegmentDistance($current, $cleanFrom);
        $returnKm = $returnSeg['success'] ? (float)$returnSeg['distance_km'] : 0.0;
        $totalRoundTripKm = $totalOneWayKm + $returnKm;

        return [
            'success' => true,
            'from' => $cleanFrom,
            'to' => implode(', ', $cleanDests),
            'is_multi_stop' => true,
            'stop_count' => count($cleanDests),
            'distance_km' => round($totalOneWayKm, 1),
            'round_trip_km' => round($totalRoundTripKm, 1),
            'source' => 'vzdialenosti.sk (okružná trasa)',
            'segments' => $segments,
        ];
    }

    /**
     * Calculate distance for a single segment (from -> to)
     */
    public function getSingleSegmentDistance(string $from, string $to): array
    {
        $cleanFrom = $this->cleanLocationName($from);
        $cleanTo = $this->cleanLocationName($to);

        if (mb_strtolower($cleanFrom) === mb_strtolower($cleanTo)) {
            return [
                'success' => true,
                'from' => $cleanFrom,
                'to' => $cleanTo,
                'distance_km' => 0.0,
                'round_trip_km' => 0.0,
                'source' => 'same_location',
            ];
        }

        // Check cache (30 days)
        $cacheKey = 'dist_' . md5(mb_strtolower($cleanFrom) . '|' . mb_strtolower($cleanTo));
        return Cache::remember($cacheKey, 60 * 24 * 30, function () use ($cleanFrom, $cleanTo) {
            // 1. Try pulling directly from vzdialenosti.sk
            $vzdialenostiResult = $this->fetchFromVzdialenostiSk($cleanFrom, $cleanTo);
            if ($vzdialenostiResult !== null) {
                return [
                    'success' => true,
                    'from' => $cleanFrom,
                    'to' => $cleanTo,
                    'distance_km' => $vzdialenostiResult,
                    'round_trip_km' => round($vzdialenostiResult * 2, 1),
                    'source' => 'vzdialenosti.sk',
                ];
            }

            // 2. Try local suburb / district table
            $pairKey = mb_strtolower($cleanFrom) . '|' . mb_strtolower($cleanTo);
            if (isset(self::LOCAL_FALLBACKS[$pairKey])) {
                $km = self::LOCAL_FALLBACKS[$pairKey];
                return [
                    'success' => true,
                    'from' => $cleanFrom,
                    'to' => $cleanTo,
                    'distance_km' => $km,
                    'round_trip_km' => round($km * 2, 1),
                    'source' => 'local_matrix',
                ];
            }

            // 3. Fallback to OpenStreetMap / OSRM routing
            $osrmResult = $this->fetchFromOsrm($cleanFrom, $cleanTo);
            if ($osrmResult !== null) {
                return [
                    'success' => true,
                    'from' => $cleanFrom,
                    'to' => $cleanTo,
                    'distance_km' => $osrmResult,
                    'round_trip_km' => round($osrmResult * 2, 1),
                    'source' => 'osrm_routing',
                ];
            }

            return [
                'success' => false,
                'from' => $cleanFrom,
                'to' => $cleanTo,
                'distance_km' => 0.0,
                'round_trip_km' => 0.0,
                'source' => 'unknown',
            ];
        });
    }

    /**
     * Clean location strings (strip dashes, hospital prefixes, etc.)
     */
    protected function cleanLocationName(string $name): string
    {
        // If string contains '-' or '(', take first part
        $parts = preg_split('/[\-\(]/', $name);
        $clean = trim($parts[0]);
        // Remove common keywords like 'Nemocnica', 'RÚVZ', 'MÚ'
        $clean = preg_replace('/^(Nemocnica|RÚVZ|MÚ|ÚPSVaR|ZŠ)\s+/i', '', $clean);
        return trim($clean);
    }

    /**
     * Direct extraction from vzdialenosti.sk
     */
    protected function fetchFromVzdialenostiSk(string $from, string $to): ?float
    {
        try {
            // Suggestion resolver for vzdialenosti.sk
            $resolve = function ($place) {
                $ch = curl_init("https://www.vzdialenosti.sk/ajax.php?field=start&search=" . urlencode($place));
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    CURLOPT_TIMEOUT => 4,
                ]);
                $res = curl_exec($ch);
                curl_close($ch);

                if ($res && strpos($res, '|') !== false) {
                    $parts = explode('|', $res);
                    if (!empty($parts[1])) {
                        $lines = explode("\n", trim($parts[1]));
                        foreach ($lines as $line) {
                            $item = explode('@', $line);
                            if (isset($item[1]) && !empty($item[1])) {
                                return trim($item[1]);
                            }
                        }
                    }
                }
                return $place;
            };

            $startStr = $resolve($from);
            $finishStr = $resolve($to);

            // POST to www.vzdialenosti.sk
            $ch = curl_init("https://www.vzdialenosti.sk/");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'start' => $startStr,
                    'finish' => $finishStr,
                    'search' => '1',
                ]),
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                CURLOPT_TIMEOUT => 5,
            ]);
            $html = curl_exec($ch);
            curl_close($ch);

            if (!$html) {
                return null;
            }

            // Extract coordinates from JavaScript inside vzdialenosti.sk HTML
            if (preg_match('/coordsStart\s*=\s*\[([0-9\.]+),\s*([0-9\.]+)\]/i', $html, $mStart) &&
                preg_match('/coordsFinish\s*=\s*\[([0-9\.]+),\s*([0-9\.]+)\]/i', $html, $mFinish)) {

                $lon1 = $mStart[1]; $lat1 = $mStart[2];
                $lon2 = $mFinish[1]; $lat2 = $mFinish[2];

                // Query the exact Mapy.cz routing engine that vzdialenosti.sk uses
                $routeUrl = "https://api.mapy.cz/v1/routing/route?apikey=mZtBu-WKgxWGxw2ZVoP-jpLhKrXuCDvAJsH-gM_2wQg&lang=cs&start={$lon1},{$lat1}&end={$lon2},{$lat2}&routeType=car_fast";
                $chRoute = curl_init($routeUrl);
                curl_setopt_array($chRoute, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HTTPHEADER => [
                        'Origin: https://www.vzdialenosti.sk',
                        'Referer: https://www.vzdialenosti.sk/',
                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    ],
                    CURLOPT_TIMEOUT => 4,
                ]);
                $routeJson = curl_exec($chRoute);
                curl_close($chRoute);

                $data = json_decode($routeJson, true);
                if (isset($data['parts'][0]['length'])) {
                    return round($data['parts'][0]['length'] / 1000, 1);
                }
                if (isset($data['length'])) {
                    return round($data['length'] / 1000, 1);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Error fetching from vzdialenosti.sk: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Fallback routing via OSRM / OpenStreetMap
     */
    protected function fetchFromOsrm(string $from, string $to): ?float
    {
        try {
            $getCoords = function ($city) {
                $url = "https://nominatim.openstreetmap.org/search?format=json&countrycodes=sk&limit=1&q=" . urlencode($city . ', Slovensko');
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => 'KAPZ-Appka/1.0',
                    CURLOPT_TIMEOUT => 4,
                ]);
                $res = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($res, true);
                if (!empty($data[0]['lat'])) {
                    return ['lat' => (float)$data[0]['lat'], 'lon' => (float)$data[0]['lon']];
                }
                return null;
            };

            $c1 = $getCoords($from);
            $c2 = $getCoords($to);

            if ($c1 && $c2) {
                $osrmUrl = "https://router.project-osrm.org/route/v1/driving/{$c1['lon']},{$c1['lat']};{$c2['lon']},{$c2['lat']}?overview=false";
                $ch = curl_init($osrmUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => 'KAPZ-Appka/1.0',
                    CURLOPT_TIMEOUT => 4,
                ]);
                $res = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($res, true);
                if (!empty($data['routes'][0]['distance'])) {
                    return round($data['routes'][0]['distance'] / 1000, 1);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Error fetching from OSRM: " . $e->getMessage());
        }

        return null;
    }
}
