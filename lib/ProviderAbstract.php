<?php

namespace PCBIS2PDF;

use Doctrine\Common\Cache\FilesystemCache;
use Biblys\Isbn\Isbn;

/**
 * Class ProviderAbstract
 *
 * Abstract class for all providers of book information
 *
 * @package PCBIS2PDF
 */

abstract class ProviderAbstract
{
    public function __construct(string $cachePath = './.cache', array $sortOrder = null)
    {
        // Defines path for cached data
        $this->cachePath = $cachePath;

        // Array holding desirable header sort order
        if ($sortOrder !== null) {
            $this->sortOrder = $sortOrder;
        }
    }


    /**
     *  Forcing classes to include specific functions
     */

     /**
      * Returns raw book data from provider's API
      *
      * @param string $isbn
      */
    abstract public function getBook(string $isbn);


    /**
     * Enriches basic book data with information from provider's API
     *
     * @param array $dataInput - Input that should be processed
     */
    abstract public function process(array $dataInput);


    /**
     *  Common functionality
     */

    /**
     * Fetches book information from cache if they exist, otherwise loads them & saves to cache
     *
     * @param string $isbn - A given book's ISBN
     * @param string $identifier - Cache name to distinguish cache entries from one another
     * @return array
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
     * @param array $array - Input that should be sorted
     * @return array
     */
    protected function sortArray($array)
    {
        $sortedArray = [];

        foreach ($this->sortOrder as $entry) {
            $sortedArray[$entry] = $array[$entry];
        }

        return $sortedArray;
    }

    protected function validateISBN($isbn)
    {
        try {
            $object = new PCBIS2PDF;
            $object->validateISBN($isbn);
        } catch (\InvalidArgumentException $e) {
            echo 'Error: ' . $e->getMessage(), "\n";
            return false;
        }

        return true;
    }
}
