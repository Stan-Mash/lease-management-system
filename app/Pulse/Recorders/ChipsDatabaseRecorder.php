<?php

declare(strict_types=1);

namespace App\Pulse\Recorders;

use Exception;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Pulse\Events\SharedBeat;
use Laravel\Pulse\Pulse;

/**
 * Records CHIPS database connection health for Pulse monitoring.
 *
 * Performs a simple SELECT 1 query on the read-only chips_db connection
 * to verify connectivity. Records status as Connected or Down.
 */
class ChipsDatabaseRecorder
{
    /**
     * The events to listen for.
     *
     * @var class-string
     */
    public string $listen = SharedBeat::class;

    /**
     * The database connection name for CHIPS.
     */
    protected string $connectionName;

    /**
     * Query timeout in seconds.
     */
    protected int $timeout;

    public function __construct(
        protected Pulse $pulse,
        protected Repository $config,
    ) {
        $this->connectionName = $config->get(
            'pulse.recorders.' . self::class . '.connection',
            'chips_db',
        );
        $this->timeout = (int) $config->get(
            'pulse.recorders.' . self::class . '.timeout',
            5,
        );
    }

    /**
     * Record the CHIPS database health on each shared beat.
     */
    public function record(SharedBeat $event): void
    {
        // Check every 15 seconds (on 0, 15, 30, 45 second marks)
        if ($event->time->second % 15 !== 0) {
            return;
        }

        $startTime = microtime(true);

        try {
            // Check if the connection is configured
            if (! $this->isConnectionConfigured()) {
                $this->recordStatus(
                    event: $event,
                    status: 'not_configured',
                    message: "Database connection '{$this->connectionName}' is not configured",
                    responseTime: 0,
                );

                return;
            }

            // Attempt a simple query with timeout
            $result = $this->performHealthCheck();
            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to ms

            if ($result) {
                $this->recordStatus(
                    event: $event,
                    status: 'connected',
                    message: 'CHIPS database is connected',
                    responseTime: $responseTime,
                );
            } else {
                $this->recordStatus(
                    event: $event,
                    status: 'down',
                    message: 'CHIPS database query returned unexpected result',
                    responseTime: $responseTime,
                );
            }

        } catch (Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;

            Log::error('CHIPS database health check failed', [
                'connection' => $this->connectionName,
                'error' => $e->getMessage(),
            ]);

            $this->recordStatus(
                event: $event,
                status: 'down',
                message: $this->sanitizeErrorMessage($e->getMessage()),
                responseTime: $responseTime,
            );
        }
    }

    /**
     * Record the status to Pulse storage.
     */
    protected function recordStatus(
        SharedBeat $event,
        string $status,
        string $message,
        float $responseTime,
    ): void {
        // Record response time for graphing
        $this->pulse->record(
            type: 'chips_response_time',
            key: $this->connectionName,
            value: (int) $responseTime,
            timestamp: $event->time,
        )->avg()->onlyBuckets();

        // Record status (1 = connected, 0 = down)
        $this->pulse->record(
            type: 'chips_status',
            key: $this->connectionName,
            value: $status === 'connected' ? 1 : 0,
            timestamp: $event->time,
        )->max()->onlyBuckets();

        // Store detailed status for card display
        $this->pulse->set(
            type: 'chips_health',
            key: $this->connectionName,
            value: json_encode([
                'status' => $status,
                'message' => $message,
                'response_time_ms' => round($responseTime, 2),
                'connection' => $this->connectionName,
                'checked_at' => $event->time->toIso8601String(),
            ]),
            timestamp: $event->time,
        );

        // Log if database is down
        if ($status === 'down') {
            Log::warning('CHIPS database is down', [
                'connection' => $this->connectionName,
                'message' => $message,
            ]);
        }
    }

    /**
     * Perform the health check query.
     */
    protected function performHealthCheck(): bool
    {
        // Set a statement timeout for the query
        $connection = DB::connection($this->connectionName);

        // For PostgreSQL, set statement_timeout
        if ($connection->getDriverName() === 'pgsql') {
            $connection->statement("SET statement_timeout = '{$this->timeout}s'");
        }

        // Simple connectivity check
        $result = $connection->selectOne('SELECT 1 as health_check');

        return $result && property_exists($result, 'health_check') && $result->health_check === 1;
    }

    /**
     * Check if the database connection is configured.
     */
    protected function isConnectionConfigured(): bool
    {
        $connections = config('database.connections', []);

        return isset($connections[$this->connectionName]);
    }

    /**
     * Sanitize error message to remove sensitive information.
     */
    protected function sanitizeErrorMessage(string $message): string
    {
        // Remove potential credentials or sensitive details
        $patterns = [
            '/password["\']?\s*[=:]\s*["\']?[^"\'\s]+["\']?/i' => 'password=***',
            '/host["\']?\s*[=:]\s*["\']?[^"\'\s]+["\']?/i' => 'host=***',
            '/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/' => '***.***.***.***',
        ];

        $sanitized = preg_replace(
            array_keys($patterns),
            array_values($patterns),
            $message,
        );

        // Truncate if too long
        return mb_substr($sanitized ?? $message, 0, 200);
    }
}
