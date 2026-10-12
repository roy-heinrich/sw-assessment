<?php
declare(strict_types=1);

namespace App;

use PDO;

class Database
{
    private static ?PDO $instance = null;
    private static bool $schemaInitialized = false;

    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? '');
        if ($databaseUrl) {
            self::$instance = self::connectNeonUrl($databaseUrl);
            self::ensureSoftDeleteColumn(self::$instance, 'pgsql');
        } else {
            $dbPath = sys_get_temp_dir() . '/southwest_tours.sqlite';
            self::$instance = new PDO("sqlite:{$dbPath}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            if (!self::$schemaInitialized) {
                self::initSchema(self::$instance);
                self::ensureSoftDeleteColumn(self::$instance, 'sqlite');
                self::$schemaInitialized = true;
            }
        }

        return self::$instance;
    }

    public static function getDriverName(): string
    {
        return self::getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME) ?? 'unknown';
    }

    private static function connectNeonUrl(string $url): PDO
    {
        $parts = parse_url($url);
        if (!$parts || !isset($parts['host'])) {
            throw new \InvalidArgumentException("Invalid DATABASE_URL");
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? 5432;
        $user = $parts['user'] ?? '';
        $pass = $parts['pass'] ?? '';
        $dbName = ltrim($parts['path'] ?? '', '/') ?: 'neondb';

        parse_str($parts['query'] ?? '', $query);

        // Strip channel_binding if present (can cause TLS handshake negotiation delay)
        unset($query['channel_binding']);
        $sslmode = $query['sslmode'] ?? 'require';

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbName};sslmode={$sslmode}";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    }

    /**
     * Soft delete was added after the table already existed, and CREATE TABLE IF NOT EXISTS
     * will not add a column to an existing table. Patch it in; safe and cheap to repeat.
     */
    private static function ensureSoftDeleteColumn(PDO $pdo, string $driver): void
    {
        if ($driver === 'pgsql') {
            // A read avoids taking a write lock on every request the way ALTER ... IF NOT EXISTS would.
            $exists = $pdo->query(
                "SELECT 1 FROM information_schema.columns WHERE table_name = 'bookings' AND column_name = 'deleted_at'"
            )->fetchColumn();
            if (!$exists) {
                $pdo->exec("ALTER TABLE bookings ADD COLUMN deleted_at TIMESTAMP NULL");
            }
            return;
        }

        // SQLite has no ADD COLUMN IF NOT EXISTS, so check first.
        foreach ($pdo->query("PRAGMA table_info(bookings)")->fetchAll(PDO::FETCH_ASSOC) as $col) {
            if (($col['name'] ?? '') === 'deleted_at') {
                return;
            }
        }
        $pdo->exec("ALTER TABLE bookings ADD COLUMN deleted_at TEXT NULL");
    }

    private static function initSchema(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $sql = "
                CREATE TABLE IF NOT EXISTS bookings (
                    id SERIAL PRIMARY KEY,
                    guest_name VARCHAR(255) NOT NULL,
                    contact_number VARCHAR(50) NOT NULL,
                    service_type VARCHAR(100) NOT NULL,
                    pickup_point VARCHAR(255) NOT NULL,
                    dropoff_point VARCHAR(255) NOT NULL,
                    travel_date DATE NOT NULL,
                    passengers INT NOT NULL DEFAULT 1,
                    status VARCHAR(50) NOT NULL DEFAULT 'Confirmed',
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    deleted_at TIMESTAMP NULL
                );
            ";
        } else {
            $sql = "
                CREATE TABLE IF NOT EXISTS bookings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    guest_name TEXT NOT NULL,
                    contact_number TEXT NOT NULL,
                    service_type TEXT NOT NULL,
                    pickup_point TEXT NOT NULL,
                    dropoff_point TEXT NOT NULL,
                    travel_date TEXT NOT NULL,
                    passengers INTEGER NOT NULL DEFAULT 1,
                    status TEXT NOT NULL DEFAULT 'Confirmed',
                    notes TEXT,
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    deleted_at TEXT NULL
                );
            ";
        }

        $pdo->exec($sql);
    }
}
