<?php
namespace ClearMarkup\Validation\ExtendsRules;

use ClearMarkup\Classes\Core;

class CorrectPassword extends \Rakit\Validation\Rule
{
    protected $message = "Incorrect password. Please try again.";

    protected $fillableParams = [];

    public function check($value): bool
    {
        return Core::getAuthInstance()->reconfirmPassword($value);
    }
}