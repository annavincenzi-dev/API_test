<?php

namespace App\Contracts;

interface ModelValidator
{
    //regole di validazione
    public static function recordValidator($record, $updating = false);
    //messaggi di validazione
    public static function recordValidatorMessages();
}