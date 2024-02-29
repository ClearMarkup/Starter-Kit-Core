<?php
namespace ClearMarkupValidation\ExtendsRules;

use ClearMarkup\Classes\Core;

class UsernameValidation extends \Rakit\Validation\Rule
{
    protected $message = "The username must be between 5 and 20 characters long, only contain alphanumeric characters, numbers and underscores, and not start or end with an underscore.";

    protected $fillableParams = [];

    public function check($value): bool
    {
        $db = Core::getDbInstance();
        $check = $db->has('users', ['username' => $value]);

        if ($check) {
            $this->message = "This username is already taken.";
            return false;
        }

        if (!\preg_match('/[\x00-\x1f\x7f\/:\\\\]/', $value) === 0) {
            return true;
        }

        // Check if the username is between 5 and 20 characters long
        if (strlen($value) < 5 || strlen($value) > 20) {
            return false;
        }

        // Check if the username only contains alphanumeric characters and underscores
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            return false;
        }

        // Check if the username doesn't start or end with an underscore
        if ($value[0] === '_' || $value[strlen($value) - 1] === '_') {
            return false;
        }

        // Check if the username doesn't contain more than one underscore in a row
        if (strpos($value, '__') !== false) {
            return false;
        }

        return true;
    }
}