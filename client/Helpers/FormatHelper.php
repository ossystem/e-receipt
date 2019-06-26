<?php

namespace Helpers;

abstract class FormatHelper{

    public static function format_dec($number, $decimals = 2, $dec_point = ".", $thousands_sep = ""){
        return number_format($number, $decimals, $dec_point, $thousands_sep);
    }

}