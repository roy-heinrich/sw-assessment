<?php
declare(strict_types=1);

namespace App;

use PDO;

class BookingRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function filterClause(string $search, string $status, bool $archived): array
    {
        $sql = " WHERE " . ($archived ? "deleted_at IS NOT NULL" : "deleted_at IS NULL");
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (LOWER(first_name) LIKE :n1 ESCAPE '\\' OR LOWER(middle_name) LIKE :n2 ESCAPE '\\' OR LOWER(last_name) LIKE :n3 ESCAPE '\\' OR LOWER(pickup_point) LIKE :s1 ESCAPE '\\' OR LOWER(dropoff_point) LIKE :s2 ESCAPE '\\' OR LOWER(contact_number) LIKE :s3 ESCAPE '\\')";
            // Escape LIKE wildcards so a "%" or "_" typed by the user is matched literally.
            $searchClean = preg_replace('/\s+/', ' ', trim($search));
            $lowered = strtolower($searchClean);
            if (function_exists('mb_strtolower')) {
                $lowered = mb_strtolower($searchClean, 'UTF-8');
            }
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $lowered);
            $term = '%' . $escaped . '%';
            $params[':n1'] = $term;
            $params[':n2'] = $term;
            $params[':n3'] = $term;
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
        }

        if (!empty($status) && in_array($status, ['Confirmed', 'Pending', 'Completed', 'Cancelled'], true)) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        return [$sql, $params];
    }

    public function getAll(string $search = '', string $status = '', bool $archived = false, ?int $limit = null, int $offset = 0): array
    {
        [$where, $params] = $this->filterClause($search, $status, $archived);
        $sql = "SELECT * FROM bookings" . $where . " ORDER BY id DESC";
        if ($limit !== null) {
            // Cast to int: values are already ints here, so no injection risk.
            $sql .= " LIMIT " . $limit . " OFFSET " . max(0, $offset);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(string $search = '', string $status = '', bool $archived = false): int
    {
        [$where, $params] = $this->filterClause($search, $status, $archived);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings" . $where);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getStats(bool $archived = false): array
    {
        $cond = $archived ? 'deleted_at IS NOT NULL' : 'deleted_at IS NULL';
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
            FROM bookings
            WHERE {$cond}
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'confirmed' => (int)($row['confirmed'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
            'completed' => (int)($row['completed'] ?? 0),
        ];
    }

    public function find(int $id, bool $archived = false): ?array
    {
        $cond = $archived ? 'deleted_at IS NOT NULL' : 'deleted_at IS NULL';
        $stmt = $this->db->prepare("SELECT * FROM bookings WHERE id = :id AND {$cond} LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO bookings (first_name, middle_name, last_name, contact_number, service_type, pickup_point, dropoff_point, travel_date, passengers, status, notes)
            VALUES (:first_name, :middle_name, :last_name, :contact_number, :service_type, :pickup_point, :dropoff_point, :travel_date, :passengers, :status, :notes)
        ");

        $stmt->execute([
            ':first_name' => trim((string)($data['first_name'] ?? '')),
            ':middle_name' => trim((string)($data['middle_name'] ?? '')) ?: null,
            ':last_name' => trim((string)($data['last_name'] ?? '')),
            ':contact_number' => trim((string)($data['contact_number'] ?? '')),
            ':service_type' => trim((string)($data['service_type'] ?? 'Airport Shuttle (Kalibo to Boracay)')),
            ':pickup_point' => trim((string)($data['pickup_point'] ?? '')),
            ':dropoff_point' => trim((string)($data['dropoff_point'] ?? '')),
            ':travel_date' => $data['travel_date'] ?? date('Y-m-d'),
            ':passengers' => max(1, (int)($data['passengers'] ?? 1)),
            ':status' => in_array($data['status'] ?? '', ['Confirmed', 'Pending', 'Completed', 'Cancelled'], true) ? $data['status'] : 'Confirmed',
            ':notes' => trim((string)($data['notes'] ?? '')),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE bookings SET
                first_name = :first_name,
                middle_name = :middle_name,
                last_name = :last_name,
                contact_number = :contact_number,
                service_type = :service_type,
                pickup_point = :pickup_point,
                dropoff_point = :dropoff_point,
                travel_date = :travel_date,
                passengers = :passengers,
                status = :status,
                notes = :notes
            WHERE id = :id AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':id' => $id,
            ':first_name' => trim((string)($data['first_name'] ?? '')),
            ':middle_name' => trim((string)($data['middle_name'] ?? '')) ?: null,
            ':last_name' => trim((string)($data['last_name'] ?? '')),
            ':contact_number' => trim((string)($data['contact_number'] ?? '')),
            ':service_type' => trim((string)($data['service_type'] ?? 'Airport Shuttle (Kalibo to Boracay)')),
            ':pickup_point' => trim((string)($data['pickup_point'] ?? '')),
            ':dropoff_point' => trim((string)($data['dropoff_point'] ?? '')),
            ':travel_date' => $data['travel_date'] ?? date('Y-m-d'),
            ':passengers' => max(1, (int)($data['passengers'] ?? 1)),
            ':status' => in_array($data['status'] ?? '', ['Confirmed', 'Pending', 'Completed', 'Cancelled'], true) ? $data['status'] : 'Confirmed',
            ':notes' => trim((string)($data['notes'] ?? '')),
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE bookings SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE bookings SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** Hard delete. Only an already-archived booking can be purged. */
    public function purge(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM bookings WHERE id = :id AND deleted_at IS NOT NULL");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['Confirmed', 'Pending', 'Completed', 'Cancelled'], true)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE bookings SET status = :status WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id, ':status' => $status]);
        return $stmt->rowCount() > 0;
    }
}
