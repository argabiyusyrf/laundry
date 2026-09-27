<?php
namespace App\Core;

use PDO;

/**
 * Model dasar. Semua model turunan mendapatkan akses PDO ber-lewat db().
 */
abstract class Model
{
    protected function db(): PDO
    {
        return Database::conn();
    }
}