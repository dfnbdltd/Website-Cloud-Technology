<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    public function passes($attribute, $value)
    {
        return is_string($value)
            && preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $value) === 1;
    }

    public function message()
    {
        return 'The password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character.';
    }
}
