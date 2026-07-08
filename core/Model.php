<?php
require_once dirname(__DIR__) . '/core/Database.php';

abstract class Model {
    protected Database $db;
    protected string $table = '';
    protected ?int $tenantId = null;

    public function __construct(?int $tenantId = null) {
        $this->db       = Database::getInstance();
        $this->tenantId = $tenantId;
    }

    protected function scopedWhere(): string {
        return $this->tenantId ? "tenant_id = {$this->tenantId}" : '1=1';
    }

    public function findAll(string $extra = '', array $params = []): array {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->scopedWhere()} {$extra}";
        return $this->db->fetchAll($sql, $params);
    }

    public function findById(int $id): array|false {
        $sql    = "SELECT * FROM {$this->table} WHERE id = ? AND {$this->scopedWhere()}";
        $params = [$id];
        return $this->db->fetchOne($sql, $params);
    }

    public function create(array $data): string {
        if ($this->tenantId && !isset($data['tenant_id'])) {
            $data['tenant_id'] = $this->tenantId;
        }
        $cols   = implode(', ', array_keys($data));
        $slots  = implode(', ', array_fill(0, count($data), '?'));
        $sql    = "INSERT INTO {$this->table} ({$cols}) VALUES ({$slots})";
        return $this->db->insert($sql, array_values($data));
    }

    public function update(int $id, array $data): int {
        $sets   = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $sql    = "UPDATE {$this->table} SET {$sets} WHERE id = ? AND {$this->scopedWhere()}";
        $params = [...array_values($data), $id];
        return $this->db->execute($sql, $params);
    }

    public function delete(int $id): int {
        $sql = "DELETE FROM {$this->table} WHERE id = ? AND {$this->scopedWhere()}";
        return $this->db->execute($sql, [$id]);
    }

    public function count(string $extra = '', array $params = []): int {
        $sql = "SELECT COUNT(*) AS cnt FROM {$this->table} WHERE {$this->scopedWhere()} {$extra}";
        $row = $this->db->fetchOne($sql, $params);
        return (int)($row['cnt'] ?? 0);
    }
}
