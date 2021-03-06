<?php

/**
 * PCBIS2PDF - pcbis.de helper library
 *
 * @link https://github.com/Fundevogel/pcbis2pdf
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL v3
 */

namespace PCBIS2PDF;

use PCBIS2PDF\Providers\KNV;
use PCBIS2PDF\Helpers\Butler;

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
    const VERSION = '1.0.2';


    /**
     * Path to downloaded book cover images
     *
     * @var string
     */
    private $imagePath = './dist/images';


    /**
     * User-Agent used when downloading book cover images
     *
     * @var string
     */
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0';


    /**
     * CSV input file headers in order of use when exporting with pcbis.de
     *
     * @var string
     */
    private $cachePath = './.cache';


    /**
     * CSV input file headers in order of use when exporting with pcbis.de
     *
     * @var array
     */
    private $headers = [
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
     * Prefix to be added to each CSV output file header
     *
     * @var null
     */
    private $headerPrefix = null;


    /**
     * Suffix to be added to each CSV output file header
     *
     * @var null
     */
    private $headerSuffix = null;


    /**
     * Sort order for CSV output file headers
     *
     * @var array
     */
    private $sortOrder = [
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


    /**
     * Work mode ('normal' & 'indesign' available)
     *
     * @var string
     */
    private $mode = 'normal';


    public function __construct(array $login = null, string $lang = 'de')
    {
        // Credentials for restricted APIs
        $this->login = $login;

        // Feel free to open a pull request to include additional language variables
        // TODO: Extending language variables by local files or other means (eg passing an array)
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

    public function setUserAgent(string $userAgent)
    {
        $this->userAgent = $userAgent;
    }

    public function getUserAgent()
    {
        return $this->userAgent;
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

    public function setHeaderPrefix(string $headerPrefix)
    {
        $this->headerPrefix = $headerPrefix;
    }

    public function getHeaderPrefix()
    {
        return $this->headerPrefix;
    }

    public function setHeaderSuffix(string $headerSuffix)
    {
        $this->headerSuffix = $headerSuffix;
    }

    public function getHeaderSuffix()
    {
        return $this->headerSuffix;
    }

    public function setSortOrder(array $sortOrder)
    {
        $this->sortOrder = $sortOrder;
    }

    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    public function setMode(string $mode)
    {
        $this->mode = $mode;
    }

    public function getMode()
    {
        return $this->mode;
    }


    /**
     * Merges CSV files
     *
     * @param array $input - Source CSV files to read data from
     * @param string $output - Destination CSV file to write data to
     * @param boolean $hasHeader - Specifies whether or not a header row is present in source CSV files
     * @param string $delimiter - Delimiting character
     * @return Stream
     */
    public function mergeCSV(array $inputCSV = null, string $outputCSV = './src/Titelexport.csv', bool $hasHeader = false, $delimiter = ',')
    {
        if ($inputCSV === null) {
            $inputCSV = glob('./src/csv/*.csv');
        }

        $count = 0;

        foreach ($inputCSV as $file) {
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

        if ($hasHeader === true) {
            $headerArray = [];

            foreach ($array as $key => $value) {
                $headerArray[implode($value)] = $value;
            }

            $array = array_values($headerArray);
        }

        $handle = fopen($outputCSV, 'w');

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
     * @return Stream
     */
    public function PHP2CSV(array $dataInput = null, string $output = './dist/data.csv', string $delimiter = ',')
    {
        if ($dataInput === null) {
            throw new \InvalidArgumentException('No data given to process.');
        }

        $header = null;

        if (($handle = fopen($output, 'w')) !== false) {
            foreach ($dataInput as $row) {
                $headerArray = array_keys($row);

                // Optionally prefix all headers
                if ($this->headerPrefix !== null) {
                    foreach ($headerArray as $key => $value) {
                        $headerArray[$key] = $this->headerPrefix . $value;
                    }
                }

                // Optionally suffix all headers
                if ($this->headerSuffix !== null) {
                    foreach ($headerArray as $key => $value) {
                        $headerArray[$key] = $value . $this->headerSuffix;
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
    private function generateInfo(array $array)
    {
        $age = 'Keine Altersangabe';
        $pageCount = '';
        $year = '';

        foreach ($array as $entry) {
            // Remove garbled book dimensions
            if (Butler::contains($entry, ' cm') || Butler::contains($entry, ' mm')) {
                unset($array[array_search($entry, $array)]);
            }

            // Filtering age
            if (Butler::contains($entry, ' J.') || Butler::contains($entry, ' Mon.')) {
                $age = $this->convertAge($entry);
                unset($array[array_search($entry, $array)]);
            }

            // Filtering page count
            if (Butler::contains($entry, ' S.')) {
                $pageCount = $this->convertPageCount($entry);
                unset($array[array_search($entry, $array)]);
            }

            // Filtering year (almost always right at this point)
            if (Butler::length($entry) == 4) {
                $year = $entry;
                unset($array[array_search($entry, $array)]);
            }
        }

        $strings = $this->translations['information'];
        $array = Butler::replace($array,
            array_keys($strings),
            array_values($strings)
        );

        $info = ucfirst(implode(', ', $array));

        if (Butler::length($info) > 0) {
            $info = Butler::replace($info, '.', '') . '.';
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
        return Butler::substr($string, 0, -1);
    }


    /**
     * Builds 'Altersangabe' attribute as exported with pcbis.de
     *
     * @param string $string - Altersangabe string
     * @return string
     */
    private function convertAge($string)
    {
      	$string = Butler::replace($string, 'J.', 'Jahren');
      	$string = Butler::replace($string, 'Mon.', 'Monaten');
      	$string = Butler::replace($string, '-', ' bis ');
      	$string = Butler::replace($string, 'u.', '&');

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
        $string = Butler::replace($string, 'EUR', '€');
        $string = Butler::replace($string, '.', ',');

        return $string;
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
    public function downloadCover(string $isbn, string $fileName = null)
    {
        try {
            Butler::validateISBN($isbn);
        } catch (\InvalidArgumentException $e) {
            echo 'Error: ', $e->getMessage(), "\n";
            return false;
        }

        if ($fileName == null) {
            $fileName = $isbn;
        }

        $file = $this->imagePath . '/' . $fileName . '.jpg';

        if (file_exists($file)) {
            echo 'Book cover for ' . $isbn . ' already exists, skipping ..', "\n";
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
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
            $raw = curl_exec($ch);
            curl_close($ch);

            if (!$raw) {
                @unlink($file);
                return false;
            }

            fwrite($handle, $raw);
            fclose($handle);

            echo 'Downloading & saving "' . $isbn . '" as "' . $file . '" .. done!', "\n";
            return true;
        }
        return false;
    }


    /**
     * Sorts a given array holding book information by certain sort order
     *
     * @param array $array - Input that should be sorted
     * @return array
     * TODO: https://www.php.net/manual/en/function.usort.php#25360
     */
    private function sortArray(array $array)
    {
        $sortedArray = [];

        foreach ($this->sortOrder as $entry) {
            $sortedArray[$entry] = $array[$entry];
        }

        return $sortedArray;
    }


    /**
     * Enriches an array with KNV information
     *
     * @param array $dataInput - Input that should be processed
     * @param boolean $downloadCovers - Whether to download book covers on-the-fly
     * @param boolean $includeProviders - Whether to include third-party providers
     * @return array|InvalidArgumentException
     */
    public function processData(array $dataInput = null, bool $downloadCovers = true)
    {
        if ($dataInput === null) {
            throw new \InvalidArgumentException('No data given to process.');
        }

        $dataOutput = [];

        foreach ($dataInput as $array) {
            // Continue the foreach loop with the next book upon invalid ISBN,
            // which only ever applies to self-generated CSV files (but whatever)
            try {
                Butler::validateISBN($array['ISBN']);
            } catch (\InvalidArgumentException $e) {
                echo 'Error: ', $e->getMessage(), "\n";
                continue;
            }

            // Gathering & processing generic book information
            $infoString = $array['Informationen'];
            $infoArray = Butler::split($infoString, ';');

            if (count($infoArray) === 1) {
                $infoArray = Butler::split($infoString, '.');
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
            $slug = Butler::slug($title);

            $downloaded = false;

            if ($downloadCovers === true) {
                $downloaded = $this->downloadCover($array['ISBN'], $slug);
            }

            $cover = '';
            $coverDNB = '';
            $imageName = $slug . '.jpg';

            if ($downloaded && file_exists($imagePath = $this->imagePath . '/' . $imageName)) {
                // Although InDesign seems to support relative paths for images,
                // we don't want to go through specifics by providing their absolute path
                $cover = $this->mode == 'indesign' ? realpath($imagePath) : $imageName;
                $coverDNB = 'https://portal.dnb.de/opac/mvb/cover.htm?isbn=' . $array['ISBN'];
            }

            $array = Butler::update($array, [
                // Updating existing entries + adding blanks to prevent columns from shifting
                'Titel' => $title,
                'Untertitel' => '',
                'Mitwirkende' => '',
                'Preis' => $this->convertPrice($array['Preis']),
                'Erscheinungsjahr' => $year,
                'Altersempfehlung' => $age,
                'Inhaltsbeschreibung' => '',
                'Informationen' => $info,
                'Einband' => $this->convertBinding($array['Einband']),
                'Seitenzahl' => $pageCount,
                'Abmessungen' => '',
                '@Cover' => $cover,
                'Cover DNB' => $coverDNB,
                'Cover KNV' => '',
            ]);

            $data[] = $this->sortArray($array);
        }

        $login = $this->login;

        if ($login === null) {
            $login = Butler::getLogin('knv');
        }

        $KNV = new KNV(
            $login,
            $this->cachePath
        );

        try {
            $dataOutput = $KNV->processData($data);
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage(), "\n";
        }

        echo 'Operation was successful!', "\n";
        return Butler::sort($dataOutput, 'AutorIn', 'asc');
    }
}
