<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

class AuditLogger
{
    /**
     * Log an audit event
     */
    public static function log(array $data)
    {
        try {
            $agent = new Agent();

            // Get authenticated user safely
            $user = auth()->user();

            /**
             * IMPORTANT:
             * Use application-level user_id (STRING),
             * NOT database primary key id (BIGINT)
             */
            $userId = $user ? $user->user_id : ($data['user_id'] ?? null);

            // Parse user agent
            $userAgent = Request::header('User-Agent');

            AuditLog::create([
                'user_id'     => $userId,
                'user_name'   => $user ? $user->name : ($data['user_name'] ?? 'Guest'),
                'user_email'  => $user ? self::maskEmail($user->email) : null,
                'event_type'  => $data['event_type'] ?? 'general',
                'action'      => $data['action'],
                'description' => $data['description'] ?? null,
                'ip_address'  => self::maskIP(Request::ip()),
                'user_agent'  => substr($userAgent, 0, 500),
                'device_type' => $agent->isDesktop()
                                    ? 'desktop'
                                    : ($agent->isMobile() ? 'mobile' : 'tablet'),
                'browser'     => $agent->browser(),
                'platform'    => $agent->platform(),
                'severity'    => $data['severity'] ?? 'low',
                'status'      => $data['status'] ?? 'success',
                'metadata'    => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            ]);

        } catch (\Exception $e) {
            \Log::error('Audit logging failed: ' . $e->getMessage());
        }
    }

    /**
     * Log login attempt
     * $userId MUST be users.user_id (STRING)
     */
    public static function logLogin($userId, $success = true, $reason = null)
    {
        self::log([
            'user_id'    => $userId,
            'event_type' => 'authentication',
            'action'     => $success ? 'login_success' : 'login_failed',
            'description'=> $success
                ? 'User logged in successfully'
                : 'Failed login attempt: ' . ($reason ?? 'Invalid credentials'),
            'severity'   => $success ? 'low' : 'medium',
            'status'     => $success ? 'success' : 'failed',
            'user_name'  => $userId,
            'metadata'   => [
                'reason'    => $reason,
                'timestamp' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Log logout
     */
    public static function logLogout()
    {
        self::log([
            'event_type' => 'authentication',
            'action'     => 'logout_success',
            'description'=> 'User logged out successfully',
            'severity'   => 'low',
            'status'     => 'success',
        ]);
    }

    /**
     * Log MFA events
     */
    public static function logMFA($action, $success = true, $reason = null)
    {
        self::log([
            'event_type' => 'authentication',
            'action'     => "mfa_{$action}",
            'description'=> $success
                ? "MFA {$action} successful"
                : "MFA {$action} failed: " . ($reason ?? 'Invalid OTP'),
            'severity'   => $success ? 'low' : 'high',
            'status'     => $success ? 'success' : 'failed',
            'metadata'   => [
                'mfa_action' => $action,
                'reason'     => $reason
            ]
        ]);
    }

    /**
     * Log user management actions
     */
    public static function logUserManagement($action, $targetUserId, $targetUserName)
    {
        self::log([
            'event_type' => 'user_management',
            'action'     => $action,
            'description'=> "User {$action}: {$targetUserName}",
            'severity'   => in_array($action, ['user_deleted', 'user_deactivated']) ? 'high' : 'medium',
            'status'     => 'success',
            'metadata'   => [
                'target_user_id'   => $targetUserId,
                'target_user_name' => $targetUserName
            ]
        ]);
    }

    /**
     * Log suspicious activity
     */
    public static function logSuspicious($description, $metadata = [])
    {
        self::log([
            'event_type' => 'security',
            'action'     => 'suspicious_activity',
            'description'=> $description,
            'severity'   => 'high',
            'status'     => 'suspicious',
            'metadata'   => $metadata
        ]);
    }

    /**
     * Log critical security event
     */
    public static function logCritical($action, $description, $metadata = [])
    {
        self::log([
            'event_type' => 'security',
            'action'     => $action,
            'description'=> $description,
            'severity'   => 'critical',
            'status'     => 'blocked',
            'metadata'   => $metadata
        ]);
    }

    /**
     * Log admin action
     */
    public static function logAdminAction($action, $description, $metadata = [])
    {
        self::log([
            'event_type' => 'admin',
            'action'     => $action,
            'description'=> $description,
            'severity'   => 'medium',
            'status'     => 'success',
            'metadata'   => $metadata
        ]);
    }

    /**
     * Mask email (OWASP)
     */
    private static function maskEmail($email)
    {
        if (!$email) return null;

        [$name, $domain] = array_pad(explode('@', $email), 2, '');

        $maskedName = strlen($name) <= 2
            ? str_repeat('*', strlen($name))
            : substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);

        return $maskedName . '@' . $domain;
    }

    /**
     * Mask IP (OWASP)
     */
    private static function maskIP($ip)
    {
        if (!$ip) return null;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = 'xxx';
            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $parts[count($parts) - 1] = 'xxxx';
            return implode(':', $parts);
        }

        return $ip;
    }

    /**
     * Retention policy
     */
    public static function cleanOldLogs($days = 90)
    {
        try {
            AuditLog::where('created_at', '<', now()->subDays($days))
                ->where('severity', '!=', 'critical')
                ->delete();

            \Log::info("Cleaned audit logs older than {$days} days");
        } catch (\Exception $e) {
            \Log::error('Failed to clean audit logs: ' . $e->getMessage());
        }
    }
}
