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
        global $config;
    
        $mail = new PHPMailer(true);
        $mail->CharSet = "UTF-8";
    
        if ($config->smtp) {
            $mail->isSMTP();
            $mail->Host       = $config->smtp['host'];
            $mail->SMTPAuth   = $config->smtp['SMTPAuth'];
            $mail->Username   = $config->smtp['username'];
            $mail->Password   = $config->smtp['password'];
            $mail->SMTPSecure = $config->smtp['SMTPSecure'];
            $mail->Port       = $config->smtp['port'];
        }
    
        foreach ($holders as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
    
        $mail->setFrom($config->mail_from, $config->mail_from_text);
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
}