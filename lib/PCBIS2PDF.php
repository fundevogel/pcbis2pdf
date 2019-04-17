<?php

/**
 * PCBIS2PDF - pcbis.de helper class
 *
 * @link https://github.com/Fundevogel/pcbis2pdf
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL v3
 */

namespace PCBIS2PDF;

use PCBIS2PDF\Providers\KNV;

use a;
use str;

/**
 * Class PCBIS2PDF
 *
 * Retrieves information from pcbis.de exported CSV files, sorts them out,
 * downloads book covers, enriches the results by adding information from
 * other sources, such as KNV's API (optionally, GoogleBooks API support)
 *
 * @package PCBIS2PDF
 */

class PCBIS2PDF
{
    /**
     * Current version number of PCBIS2PDF
     */
    const VERSION = '0.9.4';


    /**
     * Path to saved book cover images
     *
     * @var string
     */
    public $imagePath = './dist/images';


    /**
     * CSV input file headers in order of use when exporting with pcbis.de
     *
     * @var string
     */
    public $cachePath = './.cache';


    /**
     * CSV input file headers in order of use when exporting with pcbis.de
     *
     * @var array
     */
    public $headers = [
        'AutorIn',
        'Titel',
        'Verlag',
        'ISBN',
        'Einband',
        'Preis',
        'Meldenummer',
        'SortRabatt',
        'Gewicht',
        'Informationen',
        'Zusatz',
        'Kommentar'
    ];


    /**
     * Sort order for CSV output file headers
     *
     * @var array
     */
    public $sortOrder = [
        'AutorIn',
        'Titel',
        'Untertitel',
        'Verlag',
        'Mitwirkende',
        'Preis',
        'Erscheinungsjahr',
        'ISBN',
        'Altersempfehlung',
        'Inhaltsbeschreibung',
        'Informationen',
        'Einband',
        'Seitenzahl',
        'Abmessungen',
        '@Cover',
        'Cover DNB',
        'Cover KNV',
    ];

    public function __construct(string $imagePath = null, array $headers = null, string $mode = 'normal', string $lang = 'de')
    {
        if ($imagePath !== null) {
            $this->setImagePath($imagePath);
        }

        if ($headers !== null) {
            $this->setHeaders($headers);
        }

        $this->mode = $mode;

        $this->translations = json_decode(file_get_contents(__DIR__ . '/../languages/' . $lang . '.json'), true);
    }


    /**
     * Setters & getters
     */

    public function setImagePath(string $imagePath)
    {
        $this->imagePath = $imagePath;
    }

    public function getImagePath()
    {
        return $this->imagePath;
    }

    public function setCachePath(string $cachePath)
    {
        $this->cachePath = $cachePath;
    }

    public function getCachePath()
    {
        return $this->cachePath;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setSortOrder(array $sortOrder)
    {
        $this->sortOrder = $sortOrder;
    }

    public function getSortOrder()
    {
        return $this->sortOrder;
    }


    /**
     * Merges CSV files
     *
     * @param array $input - Source CSV files to read data from
     * @param string $output - Destination CSV file to write data to
     * @param boolean $hasHeader - Specifies whether or not a header row is present in source CSV files
     * @param string $delimiter - Delimiting character
     * @return array
     */
    public function mergeCSV(array $input = [], string $output = './src/Titelexport.csv', bool $hasHeader = false, $delimiter = ',')
    {
        if (empty($input)) {
            $input = glob('./src/csv/*.csv');
        }

        $count = 0;

        foreach ($input as $file) {
            if (($handle = fopen($file, 'r')) !== false) {
                while (($row = fgetcsv($handle, 0, ';')) !== false) {
                    $rowCount = count($row);

                    for ($i = 0; $i < $rowCount; $i++) {
                        $array[$count][] = $row[$i];
                    }
                    $count++;
                }
                fclose($handle);
            }
        }

        if ($hasHeader == true) {
            $headerArray = [];

            foreach ($array as $key => $value) {
                $headerArray[implode($value)] = $value;
            }

            $array = array_values($headerArray);
        }

        $handle = fopen($output, 'w');

        foreach ($array as $fields) {
            fputcsv($handle, $fields, $delimiter);
        }

        fclose($handle);
    }


    /**
     * Turns CSV data into a PHP array
     *
     * @param string $input - Source CSV file to read data from
     * @param string $delimiter - Delimiting character
     * @return array
     */
    public function CSV2PHP(string $input = './src/Titelexport.csv', string $delimiter = ',')
    {
        if ($input == null) {
            $input = $this->input;
        }

        if (!file_exists($input) || !is_readable($input)) {
            return false;
        }

        $data = [];

        if (($handle = fopen($input, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                $row = array_map('utf8_encode', $row);
                $data[] = array_combine($this->headers, $row);
            }
            fclose($handle);
        }
        return $data;
    }


    /**
     * Turns a PHP array into CSV file
     *
     * @param array $data - Source PHP array to read data from
     * @param string $output - Destination CSV file to write data to
     * @param string $delimiter - Delimiting character
     * @return stream
     */
    public function PHP2CSV(array $dataInput, string $output = './dist/data.csv', string $headerPrefix = null, string $headerSuffix = null, string $delimiter = ',')
    {
        $header = null;

        if (($handle = fopen($output, 'w')) !== false) {
            foreach ($dataInput as $row) {
                $headerArray = array_keys($row);

                // Optionally prefix all headers
                if ($headerPrefix !== null) {
                    foreach ($headerArray as $key => $value) {
                        $headerArray[$key] = $headerPrefix . $value;
                    }
                }

                // Optionally suffix all headers
                if ($headerSuffix !== null) {
                    foreach ($headerArray as $key => $value) {
                        $headerArray[$key] = $value . $headerSuffix;
                    }
                }

                if (!$header) {
                    fputcsv($handle, $headerArray, $delimiter);
                    $header = true;
                }
                fputcsv($handle, $row, $delimiter);
            }
            fclose($handle);
        }
        return true;
    }


    /**
     * Processes array containing general information,
     * applying functions to convert wanted data
     *
     * @param array $array - Source PHP array to read data from
     * @return array
     */
    private function generateInfo($array)
    {
    		$age = 'Keine Altersangabe';
    		$pageCount = '';
    		$year = '';

    		foreach ($array as $entry) {
    				// Remove garbled book dimensions
    				if (str::contains($entry, ' cm') || str::contains($entry, ' mm')) {
    						unset($array[array_search($entry, $array)]);
    				}

    				// Filtering age
    				if (str::contains($entry, ' J.') || str::contains($entry, ' Mon.')) {
    						$age = $this->convertAge($entry);
    						unset($array[array_search($entry, $array)]);
    				}

    				// Filtering page count
    				if (str::contains($entry, ' S.')) {
    						$pageCount = $this->convertPageCount($entry);
    						unset($array[array_search($entry, $array)]);
    				}

    				// Filtering year (almost always right at this point)
    				if (str::length($entry) == 4) {
    						$year = $entry;
    						unset($array[array_search($entry, $array)]);
    				}
    		}

    		$strings = $this->translations['information'];
    		$array = str::replace($array,
      			array_keys($strings),
      			array_values($strings)
    		);

    		$info = ucfirst(implode(', ', $array));

    		if (str::length($info) > 0) {
      			$info = str::replace($info, '.', '') . '.';
    		}

    		return [
      			$info,
      			$year,
      			$age,
      			$pageCount,
    		];
    }


    /**
     * Builds 'Titel' attribute as exported with pcbis.de
     *
     * @param string $string - Title string
     * @return string
     */
    private function convertTitle($string)
    {
    		// Input: Book title.
    		// Output: Book title
    		return str::substr($string, 0, -1);
    }


    /**
     * Builds 'Altersangabe' attribute as exported with pcbis.de
     *
     * @param string $string - Altersangabe string
     * @return string
     */
    private function convertAge($string)
    {
      	$string = str::replace($string, 'J.', 'Jahren');
      	$string = str::replace($string, 'Mon.', 'Monaten');
      	$string = str::replace($string, '-', ' bis ');
      	$string = str::replace($string, 'u.', '&');

      	return $string;
    }


    /**
     * Builds 'Seitenzahl' attribute as exported with pcbis.de
     *
     * @param string $string - Seitenzahl string
     * @return string
     */
    private function convertPageCount($string)
    {
    		return (int) $string;
    }


    /**
     * Builds 'Einband' attribute as exported with pcbis.de
     *
     * @param string $string - Einband string
     * @return string
     */
    private function convertBinding($string)
    {
    		$translations = $this->translations['binding'];
    		$string = $translations[$string];

    		return $string;
    }


    /**
     * Builds 'Preis' attribute as exported with pcbis.de
     *
     * @param string $string - Preis string
     * @return string
     */
    private function convertPrice($string)
    {
    		// Input: XX.YY EUR
    		// Output: XX,YY €
    		$string = str::replace($string, 'EUR', '€');
    		$string = str::replace($string, '.', ',');

    		return $string;
    }


    /**
     * Checks whether given ISBN consists of 10 or 13 digits
     * For more advanced ways to detect valid ISBNs,
     * see
     *
     * @param string $isbn - International Standard Book Number
     * @return boolean|InvalidArgumentException
     */
    public function validateISBN($isbn)
    {
        $cleanISBN = str::replace($isbn, '-', '');
        $length = str::length($cleanISBN);

        if ($length === 10 || $length === 13) {
            return true;
        }

        throw new \InvalidArgumentException('ISBN must consist of 10 or 13 digits, ' . $length . ' given (' . $isbn . ').');
    }


    /**
     * Downloads book cover from DNB
     *
     * .. if book cover for given ISBN doesn't exist already
     *
     * @param string $isbn - International Standard Book Number
     * @param string $fileName - Filename for the image to be downloaded
     * @return boolean
     */
    public function downloadCover(string $isbn, string $fileName = null, string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0')
    {
        try {
            $this->validateISBN($isbn);
        } catch (\InvalidArgumentException $e) {
            echo 'Error: ', $e->getMessage(), "\n";
            return false;
        }

        if ($fileName == null) {
            $fileName = $isbn;
        }

        $file = $this->imagePath . '/' . $fileName . '.jpg';

        if (file_exists($file)) {
            echo 'Book cover for ' . $isbn . ' already exists, skipping ..' . "\n";
            return true;
        }

        $url = 'https://portal.dnb.de/opac/mvb/cover.htm?isbn=' . $isbn;

        if ($handle = fopen($file, 'w')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            $result = parse_url($url);
            curl_setopt($ch, CURLOPT_REFERER, $result['scheme'] . '://' . $result['host']);
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
            $raw = curl_exec($ch);
            curl_close($ch);

            if (!$raw) {
                @unlink($file);
                return false;
            }

            fwrite($handle, $raw);
            fclose($handle);

            echo 'Downloading & saving "' . $isbn . '" as "' . $file . '" .. done!' . "\n";
            return true;
        }
        return false;
    }


    /**
     * Enriches an array with KNV information
     *
     * @param array $dataInput - Input that should be processed
     * @param string $cachePath - Path for local cache results
     * @return array
     */
    public function process(array $dataInput = null, string $cachePath = null, array $sortOrder = null, bool $includeProviders = false)
    {
        if ($dataInput == null) {
            $dataInput = $this->CSV2PHP();
        }

        if ($cachePath !== null) {
            $this->setCachePath($cachePath);
        }

        if ($sortOrder !== null) {
            $this->setSortOrder($sortOrder);
        }

        $dataOutput = [];

        foreach ($dataInput as $array) {
            // Continue the foreach loop with the next book upon invalid ISBN,
            // which only ever applies to self-generated CSV files (but whatever)
            try {
                $this->validateISBN($array['ISBN']);
            } catch (\InvalidArgumentException $e) {
                echo 'Error: ', $e->getMessage(), "\n";
                continue;
            }

            // Gathering & processing generic book information
            $infoString = $array['Informationen'];
            $infoArray = str::split($infoString, ';');

            if (count($infoArray) == 1) {
                $infoArray = str::split($infoString, '.');
            }

            // Extracting variables from $infoArray
            list(
                $info,
                $year,
                $age,
                $pageCount
            ) = $this->generateInfo($infoArray);

            // Title, cover & image download
            $title = $this->convertTitle($array['Titel']);
            $slug = str::slug($title);

            $cover = '';
            $download = $this->downloadCover($array['ISBN'], $slug);
            $imageName = $slug . '.jpg';

            if ($download && file_exists($imagePath = $this->imagePath . '/' . $imageName)) {
                // Although InDesign seems to support relative paths for images,
                // we don't want to go through specifics by providing their absolute path
                $cover = $this->mode == 'indesign' ? realpath($imagePath) : $imageName;
            }

            $coverDNB = 'https://portal.dnb.de/opac/mvb/cover.htm?isbn=' . $array['ISBN'];

            $array = a::update($array, [
                // Updating existing entries + adding blanks to prevent columns from shifting
                'Einband' => $this->convertBinding($array['Einband']),
                'Preis' => $this->convertPrice($array['Preis']),
                'Titel' => $title,
                'Untertitel' => '',
                'Altersempfehlung' => $age,
                'Erscheinungsjahr' => $year,
                'Seitenzahl' => $pageCount,
                'Abmessungen' => '',
                'Mitwirkende' => '',
                'Informationen' => $info,
                'Inhaltsbeschreibung' => '',
                '@Cover' => $cover,
                'Cover DNB' => $coverDNB,
                'Cover KNV' => '',
            ]);

            $data[] = $array;
        }

        try {
            $KNV = new KNV(
              $this->cachePath,
              $this->sortOrder
            );

            $dataOutput = $KNV->process($data);

            if ($includeProviders == true) {
                $dataOutput = includeProviders($dataOutput);
            }
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }

        echo 'Operation was successful!' . "\n";
        return a::sort($dataOutput, 'AutorIn', 'asc');
    }


    /**
     * Enriches an array with specific provider information
     *
     * @param array $dataInput - Input that should be processed
     * @param string $cachePath - Path for local cache results
     * @return array
     */
    private function includeProviders($dataInput = null, string $cachePath = null)
    {
        if ($dataInput == null) {
            throw new \Exception('No data given to process!');
        }

        if ($cachePath == null) {
          throw new \Exception('No cache path specified!');
        }

        $providers = array_map(function ($filePath) {
            $fileName = basename($filePath, '.php');
            return $fileName;
        }, glob(__DIR__ . '/Providers/*.php'));

        // KNV is used by default, so we don't need to include it
        unset($providers[array_search('KNV', $providers)]);

        try {
            foreach ($providers as $provider) {
                $providerName = ucfirst(strtolower($provider));
                $className = 'PCBIS2PDF\\Providers\\' . $providerName;

                if (!class_exists($className)) {
                    continue;
                }

                $classObject = new $className($this->cachePath);

                if (!$classObject instanceof ProviderAbstract || !is_callable([$classObject, 'process'])) {
                    continue;
                }

                $data = $classObject->process($dataInput);

                if ($data) {
                    echo 'Operation by ' . $providerName . ' was successful!' . "\n";
                    break;
                }
            }

            return a::sort($dataOutput, 'AutorIn', 'asc');
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
}
