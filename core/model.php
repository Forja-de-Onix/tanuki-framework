<?php

require_once __DIR__ . '/../config/database.php';

class Model {
    protected static $table = '';

    public static function all() {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT * FROM " . static::$table);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id, $inputs = [], $filter = []) {
        // TODO añadir parámetros de búsqueda mediante dos arrays campos y filtro (estan llamados por defecto pero no implementados aqui)
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM " . static::$table . " WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $pdo = Database::connect();
        $columns = implode(', ', array_keys($data));
        $placeholders = ":" . implode(', :', array_keys($data));
        $stmt = $pdo->prepare("INSERT INTO " . static::$table . " ($columns) VALUES ($placeholders)");
        return $stmt->execute($data);
    }

    public static function update($id, $data) {
        $pdo = Database::connect();
        $fields = implode(', ', array_map(fn($key) => "$key = :$key", array_keys($data)));
        $data['id'] = $id;
        $stmt = $pdo->prepare("UPDATE " . static::$table . " SET $fields WHERE id = :id");
        return $stmt->execute($data);
    }

    public static function delete($id) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("DELETE FROM " . static::$table . " WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
