<?php

namespace PCBIS2PDF\Providers;

use PCBIS2PDF\ProviderAbstract;
use Scriptotek\GoogleBooks\GoogleBooks;

use a;
// use str;

/**
 * Class Google
 *
 * Holds functions to collect & process Google Books information
 *
 * @package PCBIS2PDF\Providers
 */

class Google extends ProviderAbstract
{
    /**
     * Returns raw book data from Google Books API
     *
     * .. if book for given ISBN exists
     *
     * @param String $isbn
     * @return Array|Boolean
     */
    public function getBook($isbn)
    {
        $json = file_get_contents(basename('./google.login.json'));
        $login = json_decode($json, true);

        $client = new GoogleBooks($login->key);

        if ($query = $client->volumes->byIsbn($isbn)) {
    				$array = (array) $query->volumeInfo;
    				return $array;
    		}

    		return false;
    }


    /**
     * Enriches an array with Google Books information
     *
     * @param Array $dataInput - Input that should be processed
     * @return Array
     */
    public function process(array $dataInput = null)
    {
        if ($dataInput == null) {
            throw new \Exception('No data to process!');
        }

        $dataOutput = [];

        foreach ($dataInput as $array) {
        		try {
        		    $book = $this->accessCache($array['ISBN'], 'Google');
        		    $arrayGoogle = [
            				// 'AutorIn' => a::missing($book, ['authors']) ? '' : implode(' & ', $book['authors']),
        						// 'Datum' => a::missing($book, ['publishedDate']) ? '' : date('d.m.Y', strtotime($book['publishedDate'])),
        						// 'Seitenzahl' => a::missing($book, ['pageCount']) ? '' : $book['pageCount'],
        		        // 'Cover Google' => '',
        		    ];
        		} catch (Exception $e) {
        		    echo 'Error: ' . $e->getMessage();
        		}

        		$array = a::update($array, array_filter($arrayGoogle, 'strlen'));

            $dataOutput[] = $this->sortArray($array);
        }

        return $dataOutput;
    }
}
