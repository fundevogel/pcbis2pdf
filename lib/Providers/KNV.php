<?php

namespace PCBIS2PDF\Providers;

use PCBIS2PDF\ProviderAbstract;
use PCBIS2PDF\Helpers\Butler;

/**
 * Class KNV
 *
 * Holds functions to collect & process KNV gibberish to useful information
 *
 * @package PCBIS2PDF\Providers
 */

class KNV extends ProviderAbstract
{
    /**
     * Returns raw book data from KNV
     *
     * .. if book for given ISBN exists
     *
     * @param string $isbn
     * @return array|Exception
     */
    public function getBook(string $isbn)
    {
        try {
            Butler::validateISBN($isbn);

            // TODO: TypeException for anything but arrays
            $login = $this->login;

            if ($login === null) {
                $provider = Butler::lower(basename(__FILE__, '.php'));
                $login = Butler::getLogin($provider);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        $client = new \SoapClient('http://ws.pcbis.de/knv-2.0/services/KNVWebService?wsdl', [
            'soap_version' => SOAP_1_2,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
            'cache_wsdl' => WSDL_CACHE_BOTH,
            'trace' => true,
            'exceptions' => true,
        ]);

        // For getting started with KNV's (surprisingly well documented) german API,
        // see http://www.knv.de/fileadmin/user_upload/IT/KNV_Webservice_2018.pdf
        $query = $client->WSCall([
            // Login using credentials provided by `knv.login.json`
            'LoginInfo' => $login,
            // Starting a new database query
            'Suchen' => [
                'Datenbank' => [
                // Basically searching all databases they got
                    'KNV',
                    'KNVBG',
                    'BakerTaylor',
                    'Gardners',
                ],
                'Suche' => [
                    'SimpleTerm' => [
                        // Simple search suffices as from exported CSV,
                        // we already know they know .. you know?
                        'Suchfeld' => 'ISBN',
                        'Suchwert' => $isbn,
                        'Schwert2' => '',
                        'Suchart' => 'Genau'
                    ],
                ],
            ],
            // Reading the results of the query above
            'Lesen' => [
                // Returning the first result is alright, since given ISBN is unique
                'SatzVon' => 1,
                'SatzBis' => 1,
                'Format' => 'KNVXMLLangText',
                'AuswahlMultimediaDaten' => [
                    // We only want the best cover they got - ZOOM mode ON!
                    'mmDatenLiefern' => true,
                    'mmVarianteFilter' => 'zoom',
                ],
            ],
            // .. and logging out, that's it!
            'Logout' => true
        ]);

        // Getting raw XML response & preparing it to be loaded by SimpleXML
        $result = $query->Daten->Datensaetze->Record->ArtikelDaten;
        $result = Butler::replace($result, '&', '&amp;');

        // XML to JSON to PHP array - we want its last entry
        $xml = simplexml_load_string($result);
        $json = json_encode($xml);
        $array = (json_decode($json, true));

        return Butler::last($array);
    }


    /**
     * Processes array (fetched from KNV's API) & builds 'AutorIn' attribute
     *
     * .. if it exists
     *
     * @param array $array - Source PHP array to read data from
     * @param array $arrayCSV - Second array, usually provided by CSV source file
     * @return string
     */
    private function getAuthor(array $array, array $arrayCSV = ['Titel' => ''])
    {
        if (Butler::missing($array, ['AutorSachtitel'])) {
            return '';
        }

        if ($arrayCSV['Titel'] == $array['AutorSachtitel']) {
            return '';
        }

        return $array['AutorSachtitel'];
    }


    /**
     * Processes array (fetched from KNV's API) & builds 'Untertitel' attribute
     *
     * .. if it exists
     *
     * @param array $array - Source PHP array to read data from
     * @return string
     */
    private function getSubtitle(array $array)
    {
        if (Butler::missing($array, ['Utitel'])) {
            return '';
        }

        if ($array['Utitel'] == null) {
            return '';
        }

        return $array['Utitel'];
    }


    /**
     * Processes array (fetched from KNV's API) & builds 'Erscheinungsjahr' attribute
     *
     * .. if it exists
     *
     * @param array $array - Source PHP array to read data from
     * @return string
     */
    private function getYear(array $array)
    {
        if (Butler::missing($array, ['Erschjahr'])) {
            return '';
        }

        return $array['Erschjahr'];
    }


    /**
     * Processes array (fetched from KNV's API) & builds 'Inhaltsbeschreibung' attribute
     *
     * .. if it exists
     *
     * @param array $array - Source PHP array to read data from
     * @return string
     */
    private function getText(array $array)
    {
        if (Butler::missing($array, ['Text1'])) {
            return 'Keine Beschreibung vorhanden!';
        }

        $textArray = Butler::split($array['Text1'], 'º');

        foreach ($textArray as $index => $entry) {
            $entry = htmlspecialchars_decode($entry);
            $entry = Butler::replace($entry, '<br><br>', '. ');
            $entry = Butler::unhtml($entry);
            $textArray[$index] = $entry;

            if (Butler::length($textArray[$index]) < 130 && count($textArray) > 1) {
                unset($textArray[array_search($entry, $textArray)]);
            }
        }
        return Butler::first($textArray);
    }


    /**
     * Processes array (fetched from KNV's API) & builds 'Mitwirkende' attribute
     *
     * .. if it exists
     *
     * @param array $array - Source PHP array to read data from
     * @return string
     */
    private function getParticipants(array $array)
    {
        if (Butler::missing($array, ['Mitarb'])) {
            return '';
        }

        return $array['Mitarb'];
    }


    /**
     * Converts 'Abmessungen' attribute from millimeters to centimeters
     *
     * @param string $string - Abmessungen string
     * @return string
     */
    private function convertMM(string $string)
    {
        $string = $string / 10;
        $string = Butler::replace($string, '.', ',');

        return $string . 'cm';
    }


    /**
     * Processes array & builds 'Abmessungen' attribute as fetched from KNV's API
     *
     * @param array $array - Source PHP array to read data from
     * @return string
     */
    private function getDimensions(array $array)
    {
        if (Butler::missing($array, ['Breite'])) {
            return '';
        }

        if (Butler::missing($array, ['Hoehe'])) {
            return '';
        }

        $width = $this->convertMM($array['Breite']);
        $height = $this->convertMM($array['Hoehe']);

        return $width . ' x ' . $height;
    }


    /**
     * Returns cover URL from KNV
     *
     * .. always!
     *
     * @param array $array
     * @return string
     */
    private function getCover(array $array)
    {
        return $array['MULTIMEDIA']['MMUrl'];
    }


    /**
     * Enriches an array with KNV information
     *
     * @param array $dataInput - Input that should be processed
     * @return array
     */
    public function processData(array $dataInput = null)
    {
        if ($dataInput == null) {
            throw new \InvalidArgumentException('No data to process!');
        }

        $dataOutput = [];

        foreach ($dataInput as $array) {
            try {
                $book = $this->accessCache($array['ISBN'], 'KNV');
            } catch (\Exception $e) {
                echo 'Error: ' . $e->getMessage(), "\n";
                continue;
            }

        try {
            $arrayKNV = [
                'AutorIn' => $this->getAuthor($book, $array),
                'Untertitel' => $this->getSubtitle($book),
                'Mitwirkende' => $this->getParticipants($book),
                'Erscheinungsjahr' => $this->getYear($book),
                'Inhaltsbeschreibung' => $this->getText($book),
                'Abmessungen' => $this->getDimensions($book),
                'Cover KNV' => $this->getCover($book),
            ];
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage(), "\n";
        }

        $array = Butler::update($array, array_filter($arrayKNV, 'strlen'));

        $dataOutput[] = $array;
        }

        return $dataOutput;
    }
}
