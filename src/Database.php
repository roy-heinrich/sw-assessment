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
        } else {
            $dbPath = sys_get_temp_dir() . '/southwest_tours.sqlite';
            self::$instance = new PDO("sqlite:{$dbPath}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            if (!self::$schemaInitialized) {
                self::initSchema(self::$instance);
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
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP
                );
            ";
        }

        $pdo->exec($sql);

        $count = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
        if ($count === 0) {
            $sampleDate = date('Y-m-d', strtotime('+1 day'));
            $pdo->exec("
                INSERT INTO bookings (guest_name, contact_number, service_type, pickup_point, dropoff_point, travel_date, passengers, status, notes)
                VALUES 
                ('Juan Dela Cruz', '+63 917 123 4567', 'Airport Shuttle (Kalibo to Boracay)', 'Kalibo International Airport (KLO)', 'Station 2 Hotel (Boracay)', '{$sampleDate}', 2, 'Confirmed', 'Flight PR2969 arriving 10:30 AM'),
                ('Maria Santos', '+63 920 987 6543', 'Express Speedboat Transfer', 'Caticlan Airport (MPH)', 'Station 1 Resort (Boracay)', '{$sampleDate}', 4, 'Pending', 'Family with 2 kids, requested child life vests'),
                ('Alexander Smith', '+1 415 555 2671', 'Private Van Transfer', 'Kalibo Airport (KLO)', 'Station 3 Villa (Boracay)', '{$sampleDate}', 1, 'Completed', 'VIP Transfer with luggage assistance')
            ");
        }
    }
}
