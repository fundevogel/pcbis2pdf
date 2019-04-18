<?php

namespace PCBIS2PDF\Helpers;

use str;

/**
 * Class Do
 *
 * This class contains useful helper functions
 *
 * @package PCBIS2PDF
 */

class Butler
{
    /**
     * Checks whether given ISBN consists of 10 or 13 digits
     * For more advanced ways to detect valid ISBNs,
     * see https://github.com/biblys/isbn
     *
     * @param string $isbn - International Standard Book Number
     * @return boolean|Exception
     */
    public static function validateISBN($isbn)
    {
        $cleanISBN = str::replace($isbn, '-', '');
        $length = str::length($cleanISBN);

        if ($length === 10 || $length === 13) {
            return true;
        }

        throw new \InvalidArgumentException('ISBN must consist of 10 or 13 digits, ' . $length . ' given (' . $isbn . ').');
    }
}
