<?php
namespace ClearMarkupValidation\ExtendsRules;

class PasswordValidation extends \Rakit\Validation\Rule
{
    protected $message = "{{inavlid_pwd}}";

    protected $fillableParams = [];

    public function check($value): bool
    {
        global $config;

        $lengthRequirement = $config->password_policy['length'];
        $uppercaseRequirement = $config->password_policy['uppercase'];
        $lowercaseRequirement = $config->password_policy['lowercase'];
        $digitRequirement = $config->password_policy['digit'];
        $specialRequirement = $config->password_policy['special'];

        $errors = [];

        $length = strlen($value);
        $uppercaseCount = preg_match_all('/[A-Z]/', $value);
        $lowercaseCount = preg_match_all('/[a-z]/', $value);
        $digitCount = preg_match_all('/[0-9]/', $value);
        $specialCount = preg_match_all('/[^A-Za-z0-9]/', $value);

        if ($lengthRequirement > 0 && $length < $lengthRequirement) {
            $errors[] = "be at least $lengthRequirement characters long.";
        }

        if ($uppercaseRequirement > 0 && $uppercaseCount < $uppercaseRequirement) {
            $errors[] = "contain at least $uppercaseRequirement uppercase letter(s).";
        }

        if ($lowercaseRequirement > 0 && $lowercaseCount < $lowercaseRequirement) {
            $errors[] = "contain at least $lowercaseRequirement lowercase letter(s).";
        }

        if ($digitRequirement > 0 && $digitCount < $digitRequirement) {
            $errors[] = "contain at least $digitRequirement digit(s).";
        }

        if ($specialRequirement > 0 && $specialCount < $specialRequirement) {
            $errors[] = "contain at least $specialRequirement special character(s).";
        }

        if (empty($errors)) {
            return true;
        } else {
            $this->message = "The password must:<br> - ";
            $this->message .= implode("<br> - ", $errors);
            return false;
        }
    }
}