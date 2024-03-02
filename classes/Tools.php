<?php

namespace ClearMarkup\Classes;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * The Tools class provides various utility methods for common operations.
 */
class Tools extends Core{

    /**
     * Constructs a new Tools object.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Sanitizes a value using the specified operations.
     *
     * @param mixed $value The value to sanitize.
     * @param callable|array $operations The operations to apply for sanitization.
     * @return mixed The sanitized value.
     */
    static public function sanitize($value, $operations)
    {
        if (is_callable($operations)) {
            return $operations($value);
        } else {
            return self::applyOperations($value, $operations);
        }

        return $value;
    }

    /**
     * Explodes a value using the specified delimiter and applies a callback function to each element.
     *
     * @param string $value The value to explode.
     * @param string $delimiter The delimiter to use for exploding.
     * @param callable $callback The callback function to apply to each element.
     * @return array The exploded and mapped array.
     */
    static public function exlodeMap($value, $delimiter, $callback)
    {
        $value = explode($delimiter, $value);
        $value = array_map($callback, $value);
        return array_filter($value);
    }

    /**
     * Formats a number to a short representation with suffixes (k, m, b, t).
     *
     * @param float $n The number to format.
     * @param int $precision The number of decimal places to round to (default: 1).
     * @return string The formatted number with suffix.
     */
    static public function shortNummber($n, $precision = 1)
    {
        if ($n < 900) {
            // 0 - 900
            $n_format = number_format($n, $precision);
            $suffix = '';
        } else if ($n < 900000) {
            // 0.9k-850k
            $n_format = number_format($n / 1000, $precision);
            $suffix = 'k';
        } else if ($n < 900000000) {
            // 0.9m-850m
            $n_format = number_format($n / 1000000, $precision);
            $suffix = 'm';
        } else if ($n < 900000000000) {
            // 0.9b-850b
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = 'b';
        } else {
            // 0.9t+
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = 't';
        }
    
        // Remove unecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
        // Intentionally does not affect partials, eg "1.50" -> "1.50"
        if ($precision > 0) {
            $dotzero = '.' . str_repeat('0', $precision);
            $n_format = str_replace($dotzero, '', $n_format);
        }
    
        return $n_format . $suffix;
    }

    /**
     * Sends an email using PHPMailer.
     *
     * @param string $to The recipient email address.
     * @param string $subject The email subject.
     * @param string $content The email content.
     * @param array $holders Optional placeholders to replace in the content.
     * @throws Exception If the email sending fails.
     */
    static function sendEmail($to, $subject, $content, $holders = [])
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = "UTF-8";
    
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = $_ENV['SMTP_AUTH'];
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = $_ENV['SMTP_SECURE'];
        $mail->Port       = intval($_ENV['SMTP_PORT']);
    
        foreach ($holders as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
    
        $mail->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_TEXT']);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $content;
        try {
            $mail->send();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Generates a CSRF token and returns it as a meta tag.
     *
     * @return string The CSRF token as a meta tag.
     */
    static public function headCsrf()
    {
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = \Delight\Auth\Auth::createRandomString(32);
        }
        $token = filter_var($_SESSION['_token'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        return '<meta name="csrf-token" content="' . $token . '">';
    }

    /**
     * Generates a CSRF token and returns it as a hidden input field.
     *
     * @return string The CSRF token as a hidden input field.
     */
    static public function csrf()
    {
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = \Delight\Auth\Auth::createRandomString(32);
        }
        $token = filter_var($_SESSION['_token'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        return '<input type="hidden" name="_token" value="' . $token . '">';
    }

    /**
     * Log a user action.
     * 
     * @param string $action The action performed by the user.
     * @param mixed $data Additional data related to the action.
     * @return void
     */
    static public function log($action, $data)
    {
        $db = new Db();

        $db->table('users_logs')->insert([
            'user_id' => self::getAuthInstance()->getUserId() ?? null,
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
    static public function checkLog($action, $mot, $time)
    {
        $time = strtotime($time) - time();

        $log = self::getDbInstance()->count('users_logs', [
            'user_id' => self::getAuthInstance()->getUserId(),
            'action' => $action,
            'created_at[>]' => time() - $time
        ]);

        if ($log >= $mot) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Apply operations to a value.
     * 
     * @param mixed $value The value to apply operations to.
     * @param string|array $operations The operations to apply, separated by '|' or provided as an array.
     * @return mixed The modified value.
     */
    static public function applyOperations($value, $operations)
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
     * Apply a callback to all files with a certain extension in a directory and its subdirectories.
     * 
     * @param string $fileExt The file extension to apply the callback to.
     * @param string $dir The directory to search for files in.
     * @param callable $callback The callback to apply to the files.
     * @return void
     */
    static public function applyCallbackToFiles($fileExt, $dir, $callback)
    {
        foreach (glob($dir . '/*.' . $fileExt) as $file) {
            $callback($file);
        }

        foreach (glob($dir . '/*', GLOB_ONLYDIR) as $subDir) {
            self::applyCallbackToFiles($fileExt, $subDir, $callback);
        }
    }
}