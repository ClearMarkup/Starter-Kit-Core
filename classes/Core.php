<?php

namespace ClearMarkup\Classes;

use Medoo\Medoo;
use Delight\Auth\Auth;

/**
 * Class Core
 * 
 * This class represents the core functionality of the application.
 * It provides methods for accessing the database and authentication instance,
 * as well as applying operations to values and logging user actions.
 */
class Core
{
    private static $dbInstance;
    private static $authInstance;
    private static $projectRoot;


    /**
     * Core constructor.
     * 
     * Initializes the database and authentication instances.
     */
    public function __construct()
    {

    }

    public static function getProjectRoot()
    {
        if (self::$projectRoot === null) {
            self::$projectRoot = getcwd();
        }
        return self::$projectRoot . '/';
    }

    /**
     * Get the database instance.
     * 
     * @return Medoo The database instance.
     */
    public static function getDbInstance()
    {
        if (self::$dbInstance === null) {
            self::$dbInstance = new Medoo([
                'type' => $_ENV['DB_TYPE'] ?? null,
                'database' => $_ENV['DB_DATABASE'] ?? null,
                'host' => $_ENV['DB_HOST'] ?? null,
                'username' => $_ENV['DB_USERNAME'] ?? null,
                'password' => $_ENV['DB_PASSWORD'] ?? null,
                'charset' => $_ENV['DB_CHARSET'] ?? null,
                'collation' => $_ENV['DB_COLLATION'] ?? null,
                'port' => $_ENV['DB_PORT'] ?? null,
                'prefix' => $_ENV['DB_PREFIX'] ?? null,
                'logging' => $_ENV['DEBUG'] === "true" ? true : false
            ]);
        }
        return self::$dbInstance;
    }

    /**
     * Get the authentication instance.
     * 
     * @return Auth The authentication instance.
     */
    public static function getAuthInstance()
    {
        if (self::$authInstance === null) {
            self::$authInstance = new Auth(self::getDbInstance()->pdo, null, null, $_ENV['DEBUG'] === "true" ? false : true);
        }
        return self::$authInstance;
    }
}
