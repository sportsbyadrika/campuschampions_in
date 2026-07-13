<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base model with common CRUD, campus scoping and pagination helpers.
 *
 * Subclasses set $table, $fillable and (optionally) $campusScoped.
 */
abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];

    /** When true, list/find queries are automatically scoped to the user's campus. */
    protected bool $campusScoped = true;

    protected Database $db;

    public function __construct()
    {
        $this->db = Database::instance();
    }

    public function table(): string
    {
        return $this->table;
    }

    /** Find a single record by id, honouring campus scope. */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
        $params = [$id];
        $this->applyCampusScope($sql, $params);
        return $this->db->fetch($sql, $params);
    }

    /** Find by a column value. */
    public function findBy(string $column, mixed $value): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `{$this->table}` WHERE `$column` = ? LIMIT 1",
            [$value]
        );
    }

    public function all(): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        $params = [];
        $this->applyCampusScope($sql, $params);
        $sql .= " ORDER BY `{$this->primaryKey}` DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data): int
    {
        $data = $this->filterFillable($data);
        // Auto-set campus for scoped models when not super admin
        if ($this->campusScoped && !isset($data['campus_id']) && Auth::campusId() !== null) {
            $data['campus_id'] = Auth::campusId();
        }
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $columns);
        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $this->table,
            implode(', ', array_map(fn($c) => "`$c`", $columns)),
            implode(', ', $placeholders)
        );
        return $this->db->insert($sql, $data);
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->filterFillable($data);
        if (empty($data)) {
            return false;
        }
        $sets = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($data)));
        $data['__id'] = $id;
        $sql = "UPDATE `{$this->table}` SET $sets WHERE `{$this->primaryKey}` = :__id";
        // Enforce campus scope on update
        $params = $data;
        if ($this->campusScoped && Auth::campusId() !== null) {
            $sql .= " AND campus_id = :__campus";
            $params['__campus'] = Auth::campusId();
        }
        $this->db->query($sql, $params);
        return true;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
        $params = [$id];
        if ($this->campusScoped && Auth::campusId() !== null) {
            $sql .= " AND campus_id = ?";
            $params[] = Auth::campusId();
        }
        $this->db->query($sql, $params);
        return true;
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Append a campus-scope condition to a query for non-super-admins.
     * Assumes the base table has a campus_id column.
     */
    protected function applyCampusScope(string &$sql, array &$params, string $alias = ''): void
    {
        if (!$this->campusScoped) {
            return;
        }
        $campusId = Auth::campusId();
        if ($campusId === null) {
            return; // super admin: no scope
        }
        $col = $alias ? "$alias.campus_id" : "`{$this->table}`.campus_id";
        $connector = stripos($sql, ' WHERE ') !== false ? ' AND ' : ' WHERE ';
        $sql .= $connector . "$col = ?";
        $params[] = $campusId;
    }

    /**
     * Generic paginated / searchable listing.
     *
     * @param array $options keys: select, from (defaults to table), joins,
     *        where (array of [clause, params]), search (['q'=>, 'columns'=>[]]),
     *        filters (['col'=>value]), orderBy, page, perPage, campusAlias
     * @return array{rows: array, total: int, page: int, perPage: int, pages: int}
     */
    public function paginate(array $options): array
    {
        $select      = $options['select']   ?? "`{$this->table}`.*";
        $from        = $options['from']     ?? "`{$this->table}`";
        $joins       = $options['joins']    ?? '';
        $orderBy     = $options['orderBy']  ?? "`{$this->table}`.{$this->primaryKey} DESC";
        $page        = max(1, (int) ($options['page'] ?? 1));
        $perPage     = (int) ($options['perPage'] ?? 20);
        $campusAlias = $options['campusAlias'] ?? "`{$this->table}`";

        $where  = [];
        $params = [];

        // Explicit where clauses
        foreach ($options['where'] ?? [] as $clause) {
            $where[] = $clause[0];
            $params  = array_merge($params, $clause[1] ?? []);
        }

        // Campus scope
        if ($this->campusScoped && Auth::campusId() !== null) {
            $where[]  = "$campusAlias.campus_id = ?";
            $params[] = Auth::campusId();
        }

        // Search across columns
        if (!empty($options['search']['q']) && !empty($options['search']['columns'])) {
            $q = '%' . $options['search']['q'] . '%';
            $like = [];
            foreach ($options['search']['columns'] as $col) {
                $like[] = "$col LIKE ?";
                $params[] = $q;
            }
            $where[] = '(' . implode(' OR ', $like) . ')';
        }

        // Equality filters
        foreach ($options['filters'] ?? [] as $col => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $where[] = "$col = ?";
            $params[] = $value;
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // Total count
        $countSql = "SELECT COUNT(*) FROM $from $joins $whereSql";
        $total = (int) $this->db->scalar($countSql, $params);

        $pages  = max(1, (int) ceil($total / $perPage));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT $select FROM $from $joins $whereSql ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
        $rows = $this->db->fetchAll($sql, $params);

        return [
            'rows'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'pages'   => $pages,
        ];
    }
}
