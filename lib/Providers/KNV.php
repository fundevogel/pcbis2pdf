<?php

namespace PCBIS2PDF\Providers;

use PCBIS2PDF\ProviderAbstract;

use a;
use str;

/**
 * Class KNV
 *
 * Holds functions to convert KNV gibberish to useful defaults
 *
 * @package BookRecommendations\Providers
 */

class KNV extends ProviderAbstract
{

    /**
     * Returns raw book data from KNV
     *
     * .. if it exists
     *
     * @param Array $array
     * @return String
     */
    public function getBook($isbn)
    {
        $json = file_get_contents(basename('./knv.login.json'));
        $login = json_decode($json, true);

        $client = new \SoapClient('http://ws.pcbis.de/knv-2.0/services/KNVWebService?wsdl', [
            'soap_version' => SOAP_1_1,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
            'cache_wsdl' => WSDL_CACHE_BOTH,
            'trace' => true,
            'exceptions' => true,
        ]);

        $query = $client->WSCall([
    				'LoginInfo' => $login,
            'Suchen' => [
                'Datenbank' => [
                    'KNV',
                    'KNVBG',
                    'BakerTaylor',
                    'Gardners',
                ],
                'Suche' => [
                    'SimpleTerm' => [
                        'Suchfeld' => 'ISBN',
                        'Suchwert' => $isbn,
                        'Schwert2' => '',
                        'Suchart' => 'Genau'
                    ],
                ],
            ],
            'Lesen' => [
                'SatzVon' => 1,
                'SatzBis' => 1,
    						'Format' => 'KNVXMLLangText',
                'AuswahlMultimediaDaten' => [
                    'mmDatenLiefern' => true,
                    'mmVarianteFilter' => 'zoom',
                ],
            ],
            'Logout' => true
        ]);

    		$result = $query->Daten->Datensaetze->Record->ArtikelDaten;
    		$result = str::replace($result, '&', '&amp;');

    		$xml = simplexml_load_string($result);
    		$json = json_encode($xml);
    		$array = (json_decode($json, true));

    		return a::last($array);
    }


    /**
     * Returns subtitle from KNV
     *
     * .. if it exists
     *
     * @param Array $array
     * @return String
     */
    private function getAuthor($array, $arrayCSV = ['Titel' => ''])
    {
    		if (a::missing($array, ['AutorSachtitel'])) {
    				return '';
    		}

    		if ($arrayCSV['Titel'] == $array['AutorSachtitel']) {
    				return '';
    		}

    		return $array['AutorSachtitel'];
    }


    /**
     * Returns subtitle from KNV
     *
     * .. if it exists
     *
     * @param Array $array
     * @return String
     */
    private function getSubtitle($array)
    {
        if (a::missing($array, ['Utitel'])) {
            return '';
        }

    		if ($array['Utitel'] == null) {
    		    return '';
        }

        return $array['Utitel'];
    }


    private function getYear($array)
    {
        if (a::missing($array, ['Erschjahr'])) {
            return '';
        }

        return $array['Erschjahr'];
    }


    /**
     * Returns descriptive text from KNV
     *
     * .. if it exists
     *
     * @param Array $array
     * @return String
     */
    private function getText($array)
    {
        if (a::missing($array, ['Text1'])) {
            return 'Keine Beschreibung vorhanden!';
        }

        $textArray = str::split($array['Text1'], 'º');

        foreach ($textArray as $index => $entry) {
            $entry = htmlspecialchars_decode($entry);
            $entry = str::replace($entry, '<br><br>', '. ');
            $entry = str::unhtml($entry);
            $textArray[$index] = $entry;

            if (str::length($textArray[$index]) < 130 && count($textArray) > 1) {
                unset($textArray[array_search($entry, $textArray)]);
            }
        }
        return a::first($textArray);
    }


    /**
     * Returns participant(s) from KNV
     *
     * .. if it/they exist(s)
     *
     * @param Array $array
     * @return String
     */
    private function getParticipants($array)
    {
    		if (a::missing($array, ['Mitarb'])) {
    			return '';
    		}

    		return $array['Mitarb'];
    }


    /**
     * Returns book dimensions from KNV
     *
     * .. if width & height exist
     *
     * @param Array $array
     * @return String
     */
    private function convertMM($string)
    {
    		$string = $string / 10;
    		$string = str::replace($string, '.', ',');

    		return $string . 'cm';
    }


    private function getDimensions($array)
    {
    		if (a::missing($array, ['Breite'])) {
    				return '';
    		}

    		if (a::missing($array, ['Hoehe'])) {
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
     * @param Array $array
     * @return String
     */
    private function getCover($array)
    {
    		return $array['MULTIMEDIA']['MMUrl'];
    }


    public function process(array $dataInput = null)
    {
        if ($dataInput == null) {
            $dataInput = $this->dataInput;
        }

        $dataOutput = [];

        foreach ($dataInput as $array) {
        		try {
        		    $book = $this->accessCache($array['ISBN'], 'KNV');
        		    $arrayKNV = [
        						'Erscheinungsjahr' => $this->getYear($book),
        		        'AutorIn' => $this->getAuthor($book, $array),
        		        'Untertitel' => $this->getSubtitle($book),
        						'Abmessungen' => $this->getDimensions($book),
        		        'Mitwirkende' => $this->getParticipants($book),
        		        'Inhaltsbeschreibung' => $this->getText($book),
        		        'Cover KNV' => $this->getCover($book),
        		    ];
        		} catch (Exception $e) {
        		    echo 'Error: ' . $e->getMessage();
        		}

        		$array = a::update($array, array_filter($arrayKNV, 'strlen'));

            $dataOutput[] = $this->sortArray($array);
        }

        return $dataOutput;
    }
}
