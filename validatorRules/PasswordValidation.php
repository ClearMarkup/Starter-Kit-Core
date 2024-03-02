<?php
namespace ClearMarkupValidation\ExtendsRules;

class PasswordValidation extends \Rakit\Validation\Rule
{
    protected $message = "{{inavlid_pwd}}";

    protected $fillableParams = [];

    public function check($value): bool
    {

        $lengthRequirement = intval($_ENV['PASSWORD_POLICY_LENGTH']);
        $uppercaseRequirement = intval($_ENV['PASSWORD_POLICY_UPPERCASE']);
        $lowercaseRequirement = intval($_ENV['PASSWORD_POLICY_LOWERCASE']);
        $digitRequirement = intval($_ENV['PASSWORD_POLICY_DIGIT']);
        $specialRequirement = intval($_ENV['PASSWORD_POLICY_SPECIAL']);

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