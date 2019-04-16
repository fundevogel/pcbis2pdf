<?php

namespace PCBIS2PDF;

use \Doctrine\Common\Cache\FilesystemCache;

/**
 * Class ProviderAbstract
 *
 * Abstract class for all providers of book information
 *
 * @package PCBIS2PDF
 */

abstract class ProviderAbstract
{
    public function __construct(array $sortOrder, string $cachePath = './.cache')
    {
        // Defines path for cached data
        $this->cachePath = $cachePath;

        // Array holding desirable header sort order
        $this->sortOrder = $sortOrder;
    }


    /**
     *  Forcing classes to include specific functions
     */

     /**
      * Returns raw book data from provider's API
      *
      * @param String $isbn
      */
    abstract public function getBook(string $isbn);


    /**
     * Enriches basic book data with information from provider's API
     *
     * @param Array $dataInput - Input that should be processed
     */
    abstract public function process(array $dataInput);


    /**
     *  Common functionality
     */

    /**
     * Fetches book information from cache if they exist, otherwise loads them & saves to cache
     *
     * @param String $isbn - A given book's ISBN
     * @param String $identifier - Cache name to distinguish cache entries from one another
     * @return Array
     */
    protected function accessCache($isbn, $identifier)
    {
        $driver = new FilesystemCache($this->cachePath);
        $id = implode('-', [$identifier, md5($isbn)]);

        if ($driver->contains($id)) {
            echo 'Loading "' . $isbn . '" from "' . $identifier . '" cache .. done!' . "\n";
        } else {
            $result = $this->getBook($isbn);
            $driver->save($id, $result);
            echo 'Downloading & saving "' . $isbn . '" to "' . $identifier . '" cache .. done!' . "\n";
        }

        return $driver->fetch($id);
    }


    /**
     * Sorts a given array holding book information by certain sort order
     *
     * @param Array $array - Input that should be sorted
     * @return Array
     */
    protected function sortArray($array)
    {
        $sortedArray = [];

        foreach ($this->sortOrder as $entry) {
            $sortedArray[$entry] = $array[$entry];
        }

        return $sortedArray;
    }
}
