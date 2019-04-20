<?php

/**
 * PCBIS2PDF - pcbis.de helper for use with DTP software
 *
 * @link https://github.com/Fundevogel/pcbis2pdf
 * @license GPL v3
 */

namespace PCBIS2PDF\Tests;

use PCBIS2PDF\Helpers\Butler;
use PCBIS2PDF\Providers\KNV;
use PHPUnit\Framework\TestCase;

class KNVTest extends TestCase
{
    private static $object;
    private static $login;

    public static function setUpBeforeClass(): void
    {
        $credentials = [
            'VKN' => getenv('VKN'),
            'Benutzer' => getenv('BENUTZER'),
            'Passwort' => getenv('PASSWORT'),
        ];
        self::$login = array_filter($credentials) ? $credentials : Butler::getLogin('KNV');
        self::$object = new KNV(self::$login);
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
        $array = self::$object->processData($rawData)[0];


        /*
         * Assertions
         */
        $this->assertArrayHasKey('AutorIn', $array);
        $this->assertArrayHasKey('Untertitel', $array);
        $this->assertArrayHasKey('Mitwirkende', $array);
        $this->assertArrayHasKey('Erscheinungsjahr', $array);
        $this->assertArrayHasKey('Inhaltsbeschreibung', $array);
        $this->assertArrayHasKey('Abmessungen', $array);
        $this->assertArrayHasKey('Cover KNV', $array);


        /*
         * Preparations
         */
        $expected = [
          'Ende, Michael',
          'Ausgezeichnet mit dem Jugendbuchpreis Buxtehuder Bulle 1979 u. a',
          'Mitarbeit: Schöffmann-Davidov, Eva',
          '2019',
          'Der Welt-Bestseller von Michael Ende für Kinder und Jugendliche ab 12 Jahren.. Bastian Balthasar Bux entdeckt in einer Buchhandlung ein geheimnisvolles Buch, "Die unendliche Geschichte". Begeistert liest er von den Abenteuern des Helden Atréju und seinem gefährlichen Auftrag: Phantásien und seine Herrscherin, die Kindliche Kaiserin, zu retten. Zunächst nur Zuschauer, findet er sich unversehens selbst in Phantásien wieder. TU WAS DU WILLST lautet die Inschrift auf dem Symbol der unumschränkten Herrschaftsgewalt. Doch was dieser Satz in Wirklichkeit bedeutet, erfährt Bastian erst nach einer langen Suche. Denn seine wahre Aufgabe ist es nicht, Phantásien zu beherrschen, sondern wieder herauszufinden. Wie aber verlässt man ein Reich, das keine Grenzen hat?. ',
          '15,2cm x 22,2cm',
        ];

        $actual = [
            $array['AutorIn'],
            $array['Untertitel'],
            $array['Mitwirkende'],
            $array['Erscheinungsjahr'],
            $array['Inhaltsbeschreibung'],
            $array['Abmessungen'],
        ];


        /*
         * Assertions
         */
        $this->assertEquals($expected, $actual);
        $this->assertIsString(filter_var($array['Cover KNV'], FILTER_VALIDATE_URL));
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
