<?php

namespace App\Support;

final class EmailValidation
{
    /**
     * Accept normal internet addresses and internal addresses such as
     * owner@tulipolympia, which are valid login identifiers for this app.
     */
    public static function rules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:255',
            'regex:/^[^@\s]+@[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)*$/',
        ];
    }
}
