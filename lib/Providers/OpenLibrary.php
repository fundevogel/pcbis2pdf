<?php

namespace PCBIS2PDF\Providers;

use PCBIS2PDF\ProviderAbstract;
use PCBIS2PDF\Helpers\Butler;
use GuzzleHttp\Client;

use a;
use str;

/**
 * Class OpenLibrary
 *
 * Holds functions to collect & process OpenLibrary information
 *
 * @package PCBIS2PDF\Providers
 */

class OpenLibrary extends ProviderAbstract
{
    /**
     * Returns raw book data from OpenLibrary Books API
     *
     * .. if book for given ISBN exists
     *
     * @param string $isbn
     * @return array|boolean
     */
    public function getBook(string $isbn)
    {
        try {
            Butler::validateISBN($isbn);
        } catch (\Exception $e) {
            throw $e;
        }

        $client = new Client();

        $query = $client->request('GET', 'https://openlibrary.org/api/books', [
            'query' => [
                'bibkeys' => 'ISBN:' . str::replace($isbn, '-', ''),
                'format' => 'json',
                'jscmd' => 'data',
            ],
        ]);

        if ($query->getStatusCode() == 200) {
            $string = (string) $query->getBody();
            $array = json_decode($string, true);

            if (!empty($array)) {
                return a::first($array);
            }
    		}

    		return false;
    }


    /**
     * Enriches an array with OpenLibrary information
     *
     * @param array $dataInput - Input that should be processed
     * @return array
     */
    public function process(array $dataInput = null)
    {
        if ($dataInput == null) {
            throw new \Exception('No data to process!');
        }

        $dataOutput = [];

        foreach ($dataInput as $array) {
        		try {
        		    $book = $this->accessCache($array['ISBN'], 'OpenLibrary');
        		    $arrayOpenLibrary = [
                    // 'Datum' => a::missing($book, ['publish_date']) ? '' : $book['publish_date'],
                    // 'Seitenzahl' => a::missing($book, ['number_of_pages']) ? '' : $book['number_of_pages'],
        		        // 'Cover OpenLibrary' => '',
        		    ];
        		} catch (Exception $e) {
        		    echo 'Error: ' . $e->getMessage();
        		}

        		$array = a::update($array, array_filter($arrayOpenLibrary, 'strlen'));

            $dataOutput[] = $this->sortArray($array);
        }

        return $dataOutput;
    }
}
