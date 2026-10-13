<?php
declare(strict_types=1);

namespace App;

use PDO;

class Database
{
    private static ?PDO $instance = null;
    private static bool $schemaEnsured = false;
    private static bool $schemaChecked = false;

    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? '');
        $autoSchema = strtolower((string)(getenv('APP_AUTO_SCHEMA') ?: ($_ENV['APP_AUTO_SCHEMA'] ?? 'true')));
        if ($autoSchema === '0' || $autoSchema === 'false' || $autoSchema === 'off') {
            $autoSchema = false;
        } else {
            $autoSchema = true;
        }

        if ($databaseUrl) {
            self::$instance = self::connectNeonUrl($databaseUrl);
            if ($autoSchema && !self::$schemaChecked) {
                self::ensureSchema(self::$instance, 'pgsql');
                self::$schemaChecked = true;
            }
        } else {
            if (getenv('VERCEL')) {
                // A missing env var must fail loudly, not silently use throwaway storage.
                throw new \RuntimeException('DATABASE_URL is required in production.');
            }
            $dbPath = getenv('SQLITE_PATH') ?: sys_get_temp_dir() . '/southwest_tours.sqlite';
            self::$instance = new PDO("sqlite:{$dbPath}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            if ($autoSchema && !self::$schemaEnsured) {
                self::initSchema(self::$instance);
                self::ensureSchema(self::$instance, 'sqlite');
                self::$schemaEnsured = true;
                self::$schemaChecked = true;
            }
        }

        return self::$instance;
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
     * Brings an existing database up to date. Runs one cheap catalog query and only
     * writes when something is missing, so a fully provisioned database costs one
     * round trip per request and no locks.
     *
     * shortcut: the catalog query runs on every request; if cold-start latency ever
     * matters, move migrations to a deploy step and delete this call.
     */
    private static function ensureSchema(PDO $pdo, string $driver): void
    {
        if ($driver === 'sqlite') {
            // SQLite has no ADD COLUMN IF NOT EXISTS, so read the current columns first.
            $columns = [];
            foreach ($pdo->query("PRAGMA table_info(bookings)")->fetchAll(PDO::FETCH_ASSOC) as $col) {
                $columns[$col['name'] ?? ''] = true;
            }
            if (!isset($columns['deleted_at'])) {
                $pdo->exec("ALTER TABLE bookings ADD COLUMN deleted_at TEXT NULL");
            }
            if (!isset($columns['first_name']) && isset($columns['guest_name'])) {
                self::migrateGuestName($pdo, $driver);
            }
            return;
        }

        $row = $pdo->query("
            SELECT
                to_regclass('public.bookings') IS NOT NULL AS has_table,
                EXISTS (SELECT 1 FROM information_schema.columns
                        WHERE table_name = 'bookings' AND column_name = 'deleted_at') AS has_deleted_at,
                EXISTS (SELECT 1 FROM information_schema.columns
                        WHERE table_name = 'bookings' AND column_name = 'first_name') AS has_first_name,
                EXISTS (SELECT 1 FROM information_schema.columns
                        WHERE table_name = 'bookings' AND column_name = 'guest_name') AS has_guest_name,
                (SELECT COUNT(*) FROM pg_indexes
                 WHERE tablename = 'bookings'
                   AND indexname IN ('idx_bookings_deleted_at', 'idx_bookings_status')) AS index_count
        ")->fetch(PDO::FETCH_ASSOC);

        if (empty($row['has_table'])) {
            self::initSchema($pdo);
            return;
        }
        if (empty($row['has_deleted_at'])) {
            $pdo->exec("ALTER TABLE bookings ADD COLUMN deleted_at TIMESTAMP NULL");
        }
        if (empty($row['has_first_name']) && !empty($row['has_guest_name'])) {
            self::migrateGuestName($pdo, $driver);
        }
        if ((int)($row['index_count'] ?? 0) < 2) {
            self::createIndexes($pdo);
        }
    }

    /**
     * Splits the old single "guest_name" column into first/middle/last, then drops it.
     * The split is best effort: first word, last word, everything else in the middle.
     */
    private static function migrateGuestName(PDO $pdo, string $driver): void
    {
        if ($driver === 'pgsql') {
            $pdo->exec("ALTER TABLE bookings ADD COLUMN first_name VARCHAR(255) NOT NULL DEFAULT ''");
            $pdo->exec("ALTER TABLE bookings ADD COLUMN middle_name VARCHAR(255) NULL");
            $pdo->exec("ALTER TABLE bookings ADD COLUMN last_name VARCHAR(255) NOT NULL DEFAULT ''");
        } else {
            $pdo->exec("ALTER TABLE bookings ADD COLUMN first_name TEXT NOT NULL DEFAULT ''");
            $pdo->exec("ALTER TABLE bookings ADD COLUMN middle_name TEXT NULL");
            $pdo->exec("ALTER TABLE bookings ADD COLUMN last_name TEXT NOT NULL DEFAULT ''");
        }

        $rows = $pdo->query("SELECT id, guest_name FROM bookings")->fetchAll(PDO::FETCH_ASSOC);
        $update = $pdo->prepare("UPDATE bookings SET first_name = :first, middle_name = :middle, last_name = :last WHERE id = :id");
        foreach ($rows as $row) {
            $parts = preg_split('/\s+/', trim((string)$row['guest_name']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $last = count($parts) > 1 ? (string)array_pop($parts) : '';
            $update->execute([
                ':first' => $parts[0] ?? '',
                ':middle' => implode(' ', array_slice($parts, 1)) ?: null,
                ':last' => $last,
                ':id' => $row['id'],
            ]);
        }

        $pdo->exec("ALTER TABLE bookings DROP COLUMN guest_name");
    }

    private static function initSchema(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $sql = "
                CREATE TABLE IF NOT EXISTS bookings (
                    id SERIAL PRIMARY KEY,
                    first_name VARCHAR(255) NOT NULL,
                    middle_name VARCHAR(255) NULL,
                    last_name VARCHAR(255) NOT NULL,
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
                    first_name TEXT NOT NULL,
                    middle_name TEXT NULL,
                    last_name TEXT NOT NULL,
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
        self::createIndexes($pdo);
    }

    private static function createIndexes(PDO $pdo): void
    {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bookings_deleted_at ON bookings (deleted_at)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bookings_status ON bookings (status)");
    }
}
