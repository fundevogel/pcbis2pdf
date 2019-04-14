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
    public function __construct(string $cachePath, array $sortOrder)
    {
        // Defines path for cached data
        $this->cachePath = $cachePath;

        // Array holding desirable header sort order
        $this->sortOrder = $sortOrder;
    }

    /**
     *  Forcing classes to include specific functions
     */

    abstract public function getBook(string $isbn);
    abstract public function process(array $dataInput);


    /**
     *  Common functionality
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


    protected function sortArray($array)
    {
        $sortedArray = [];

        foreach ($this->sortOrder as $entry) {
            $sortedArray[$entry] = $array[$entry];
        }

        return $sortedArray;
    }
}
