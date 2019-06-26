<?php

namespace Helpers;

abstract class ErrorCodes{

    private static $errorCodes = [
        0 => 'OK',
        1 => 'РРО не зареєстрований',
        2 => 'В документі зазначено реєстраційний код платника, що не дорівнює реєстраційному коду господарської одиниці',
        3 => 'Зміну для РРО наразі відкрито',
        4 => 'Зміну для РРО наразі не відкрито',
        5 => 'Останній документ, зареєстрований перед закриттям зміни, повинний бути Z-звітом',
        6 => 'Некоректний локальний номер чека'
    ];

    public static function getError($errorCode){
        $error = self::$errorCodes[$errorCode];

        return $error ? $error : "Невідома помилка : $errorCode";
    }

}