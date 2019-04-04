#!/usr/bin/php

<?php

require_once('vendor/autoload.php');

// use Scriptotek\GoogleBooks\GoogleBooks;
use Doctrine\Common\Cache\FilesystemCache;

$defaults = [
		'translations' => file_get_contents(basename('translations.json')),
		'inputCSV' => 'src/csv/*.csv',
		'outputCSV' => 'src/Titelexport.csv',
		'input'  => 'src/Titelexport.csv',
		'output' => 'dist/data.csv',
		'images' => 'dist/images',
		'headers' => [
				// 'category', /* 2 */
				'AutorIn',
				'Titel',
				'Verlag',
				'ISBN',
				'Einband',
				'Preis',
				'a',
				'b',
				'c',
				'Informationen',
				'Zusatz',
				'Kommentar'
		],
		'sortOrder' => [
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
				'Cover',
				'Cover DNB',
				'Cover KNV',
		],
];

$inputFile  = isset($argv[1]) ? $argv[1] : $defaults['input'];
$outputFile = isset($argv[2]) ? $argv[2] : $defaults['output'];


function path(...$dirs)
{
    return join(DIRECTORY_SEPARATOR, $dirs);
}

function mergeCSV(array $input = [], string $output = '')
{
    global $defaults;
    $count = 0;

    $input = $defaults['inputCSV'];
    $output = $defaults['outputCSV'];

    foreach (glob($input) as $file) {
        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $rowCount = count($row);
                $array[$count][] = $file;

								/* 1 */
                unset($array[$count][0]);

								/* 2 */
								// $category = $array[$count][0];
								// $category = str::split($category, '/');
								// $category = str::replace(a::last($category), ['.csv', '2019', '_ab', '_', 'Kleinsten'], ['', '', 'Bücher ab ', '', 'Für die Kleinsten']);
								// $array[$count][0] = $category;

                for ($i = 0; $i < $rowCount; $i++) {
                    $array[$count][] = $row[$i];
                }
                $count++;
            }
            fclose($handle);
        }
    }

    $handle = fopen($output, 'w');

    foreach ($array as $fields) {
        fputcsv($handle, $fields, ';');
    }

    fclose($handle);
}


function CSV2PHP($input, $delimiter = ';')
{
    if (!file_exists($input) || !is_readable($input)) {
        return false;
    }

    global $defaults;
    $data = [];

    if (($handle = fopen($input, 'r')) !== false) {
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
            $row = array_map('utf8_encode', $row);
            $data[] = array_combine($defaults['headers'], $row);
        }
        fclose($handle);
    }
    return $data;
}

function PHP2CSV($data = [], $output, $delimiter = ';')
{
    // $folder = dirname($output);
    //
    // if (!is_writable($folder))
    //     return false;
    //
    // if (!file_exists($folder))
    // 		echo 'doesnt!';
    // 		mkdir($folder);

    $header = null;

    if (($handle = fopen($output, 'w')) !== false) {
        foreach ($data as $row) {
            if (!$header) {
                fputcsv($handle, array_keys($row), $delimiter);
                $header = true;
            }
            fputcsv($handle, $row, $delimiter);
        }
        fclose($handle);
    }
    return true;
}

function getBookKNV($isbn)
{
    $json = file_get_contents(basename('knv.login.json'));
    $login = json_decode($json, true);

    $client = new SoapClient('http://ws.pcbis.de/knv-2.0/services/KNVWebService?wsdl', [
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

function getBookGoogle($isbn)
{
    $json = file_get_contents(basename('google.login.json'));
    $login = json_decode($json);

    $client = new GoogleBooks($login->key);

		if ($query = $client->volumes->byIsbn($isbn)) {
				$array = (array) $query->volumeInfo;
				return $array;
		}
		return false;
}

function sortArray($array)
{
		global $defaults;
		$sortedArray = [];

		foreach ($defaults['sortOrder'] as $entry) {
				$sortedArray[$entry] = $array[$entry];
		}
		return $sortedArray;
}

function downloadCover($isbn, $fileName)
{
		global $defaults;
		$file = path($defaults['images'], $fileName . '.jpg');

		if (file_exists($file)) {
			echo 'Book cover already exists, skipping ..' . "\n";
			return true;
		}

		$url = 'https://portal.dnb.de/opac/mvb/cover.htm?isbn=' . $isbn;

		if ($handle = fopen($file, 'w')) {
				$ch = curl_init ($url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
				$result = parse_url($url);
				curl_setopt($ch, CURLOPT_REFERER, $result['scheme'] . '://' . $result['host']);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0');
				$raw = curl_exec($ch);
				curl_close ($ch);

				if (!$raw) {
						@unlink($file);
						return false;
				}

				fwrite($handle, $raw);
				fclose($handle);

				return true;
		}
		return false;
}

function accessCache($identifier, $isbn)
{
    $driver = new FilesystemCache('./.cache');
    $id = implode('-', [$identifier, md5($isbn)]);

    if ($driver->contains($id)) {
        echo 'Loading "' . $isbn . '" from "' . $identifier . '" cache .. done!' . "\n";
    } else {
        $command = 'getBook' . $identifier;
        $result = $command($isbn);
        $driver->save($id, $result);
        echo 'Downloading & saving "' . $isbn . '" to "' . $identifier . '" cache .. done!' . "\n";
    }

    return $driver->fetch($id);
}


/**
 * Returns subtitle from KNV
 *
 * .. if it exists
 *
 * @param Array $array
 * @return return string
 */
function getAuthorKNV($array, $arrayCSV = ['Titel' => ''])
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
 * @return return string
 */
function getSubtitleKNV($array)
{
    if (a::missing($array, ['Utitel'])) {
        return '';
    }

		if ($array['Utitel'] == null) {
			echo 'Hello!';
		    return '';
    }

    return $array['Utitel'];
}


function getYearKNV($array)
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
 * @return return string
 */
function getTextKNV($array)
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
 * @return return string
 */
function getParticipantsKNV($array)
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
 * @return return string
 */
function convertMM($string)
{
		$string = $string / 10;
		$string = str::replace($string, '.', ',');

		return $string . 'cm';
}


function getDimensionsKNV($array)
{
		if (a::missing($array, ['Breite'])) {
				return '';
		}

		if (a::missing($array, ['Hoehe'])) {
				return '';
		}

		$width = convertMM($array['Breite']);
		$height = convertMM($array['Hoehe']);

		return $width . ' x ' . $height;
}


/**
 * Returns cover URL from KNV
 *
 * .. always!
 *
 * @param Array $array
 * @return return string
 */
function getCoverKNV($array)
{
		return $array['MULTIMEDIA']['MMUrl'];
}


function processInfo($array)
{
		global $defaults;

		$age = 'Keine Altersangabe';
		$pageCount = '';
		$year = '';

		foreach ($array as $entry) {
				// Remove garbled book dimensions
				if (str::contains($entry, ' cm') || str::contains($entry, ' mm')) {
						// unset($array[$index]);
						unset($array[array_search($entry, $array)]);
				}

				// Filtering age
				if (str::contains($entry, ' J.') || str::contains($entry, ' Mon.')) {
						$age = convertAge($entry);
						// unset($array[$index]);
						unset($array[array_search($entry, $array)]);
				}

				// Filtering page count
				if (str::contains($entry, ' S.')) {
						$pageCount = convertPageCount($entry);
						// unset($array[$index]);
						unset($array[array_search($entry, $array)]);
				}

				// Filtering year (almost always right at this point)
				if (str::length($entry) == 4) {
						$year = $entry;
						// unset($array[$index]);
						unset($array[array_search($entry, $array)]);
				}
		}

		$strings = json_decode($defaults['translations'], true)['information'];
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

function convertTitle($string)
{
		// Input: Book title.
		// Output: Book title
		return str::substr($string, 0, -1);
}


function convertAge($string)
{
	$string = str::replace($string, 'J.', 'Jahren');
	$string = str::replace($string, 'Mon.', 'Monaten');
	$string = str::replace($string, '-', ' bis ');
	$string = str::replace($string, 'u.', '&');

	return $string;
}


function convertPageCount($string)
{
		return (int) $string;
}


function convertBinding($string)
{
		global $defaults;

		$translations = json_decode($defaults['translations'], true)['binding'];
		$string = $translations[$string];

		return $string;
}


function convertPrice($string)
{
		// Input: XX.YY EUR
		// Output: XX,YY €
		$string = str::replace($string, 'EUR', '€');
		$string = str::replace($string, '.', ',');

		return $string;
}

mergeCSV();

$dataInput = CSV2PHP($inputFile);

$dataOutput = [];

foreach ($dataInput as $array) {
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
		) = processInfo($infoArray);


		// Title, cover & image download
		$title = convertTitle($array['Titel']);
		$slug = str::slug($title);

		$hasCover = downloadCover($array['ISBN'], $slug);
		$cover = $hasCover ? path($defaults['images'], $slug . '.jpg') : '';
		$coverDNB = $hasCover ? 'https://portal.dnb.de/opac/mvb/cover.htm?isbn=' . $array['ISBN'] : '';

		$array = a::update($array, [
				'Einband' => convertBinding($array['Einband']),
				'Preis' => convertPrice($array['Preis']),
				'Titel' => $title,
				'Untertitel' => '',
				'Altersempfehlung' => $age,
				'Erscheinungsjahr' => $year,
				'Seitenzahl' => $pageCount,
				'Abmessungen' => '',
				'Mitwirkende' => '',
				'Informationen' => $info,
				'Inhaltsbeschreibung' => '',
				'Cover' => $cover,
				'Cover DNB' => $coverDNB,
				'Cover KNV' => '',
		]);

		// try {
		// 		$bookGoogle = accessCache('Google', $array['ISBN']);
		// 		$arrayGoogle = [
		// 				'autoren' => a::missing($bookGoogle, ['authors']) ? '' : implode(' & ', $bookGoogle['authors']),
		// 				'datum' => a::missing($bookGoogle, ['publishedDate']) ? '' : date('d.m.Y', strtotime($bookGoogle['publishedDate'])),
		// 				'Seitenzahl' => a::missing($bookGoogle, ['pageCount']) ? '' : $bookGoogle['pageCount'],
		// 		];
		// 		$array = a::update($array, $arrayGoogle);
		//
		// } catch (Exception $e) {
		// 		echo 'Error: ' . $e->getMessage();
		// }

		try {
		    $bookKNV = accessCache('KNV', $array['ISBN']);
		    $arrayKNV = [
						'Erscheinungsjahr' => getYearKNV($bookKNV),
		        'AutorIn' => getAuthorKNV($bookKNV, $array),
		        'Untertitel' => getSubtitleKNV($bookKNV),
						'Abmessungen' => getDimensionsKNV($bookKNV),
		        'Mitwirkende' => getParticipantsKNV($bookKNV),
		        'Inhaltsbeschreibung' => getTextKNV($bookKNV),
		        'Cover KNV' => getCoverKNV($bookKNV),
		    ];
		} catch (Exception $e) {
		    echo 'Error: ' . $e->getMessage();
		}

		$array = a::update($array, array_filter($arrayKNV, 'strlen'));

		// phew ..
		$dataOutput[] = sortArray($array);
}

PHP2CSV($dataOutput, $outputFile);
