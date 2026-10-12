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

    public function getAll(string $search = '', string $status = '', bool $archived = false): array
    {
        $sql = "SELECT * FROM bookings WHERE " . ($archived ? "deleted_at IS NOT NULL" : "deleted_at IS NULL");
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (LOWER(guest_name) LIKE :s1 OR LOWER(pickup_point) LIKE :s2 OR LOWER(dropoff_point) LIKE :s3 OR LOWER(contact_number) LIKE :s4)";
            $term = '%' . strtolower(trim($search)) . '%';
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
            $params[':s4'] = $term;
        }

        if (!empty($status) && in_array($status, ['Confirmed', 'Pending', 'Completed', 'Cancelled'], true)) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(): array
    {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
            FROM bookings
            WHERE deleted_at IS NULL
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'confirmed' => (int)($row['confirmed'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
            'completed' => (int)($row['completed'] ?? 0),
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM bookings WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO bookings (guest_name, contact_number, service_type, pickup_point, dropoff_point, travel_date, passengers, status, notes)
            VALUES (:guest_name, :contact_number, :service_type, :pickup_point, :dropoff_point, :travel_date, :passengers, :status, :notes)
        ");

        $stmt->execute([
            ':guest_name' => trim((string)($data['guest_name'] ?? '')),
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
                guest_name = :guest_name,
                contact_number = :contact_number,
                service_type = :service_type,
                pickup_point = :pickup_point,
                dropoff_point = :dropoff_point,
                travel_date = :travel_date,
                passengers = :passengers,
                status = :status,
                notes = :notes
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':guest_name' => trim((string)($data['guest_name'] ?? '')),
            ':contact_number' => trim((string)($data['contact_number'] ?? '')),
            ':service_type' => trim((string)($data['service_type'] ?? 'Airport Shuttle (Kalibo to Boracay)')),
            ':pickup_point' => trim((string)($data['pickup_point'] ?? '')),
            ':dropoff_point' => trim((string)($data['dropoff_point'] ?? '')),
            ':travel_date' => $data['travel_date'] ?? date('Y-m-d'),
            ':passengers' => max(1, (int)($data['passengers'] ?? 1)),
            ':status' => in_array($data['status'] ?? '', ['Confirmed', 'Pending', 'Completed', 'Cancelled'], true) ? $data['status'] : 'Confirmed',
            ':notes' => trim((string)($data['notes'] ?? '')),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE bookings SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL");
        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE bookings SET deleted_at = NULL WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['Confirmed', 'Pending', 'Completed', 'Cancelled'], true)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE bookings SET status = :status WHERE id = :id");
        return $stmt->execute([':id' => $id, ':status' => $status]);
    }
}
