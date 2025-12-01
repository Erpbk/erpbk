<?php

namespace App\Rules;

use App\Services\ActiveStatusValidator;
use Illuminate\Contracts\Validation\Rule;

/**
 * Custom validation rule to check if a customer is active
 */
class ActiveCustomer implements Rule
{
    protected $validator;
    protected $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->validator = new ActiveStatusValidator();
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return true; // Allow empty values (use 'required' rule separately if needed)
        }

        $result = $this->validator->validateCustomer($value);

        if (!$result['valid']) {
            $this->message = $result['message'];
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message ?? 'The selected customer is not active.';
    }
}
