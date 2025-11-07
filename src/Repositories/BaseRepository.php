<?php

namespace WhatsBot\Repositories;

use PDO;

/**
 * BaseRepository
 * 
 * Classe base com funcionalidades comuns para todos os repositories
 */
abstract class BaseRepository
{
    protected PDO $pdo;
    protected string $table;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Executa uma query e retorna o primeiro resultado
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Executa uma query e retorna todos os resultados
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Executa uma operação de escrita (INSERT/UPDATE/DELETE)
     */
    protected function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Retorna o número de linhas afetadas pela última operação
     */
    protected function rowCount(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Verifica se uma coluna existe na tabela
     */
    protected function columnExists(string $column): bool
    {
        $sql = "SHOW COLUMNS FROM {$this->table} LIKE :column";
        $result = $this->fetchOne($sql, [':column' => $column]);
        return $result !== null;
    }
}
