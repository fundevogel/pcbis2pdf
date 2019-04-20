<?php

namespace PCBIS2PDF\Providers;

use PCBIS2PDF\ProviderAbstract;
use PCBIS2PDF\Helpers\Butler;
use Scriptotek\GoogleBooks\GoogleBooks;

use a;
use str;

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
     * @param string $isbn
     * @return array|boolean
     */
    public function getBook(string $isbn, string $apiKey = null)
    {
        try {
            Butler::validateISBN($isbn);

            if ($apiKey === null) {
                $provider = str::lower(basename(__FILE__, '.php'));
                $login = $this->getLogin($provider);
                $apiKey = $login['key'];
            }
        } catch (\Exception $e) {
            throw $e;
        }

        $client = new GoogleBooks($apiKey);

        if ($query = $client->volumes->byIsbn($isbn)) {
            $array = (array) $query->volumeInfo;
            return $array;
        }

        return false;
    }


    /**
     * Enriches an array with Google Books information
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
                $book = $this->accessCache($array['ISBN'], 'Google');
                $arrayGoogle = [
                    // 'AutorIn' => a::missing($book, ['authors']) ? '' : implode(' & ', $book['authors']),
                    // 'Datum' => a::missing($book, ['publishedDate']) ? '' : date('d.m.Y', strtotime($book['publishedDate'])),
                    // 'Seitenzahl' => a::missing($book, ['pageCount']) ? '' : $book['pageCount'],
                    // 'Cover Google' => '',
                ];
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage(), "\n";
            }

            $array = a::update($array, array_filter($arrayGoogle, 'strlen'));

            $dataOutput[] = $array;
        }

        return $dataOutput;
    }
}
