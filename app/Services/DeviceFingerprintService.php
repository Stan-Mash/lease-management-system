<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Device Fingerprinting Service
 *
 * Provides server-side device fingerprinting capabilities for security purposes.
 * Used primarily for OTP/signature verification to detect suspicious activity.
 */
class DeviceFingerprintService
{
    /**
     * Generate a device fingerprint from request headers and attributes.
     */
    public static function generate(?Request $request = null): array
    {
        $request = $request ?? request();

        $fingerprint = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'accept_language' => $request->header('Accept-Language'),
            'accept_encoding' => $request->header('Accept-Encoding'),
            'connection' => $request->header('Connection'),
            'cache_control' => $request->header('Cache-Control'),
            'sec_ch_ua' => $request->header('Sec-CH-UA'),
            'sec_ch_ua_mobile' => $request->header('Sec-CH-UA-Mobile'),
            'sec_ch_ua_platform' => $request->header('Sec-CH-UA-Platform'),
            'sec_fetch_site' => $request->header('Sec-Fetch-Site'),
            'sec_fetch_mode' => $request->header('Sec-Fetch-Mode'),
            'sec_fetch_dest' => $request->header('Sec-Fetch-Dest'),
            'dnt' => $request->header('DNT'),
            'collected_at' => now()->toIso8601String(),
        ];

        // Generate hash from stable attributes
        $fingerprint['hash'] = self::generateHash($fingerprint);

        // Parse user agent for device info
        $fingerprint['device_info'] = self::parseUserAgent($fingerprint['user_agent']);

        return $fingerprint;
    }

    /**
     * Generate a consistent hash from fingerprint data.
     */
    public static function generateHash(array $fingerprint): string
    {
        // Use only stable attributes for hashing (exclude timestamps)
        $stableAttributes = [
            $fingerprint['user_agent'] ?? '',
            $fingerprint['accept_language'] ?? '',
            $fingerprint['accept_encoding'] ?? '',
            $fingerprint['sec_ch_ua'] ?? '',
            $fingerprint['sec_ch_ua_platform'] ?? '',
        ];

        return hash('sha256', implode('|', $stableAttributes));
    }

    /**
     * Compare two fingerprints and return a similarity score (0-100).
     */
    public static function compare(array $fingerprint1, array $fingerprint2): int
    {
        $score = 0;
        $maxScore = 100;

        // IP address match (30 points)
        if (($fingerprint1['ip_address'] ?? null) === ($fingerprint2['ip_address'] ?? null)) {
            $score += 30;
        } elseif (self::isSameSubnet($fingerprint1['ip_address'] ?? '', $fingerprint2['ip_address'] ?? '')) {
            $score += 15; // Same subnet gets partial points
        }

        // User agent match (25 points)
        if (($fingerprint1['user_agent'] ?? null) === ($fingerprint2['user_agent'] ?? null)) {
            $score += 25;
        } elseif (self::isSimilarUserAgent($fingerprint1['user_agent'] ?? '', $fingerprint2['user_agent'] ?? '')) {
            $score += 10; // Similar UA gets partial points
        }

        // Language match (10 points)
        if (($fingerprint1['accept_language'] ?? null) === ($fingerprint2['accept_language'] ?? null)) {
            $score += 10;
        }

        // Platform match (15 points)
        if (($fingerprint1['sec_ch_ua_platform'] ?? null) === ($fingerprint2['sec_ch_ua_platform'] ?? null)) {
            $score += 15;
        }

        // Browser identity match (10 points)
        if (($fingerprint1['sec_ch_ua'] ?? null) === ($fingerprint2['sec_ch_ua'] ?? null)) {
            $score += 10;
        }

        // Mobile indicator match (5 points)
        if (($fingerprint1['sec_ch_ua_mobile'] ?? null) === ($fingerprint2['sec_ch_ua_mobile'] ?? null)) {
            $score += 5;
        }

        // Hash match (5 bonus points)
        if (($fingerprint1['hash'] ?? null) === ($fingerprint2['hash'] ?? null)) {
            $score += 5;
        }

        return min($score, $maxScore);
    }

    /**
     * Check if a fingerprint is suspicious based on velocity and patterns.
     */
    public static function isSuspicious(array $fingerprint, string $context = 'otp'): array
    {
        $suspicious = false;
        $reasons = [];
        $riskScore = 0;

        $ip = $fingerprint['ip_address'] ?? '';
        $hash = $fingerprint['hash'] ?? '';

        // Check 1: IP velocity (too many requests from same IP)
        $ipKey = "fingerprint:ip:{$context}:{$ip}";
        $ipCount = (int) Cache::get($ipKey, 0);

        if ($ipCount > 10) {
            $suspicious = true;
            $reasons[] = 'High velocity from same IP';
            $riskScore += 30;
        } elseif ($ipCount > 5) {
            $reasons[] = 'Moderate velocity from same IP';
            $riskScore += 15;
        }

        // Increment IP counter (1 hour window)
        Cache::put($ipKey, $ipCount + 1, now()->addHour());

        // Check 2: Device velocity (too many requests from same device)
        $deviceKey = "fingerprint:device:{$context}:{$hash}";
        $deviceCount = (int) Cache::get($deviceKey, 0);

        if ($deviceCount > 15) {
            $suspicious = true;
            $reasons[] = 'High velocity from same device';
            $riskScore += 25;
        } elseif ($deviceCount > 8) {
            $reasons[] = 'Moderate velocity from same device';
            $riskScore += 10;
        }

        // Increment device counter (1 hour window)
        Cache::put($deviceKey, $deviceCount + 1, now()->addHour());

        // Check 3: Known proxy/VPN detection (basic)
        if (self::isPossibleProxy($ip)) {
            $reasons[] = 'Possible proxy or VPN detected';
            $riskScore += 20;
        }

        // Check 4: Missing expected headers (bot detection)
        if (empty($fingerprint['user_agent'])) {
            $suspicious = true;
            $reasons[] = 'Missing user agent';
            $riskScore += 40;
        }

        if (empty($fingerprint['accept_language'])) {
            $reasons[] = 'Missing accept-language header';
            $riskScore += 10;
        }

        // Check 5: Suspicious user agent patterns
        $ua = strtolower($fingerprint['user_agent'] ?? '');
        if (self::isSuspiciousUserAgent($ua)) {
            $suspicious = true;
            $reasons[] = 'Suspicious user agent pattern';
            $riskScore += 35;
        }

        return [
            'is_suspicious' => $suspicious || $riskScore >= 50,
            'risk_score' => min($riskScore, 100),
            'reasons' => $reasons,
            'ip_velocity' => $ipCount + 1,
            'device_velocity' => $deviceCount + 1,
        ];
    }

    /**
     * Store fingerprint for a specific entity (e.g., OTP verification).
     */
    public static function store(string $entityType, int $entityId, array $fingerprint): void
    {
        $key = "fingerprint:{$entityType}:{$entityId}";
        Cache::put($key, $fingerprint, now()->addDays(30));
    }

    /**
     * Retrieve stored fingerprint for an entity.
     */
    public static function retrieve(string $entityType, int $entityId): ?array
    {
        $key = "fingerprint:{$entityType}:{$entityId}";
        return Cache::get($key);
    }

    /**
     * Parse user agent string to extract device information.
     */
    protected static function parseUserAgent(?string $userAgent): array
    {
        if (empty($userAgent)) {
            return ['device' => 'unknown', 'os' => 'unknown', 'browser' => 'unknown'];
        }

        $ua = strtolower($userAgent);

        // Detect device type
        $device = 'desktop';
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') && !str_contains($ua, 'tablet')) {
            $device = 'mobile';
        } elseif (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            $device = 'tablet';
        }

        // Detect OS
        $os = 'unknown';
        if (str_contains($ua, 'windows')) {
            $os = 'windows';
        } elseif (str_contains($ua, 'mac os') || str_contains($ua, 'macintosh')) {
            $os = 'macos';
        } elseif (str_contains($ua, 'linux')) {
            $os = 'linux';
        } elseif (str_contains($ua, 'android')) {
            $os = 'android';
        } elseif (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ios')) {
            $os = 'ios';
        }

        // Detect browser
        $browser = 'unknown';
        if (str_contains($ua, 'chrome') && !str_contains($ua, 'edg')) {
            $browser = 'chrome';
        } elseif (str_contains($ua, 'firefox')) {
            $browser = 'firefox';
        } elseif (str_contains($ua, 'safari') && !str_contains($ua, 'chrome')) {
            $browser = 'safari';
        } elseif (str_contains($ua, 'edg')) {
            $browser = 'edge';
        } elseif (str_contains($ua, 'opera') || str_contains($ua, 'opr')) {
            $browser = 'opera';
        }

        return [
            'device' => $device,
            'os' => $os,
            'browser' => $browser,
        ];
    }

    /**
     * Check if two IPs are in the same /24 subnet.
     */
    protected static function isSameSubnet(string $ip1, string $ip2): bool
    {
        if (empty($ip1) || empty($ip2)) {
            return false;
        }

        $parts1 = explode('.', $ip1);
        $parts2 = explode('.', $ip2);

        if (count($parts1) !== 4 || count($parts2) !== 4) {
            return false;
        }

        return $parts1[0] === $parts2[0] &&
               $parts1[1] === $parts2[1] &&
               $parts1[2] === $parts2[2];
    }

    /**
     * Check if user agents are similar (same browser/OS).
     */
    protected static function isSimilarUserAgent(string $ua1, string $ua2): bool
    {
        $info1 = self::parseUserAgent($ua1);
        $info2 = self::parseUserAgent($ua2);

        return $info1['browser'] === $info2['browser'] && $info1['os'] === $info2['os'];
    }

    /**
     * Basic check for possible proxy/VPN (common datacenter IPs).
     */
    protected static function isPossibleProxy(string $ip): bool
    {
        // This is a simplified check - in production, use a proper IP intelligence service
        $suspiciousRanges = [
            '10.', '172.16.', '172.17.', '172.18.', '172.19.',
            '172.20.', '172.21.', '172.22.', '172.23.', '172.24.',
            '172.25.', '172.26.', '172.27.', '172.28.', '172.29.',
            '172.30.', '172.31.', '192.168.',
        ];

        foreach ($suspiciousRanges as $range) {
            if (str_starts_with($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for suspicious user agent patterns (bots, scrapers).
     */
    protected static function isSuspiciousUserAgent(string $ua): bool
    {
        $suspiciousPatterns = [
            'curl', 'wget', 'python', 'java/', 'libwww',
            'httpclient', 'bot', 'crawler', 'spider', 'scraper',
            'headless', 'phantom', 'selenium', 'puppeteer',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($ua, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
