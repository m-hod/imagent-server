<?php

namespace App\Services\Common;

class Utils
{
    public static function arraySome(array $array, callable $fn)
    {
        foreach ($array as $value) {
            if ($fn($value)) {
                return true;
            }
        }
        return false;
    }

    public static function arrayEvery(array $array, callable $fn)
    {
        foreach ($array as $value) {
            if (!$fn($value)) {
                return false;
            }
        }

        return true;
    }

    public static function arrayFind(array $array, $id)
    {
        foreach ($array as $key => $value) {
            if ($id == $value['id']) {
                return [
                    'key' => $key,
                    'value' => $value,
                ];
            }
        }

        return [
            'key' => null,
            'value' => null,
        ];
    }
}
