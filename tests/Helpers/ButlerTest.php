<?php

/**
 * PCBIS2PDF - pcbis.de helper for use with DTP software
 *
 * @link https://github.com/Fundevogel/pcbis2pdf
 * @license GPL v3
 */

namespace PCBIS2PDF\Tests;

use PCBIS2PDF\Helpers\Butler;
use PHPUnit\Framework\TestCase;

class ButlerTest extends TestCase
{
    public function test_validateISBN_Valid()
    {
        /*
         * Assertions
         */
        $this->assertTrue(Butler::validateISBN('0-14-031753-8'));
        $this->assertTrue(Butler::validateISBN('9780140317534'));
    }


    public function test_validateISBN_Invalid()
    {
        /*
         * Assertions
         */
        $this->expectException(\InvalidArgumentException::class);
        Butler::validateISBN('12345');
    }


    // TODO: Add test for `getLogin()`
}
