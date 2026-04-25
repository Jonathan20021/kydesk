<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    protected ?PDO $pdo = null;
    protected array $cfg;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }

    public function pdo(): PDO
    {
        if ($this->pdo) return $this->pdo;
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->cfg['host'], $this->cfg['port'], $this->cfg['name'], $this->cfg['charset']
        );
        try {
            $this->pdo = new PDO($dsn, $this->cfg['user'], $this->cfg['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Permitir carga sin base de datos para el instalador
            throw $e;
        }
        return $this->pdo;
    }

    public function run(string $sql, array $params = []): \PDOStatement
    {
        $st = $this->pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public function all(string $sql, array $p = []): array  { return $this->run($sql, $p)->fetchAll(); }
    public function one(string $sql, array $p = []): ?array { $r = $this->run($sql, $p)->fetch(); return $r ?: null; }
    public function val(string $sql, array $p = [])         { $r = $this->run($sql, $p)->fetch(PDO::FETCH_NUM); return $r[0] ?? null; }
    public function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (:' . implode(',:', $cols) . ')';
        $this->run($sql, $data);
        return (int)$this->pdo()->lastInsertId();
    }
    public function update(string $table, array $data, string $where, array $params = []): int
    {
        $usesPositional = strpos($where, '?') !== false;
        $usesNamed = (bool)preg_match('/:[a-zA-Z_][a-zA-Z0-9_]*/', $where);

        if ($usesPositional && !$usesNamed) {
            $set = [];
            foreach ($data as $k => $_) $set[] = "`$k` = ?";
            $sql = 'UPDATE `' . $table . '` SET ' . implode(',', $set) . ' WHERE ' . $where;
            return $this->run($sql, array_merge(array_values($data), array_values($params)))->rowCount();
        }

        $set = [];
        $bind = [];
        foreach ($data as $k => $v) {
            $key = '_set_' . $k;
            $set[] = "`$k` = :$key";
            $bind[$key] = $v;
        }
        $sql = 'UPDATE `' . $table . '` SET ' . implode(',', $set) . ' WHERE ' . $where;
        return $this->run($sql, array_merge($bind, $params))->rowCount();
    }
    public function delete(string $table, string $where, array $params = []): int
    {
        return $this->run('DELETE FROM `' . $table . '` WHERE ' . $where, $params)->rowCount();
    }
}
