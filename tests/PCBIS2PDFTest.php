<?php

/**
 * PCBIS2PDF - pcbis.de helper for use with DTP software
 *
 * @link https://github.com/Fundevogel/pcbis2pdf
 * @license GPL v3
 */

namespace PCBIS2PDF\Tests;

use PCBIS2PDF\PCBIS2PDF;
use PHPUnit\Framework\TestCase;

class PCBIS2PDFTest extends TestCase
{
    private static $object;
    private static $filePath;

    public static function setUpBeforeClass(): void
    {
        self::$object = new PCBIS2PDF;
        self::$filePath = './tests/Resources';

        // Cleaning up all generated files for fresh start
        array_map('unlink', glob(self::$filePath . '/*_actual.{csv,jpg}', GLOB_BRACE));
    }


    public function test_mergeCSV_withHeader() {
        /*
         * Preparations
         */
        $array = [
            self::$filePath . '/test_mergeCSV_withHeader_1.csv',
            self::$filePath . '/test_mergeCSV_withHeader_2.csv',
        ];
        $expected = self::$filePath . '/test_mergeCSV_withHeader_expected.csv';
        $actual = self::$filePath . '/test_mergeCSV_withHeader_actual.csv';

        self::$object->mergeCSV($array, $actual, true, ';');


        /*
         * Assertions
         */
        $this->assertFileEquals($expected, $actual);
    }


    public function test_mergeCSV_withoutHeader() {
        /*
         * Preparations
         */
        $array = [
            self::$filePath . '/test_mergeCSV_1.csv',
            self::$filePath . '/test_mergeCSV_2.csv',
        ];
        $expected = self::$filePath . '/test_mergeCSV_expected.csv';
        $actual = self::$filePath . '/test_mergeCSV_actual.csv';

        self::$object->mergeCSV($array, $actual, false, ';');


        /*
         * Assertions
         */
        $this->assertFileEquals($expected, $actual);

    }


    public function test_CSV2PHP_Valid() {
        /*
         * Preparations
         */
        $expected = [
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
        $actual = self::$object->CSV2PHP(self::$filePath . '/test_CSV2PHP.csv', ';');


        /*
         * Assertions
         */
        $this->assertEquals($expected, $actual);
    }


    public function test_CSV2PHP_Invalid()
    {
        /*
         * Assertions
         */
        $this->assertFalse(self::$object->CSV2PHP('momo.csv'));
    }


    public function test_downloadCover() {
        /*
         * Preparations
         */
        $expected = self::$filePath . '/test_downloadCover_expected.jpg';
        $actual = self::$filePath . '/test_downloadCover_actual.jpg';

        self::$object->setImagePath(self::$filePath);
        self::$object->downloadCover('978-3-522-20260-2', basename($actual, '.jpg'));


        /*
         * Assertions
         */
        $this->assertFileEquals($expected, $actual);
    }


    public function test_PHP2CSV_Valid() {
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
        $expected = self::$filePath . '/test_PHP2CSV_expected.csv';
        $actual = self::$filePath . '/test_PHP2CSV_actual.csv';

        self::$object->PHP2CSV($rawData, $actual);


        /*
         * Assertions
         */
        $this->assertFileEquals($expected, $actual);
    }


    public function test_PHP2CSV_Invalid()
    {
        /*
         * Preparations
         */
        $this->expectException(\InvalidArgumentException::class);


        /*
         * Assertions
         */
        self::$object->PHP2CSV();
    }


    public function test_processData_Valid()
    {
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

        $expected = [
            [
                'AutorIn' => 'Ende, Michael',
                'Titel' => 'Die unendliche Geschichte',
                'Untertitel' => 'Ausgezeichnet mit dem Jugendbuchpreis Buxtehuder Bulle 1979 u. a',
                'Verlag' => 'Thienemann Verlag',
                'Mitwirkende' => 'Mitarbeit: Schöffmann-Davidov, Eva',
                'Preis' => '20,00 €',
                'Erscheinungsjahr' => '2019',
                'ISBN' => '978-3-522-20260-2',
                'Altersempfehlung' => 'von 12 bis 99 Jahren',
                'Inhaltsbeschreibung' => 'Der Welt-Bestseller von Michael Ende für Kinder und Jugendliche ab 12 Jahren.. Bastian Balthasar Bux entdeckt in einer Buchhandlung ein geheimnisvolles Buch, "Die unendliche Geschichte". Begeistert liest er von den Abenteuern des Helden Atréju und seinem gefährlichen Auftrag: Phantásien und seine Herrscherin, die Kindliche Kaiserin, zu retten. Zunächst nur Zuschauer, findet er sich unversehens selbst in Phantásien wieder. TU WAS DU WILLST lautet die Inschrift auf dem Symbol der unumschränkten Herrschaftsgewalt. Doch was dieser Satz in Wirklichkeit bedeutet, erfährt Bastian erst nach einer langen Suche. Denn seine wahre Aufgabe ist es nicht, Phantásien zu beherrschen, sondern wieder herauszufinden. Wie aber verlässt man ein Reich, das keine Grenzen hat?. ',
                'Informationen' => 'Mit Illustrationen.',
                'Einband' => 'gebunden',
                'Seitenzahl' => 480,
                'Abmessungen' => '15,2cm x 22,2cm',
                '@Cover' => '',
                'Cover DNB' => '',
            ]
        ];

        $object = new PCBIS2PDF;
        $actual = $object->processData($rawData, false);


        /*
         * Assertions
         */
        $this->assertIsString(filter_var($actual[0]['Cover KNV'], FILTER_VALIDATE_URL));


        /*
         * Preparations
         */
        unset($actual[0]['Cover KNV']);


        /*
         * Assertions
         */
        $this->assertEquals($expected, $actual);
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
