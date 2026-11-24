<?php

/**
 * Kelas dasar model. Semua query memakai prepared statement (PDO).
 */
abstract class Model
{
    protected static function db(): PDO
    {
        return Database::pdo();
    }
}
