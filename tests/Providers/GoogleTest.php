<?php

/**
 * PCBIS2PDF - pcbis.de helper for use with DTP software
 *
 * @link https://github.com/Fundevogel/pcbis2pdf
 * @license GPL v3
 */

namespace PCBIS2PDF\Tests;

use PCBIS2PDF\Providers\Google;
use PHPUnit\Framework\TestCase;

class GoogleTest extends TestCase
{
    private static $object;

    public static function setUpBeforeClass(): void
    {
        self::$object = new Google;
    }


    public function test_getBook_Valid()
    {
        /*
         * Assertions
         */
        $this->assertIsArray(self::$object->getBook('0-14-031753-8')); // ISBN-10
        $this->assertIsArray(self::$object->getBook('9780140317534')); // ISBN-13
    }


    public function test_getBook_Invalid()
    {
        /*
         * Preparations
         */
        $this->expectException(\InvalidArgumentException::class);


        /*
         * Assertions
         */
        self::$object->getBook('12345');
    }


    // TODO: Improving this test
    public function test_processData_Valid() {
        /*
         * Preparations
         */
        $rawData = [
            [
                'AutorIn' => 'Ende, Michael',
                'Titel' => 'Die unendliche Geschichte.',
                'Verlag' => 'Thienemann Verlag',
                'ISBN' => '978-3-522-20260-2',
                'Einband' => 'GEB',
                'Preis' => '20.00 EUR',
                'Meldenummer' => '',
                'SortRabatt' => '30.0',
                'Gewicht' => '694 g',
                'Informationen' => ' 2019;480 S.;m. Illustr.;220 mm;von 12-99 J.;',
                'Zusatz' => '250',
                'Kommentar' => 'Ausgezeichnet mit dem Jugendbuchpreis Buxtehuder Bulle 1979 u. a',
            ]
        ];
        $array = self::$object->processData($rawData)[0];


        /*
         * Assertions
         */
        $this->assertIsArray($array);
    }


    public function test_processData_Invalid()
    {
        /*
         * Preparations
         */
        $this->expectException(\InvalidArgumentException::class);


        /*
         * Assertions
         */
        self::$object->processData();
    }
}
