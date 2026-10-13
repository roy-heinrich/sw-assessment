<?php
declare(strict_types=1);

namespace App;

use PDO;

class Database
{
    private static ?PDO $instance = null;
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

        if (!$databaseUrl) {
            throw new \RuntimeException('DATABASE_URL is required. Configure your Neon/Postgres connection.');
        }

        self::$instance = self::connectNeonUrl($databaseUrl);
        if ($autoSchema && !self::$schemaChecked) {
            self::ensureSchema(self::$instance);
            self::$schemaChecked = true;
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
     */
    private static function ensureSchema(PDO $pdo): void
    {
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
                   AND indexname IN (
                     'idx_bookings_deleted_at',
                     'idx_bookings_status',
                     'idx_bookings_deleted_status',
                     'idx_bookings_status_deleted_at',
                     'idx_bookings_deleted_id',
                     'idx_bookings_deleted_travel_date'
                   )) AS index_count
        ")->fetch(PDO::FETCH_ASSOC);

        if (empty($row['has_table'])) {
            self::initSchema($pdo);
            return;
        }
        if (empty($row['has_deleted_at'])) {
            $pdo->exec("ALTER TABLE bookings ADD COLUMN deleted_at TIMESTAMP NULL");
        }
        if (empty($row['has_first_name']) && !empty($row['has_guest_name'])) {
            self::migrateGuestName($pdo);
        }
        if ((int)($row['index_count'] ?? 0) < 6) {
            self::createIndexes($pdo);
        }
    }

    /**
     * Splits the old single "guest_name" column into first/middle/last, then drops it.
     * The split is best effort: first word, last word, everything else in the middle.
     */
    private static function migrateGuestName(PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN first_name VARCHAR(255) NOT NULL DEFAULT ''");
        $pdo->exec("ALTER TABLE bookings ADD COLUMN middle_name VARCHAR(255) NULL");
        $pdo->exec("ALTER TABLE bookings ADD COLUMN last_name VARCHAR(255) NOT NULL DEFAULT ''");

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

        $pdo->exec($sql);
        self::createIndexes($pdo);
    }

    private static function createIndexes(PDO $pdo): void
    {
        // Single-column indexes
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bookings_deleted_at ON bookings (deleted_at)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bookings_status ON bookings (status)");
        // Composite indexes for common query patterns
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bookings_deleted_status ON bookings (deleted_at, status)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bookings_status_deleted_at ON bookings (status, deleted_at)");
        // Ordering/indexing by id when filtering by deleted_at
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bookings_deleted_id ON bookings (deleted_at, id DESC)");
        // Useful if you ever sort/filter by travel date
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bookings_deleted_travel_date ON bookings (deleted_at, travel_date)");
    }
}
