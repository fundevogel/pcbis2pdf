<?php

namespace PCBIS2PDF;

use Doctrine\Common\Cache\FilesystemCache;

/**
 * Class ProviderAbstract
 *
 * Abstract class for all providers of book information
 *
 * @package PCBIS2PDF
 */

abstract class ProviderAbstract
{
    public function __construct(string $cachePath = './.cache')
    {
        // Defines path for cached data
        $this->cachePath = $cachePath;
    }


    /**
     * Forcing classes to include specific functions
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
    abstract public function processData(array $dataInput);


    /**
     * Common functionality
     */

    /**
     * Checks if `*.login.json` file for given provider exists an returns
     * array with login information if that's the case
     *
     * @param string $provider - Provider name, eg 'KNV', 'Google', etc
     * @return array|Exception
     */
    protected function getLogin(string $provider)
    {
        if (file_exists($file = realpath('./' . $provider . '.login.json'))) {
            $json = file_get_contents($file);
            $array = json_decode($json, true);

            return $array;
        }

        throw new \Exception('No "' . $provider . '.login.json" found.');
    }


    /**
     * Fetches book information from cache if they exist, otherwise loads them & saves to cache
     *
     * @param string $isbn - A given book's ISBN
     * @param string $identifier - Cache name to distinguish cache entries from one another
     * @return array|boolean
     */
    protected function accessCache($isbn, $identifier)
    {
        // `accessCache` is always loaded after ISBN validation, so there's no need at this point ..
        $driver = new FilesystemCache($this->cachePath);
        $id = implode('-', [$identifier, md5($isbn)]);

        if ($driver->contains($id)) {
            echo 'Loading "' . $isbn . '" from "' . $identifier . '" cache .. done!', "\n";
        } else {
            // .. however, if something goes wrong with the API call,
            // we don't want to save an empty response:
            try {
                $result = $this->getBook($isbn);
            } catch (\Exception $e) {
                echo 'Error: ' . $e->getMessage(), "\n";
                return false;
            }

            $driver->save($id, $result);
            echo 'Downloading & saving "' . $isbn . '" to "' . $identifier . '" cache .. done!', "\n";
        }

        return $driver->fetch($id);
    }
}
