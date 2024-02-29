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
    protected static $dbInstance;
    protected static $authInstance;

    /**
     * Core constructor.
     * 
     * Initializes the database and authentication instances.
     */
    public function __construct()
    {
        global $config;
        self::$dbInstance = new Medoo($config->database);
        self::$authInstance = new Auth(self::$dbInstance->pdo, null, null, $config->debug ? false : true);
    }

    /**
     * Get the database instance.
     * 
     * @return Medoo The database instance.
     */
    public static function getDbInstance()
    {
        global $config;
        if (self::$dbInstance === null) {
            self::$dbInstance = new Medoo($config->database);
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
        self::getDbInstance();
        global $config;
        if (self::$authInstance === null) {
            self::$authInstance = new Auth(self::$dbInstance->pdo, null, null, $config->debug ? false : true);
        }
        return self::$authInstance;
    }

    /**
     * Apply operations to a value.
     * 
     * @param mixed $value The value to apply operations to.
     * @param string|array $operations The operations to apply, separated by '|' or provided as an array.
     * @return mixed The modified value.
     */
    static protected function applyOperations($value, $operations)
    {
        if (!is_string($operations)) {
            $operations = implode('|', $operations);
        }

        $operations = explode('|', $operations);

        foreach ($operations as $operation) {
            $parameter = null;

            if (strpos($operation, ':') !== false) {
                list($operation, $parameter) = explode(':', $operation);
            }

            switch ($operation) {
                case 'trim':
                    $value = trim($value);
                    break;
                case 'escape':
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    break;
                case 'empty_string_to_null':
                    $value = $value === '' ? null : $value;
                    break;
                case 'strip_tags':
                    $value = strip_tags($value);
                    break;
                case 'truncate':
                    $truncateLength = is_numeric($parameter) ? $parameter : 57;
                    $value = strlen($value) > $truncateLength ? substr($value, 0, $truncateLength) . '...' : $value;
                    break;
                case 'email':
                    $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                    break;
            }
        }

        return $value;
    }

    /**
     * Log a user action.
     * 
     * @param string $action The action performed by the user.
     * @param mixed $data Additional data related to the action.
     * @return void
     */
    static function log($action, $data)
    {
        $db = new Db();

        $db->table('users_logs')->insert([
            'user_id' => self::$authInstance->getUserId() ?? null,
            'action' => $action,
            'data' => json_encode($data),
            'created_at' => time()
        ]);
    }

    /**
     * Check if a user action has been logged a certain number of times within a specified time frame.
     * 
     * @param string $action The action to check for.
     * @param int $mot The minimum number of times the action should be logged.
     * @param string $time The time frame to check within.
     * @return bool True if the action has been logged enough times, false otherwise.
     */
    static function checkLog($action, $mot, $time)
    {
        $time = strtotime($time) - time();

        $log = self::$dbInstance->count('users_logs', [
            'user_id' => self::$authInstance->getUserId(),
            'action' => $action,
            'created_at[>]' => time() - $time
        ]);

        if ($log >= $mot) {
            return true;
        } else {
            return false;
        }
    }
}
