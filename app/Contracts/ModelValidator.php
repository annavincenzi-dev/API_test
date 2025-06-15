<?php

namespace App\Contracts;

interface ModelValidator
{
    public static function recordValidator($record, $updating = false);
    public static function recordValidatorMessages();
    public static function recordUpdatableFields();
}