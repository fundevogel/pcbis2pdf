<?php

namespace PCBIS2PDF\Helpers;

/**
 * Class Butler
 *
 * This class contains useful helper functions, pretty much like a butler
 *
 * @package PCBIS2PDF
 */

class Butler
{
    /**
     * An UTF-8 safe version of substr()
     *
     * @param  string  $str
     * @param  int     $start
     * @param  int     $length
     * @return string
     */
    public static function substr($str, $start, $length = null)
    {
        $length = $length === null ? static::length($str) : $length;
        return MB ? mb_substr($str, $start, $length, 'UTF-8') : substr($str, $start, $length);
    }


    /**
     * An UTF-8 safe version of strtolower()
     *
     * @param  string  $str
     * @return string
     */
    public static function lower($str)
    {
        return MB ? mb_strtolower($str, 'UTF-8') : strtolower($str);
    }


    /**
     * An UTF-8 safe version of strlen()
     *
     * @param  string  $str
     * @return string
     */
    public static function length($str)
    {
        return MB ? mb_strlen($str, 'UTF-8') : strlen($str);
    }

    /**
     * Checks if a str contains another string
     *
     * @param  string  $str
     * @param  string  $needle
     * @param  boolean $i ignore upper/lowercase
     * @return string
     */
    public static function contains($str, $needle, $i = true)
    {
        if($i) {
            $str    = static::lower($str);
            $needle = static::lower($needle);
        }

        return strstr($str, $needle) ? true : false;
    }


    /**
     * Replaces all or some occurrences of the search string with the replacement string
     * Extension of the str_replace() function in PHP with an additional $limit parameter
     *
     * @param  string|array $string  String being replaced on (haystack);
     *                               can be an array of multiple subject strings
     * @param  string|array $search  Value being searched for (needle)
     * @param  string|array $replace Value to replace matches with
     * @param  int|array    $limit   Maximum possible replacements for each search value;
     *                               multiple limits for each search value are supported;
     *                               defaults to no limit
     * @return string|array          String with replaced values;
     *                               if $string is an array, array of strings
     */
    public static function replace($string, $search, $replace, $limit = -1)
    {
        // convert Kirby collections to arrays
        if(is_a($string,  'Collection')) $string  = $string->toArray();
        if(is_a($search,  'Collection')) $search  = $search->toArray();
        if(is_a($replace, 'Collection')) $replace = $replace->toArray();

        // without a limit we might as well use the built-in function
        if($limit === -1) return str_replace($search, $replace, $string);

        // if the limit is zero, the result will be no replacements at all
        if($limit === 0) return $string;

        // multiple subjects are run separately through this method
        if(is_array($string)) {
            $result = [];
            foreach($string as $s) {
                $result[] = static::replace($s, $search, $replace, $limit);
            }

            return $result;
        }

        // build an array of replacements
        // we don't use an associative array because otherwise you couldn't
        // replace the same string with different replacements
        $replacements = static::makeReplacements($search, $replace, $limit);

        // run the string and the replacement array through the replacer
        return static::replaceReplacements($string, $replacements);
    }

    /**
     * Generates a replacement array out of dynamic input data
     * Used for Butler::replace()
     *
     * @param  string|array $search  Value being searched for (needle)
     * @param  string|array $replace Value to replace matches with
     * @param  int|array    $limit   Maximum possible replacements for each search value;
     *                               multiple limits for each search value are supported;
     *                               defaults to no limit
     * @return array                 List of replacement arrays, each with a
     *                               'search', 'replace' and 'limit' attribute
     */
    public static function makeReplacements($search, $replace, $limit)
    {
        $replacements = [];

        if(is_array($search) && is_array($replace)) {
            foreach($search as $i => $s) {
                // replace with an empty string if no replacement string was defined for this index;
                // behavior is identical to the official PHP str_replace()
                $r = (isset($replace[$i]))? $replace[$i] : '';

                if(is_array($limit)) {
                    // don't apply a limit if no limit was defined for this index
                    $l = (isset($limit[$i]))? $limit[$i] : -1;
                } else {
                    $l = $limit;
                }

                $replacements[] = ['search' => $s, 'replace' => $r, 'limit' => $l];
            }
        } else if(is_array($search) && is_string($replace)) {
            foreach($search as $i => $s) {
                if(is_array($limit)) {
                    // don't apply a limit if no limit was defined for this index
                    $l = (isset($limit[$i]))? $limit[$i] : -1;
                } else {
                    $l = $limit;
                }

                $replacements[] = ['search' => $s, 'replace' => $replace, 'limit' => $l];
            }
        } else if(is_string($search) && is_string($replace) && is_int($limit)) {
            $replacements[] = compact('search', 'replace', 'limit');
        } else {
            throw new Error('Invalid combination of $search, $replace and $limit params.');
        }

        return $replacements;
    }

    /**
     * Takes a replacement array and processes the replacements
     * Used for Butler::replace()
     *
     * @param  string $string       String being replaced on (haystack)
     * @param  array  $replacements Replacement array from Butler::makeReplacements()
     * @return string               String with replaced values
     */
    public static function replaceReplacements($string, $replacements)
    {
        // replace in the order of the replacements
        // behavior is identical to the official PHP str_replace()
        foreach($replacements as $r) {
            if(!is_int($r['limit'])) {
                throw new Error('Invalid limit "' . $r['limit'] . '".');
            } else if($r['limit'] === -1) {
                // no limit, we don't need our special replacement routine
                $string = str_replace($r['search'], $r['replace'], $string);
            } else if($r['limit'] > 0) {
                // limit given, only replace for $r['limit'] times per replacement
                $pos = -1;
                for($i = 0; $i < $r['limit']; $i++) {
                    $pos = strpos($string, $r['search'], $pos + 1);
                    if(is_int($pos)) {
                        $string = substr_replace($string, $r['replace'], $pos, strlen($r['search']));

                        // adapt $pos to the now changed offset
                        $pos = $pos + strlen($r['replace']) - strlen($r['search']);
                    } else {
                        // no more match in the string
                        break;
                    }
                }
            }
        }

        return $string;
    }


    /**
     * Better alternative for explode()
     * It takes care of removing empty values
     * and it has a built-in way to skip values
     * which are too short.
     *
     * @param  string  $string The string to split
     * @param  string  $separator The string to split by
     * @param  int     $length The min length of values.
     * @return array   An array of found values
     */
    public static function split($string, $separator = ',', $length = 1)
    {

        if(is_array($string)) return $string;

        $string = trim($string, $separator);
        $parts  = explode($separator, $string);
        $out    = array();

        foreach($parts AS $p) {
            $p = trim($p);
            if(static::length($p) > 0 && static::length($p) >= $length) $out[] = $p;
        }

        return $out;
    }


    /**
     * Convert a string to 7-bit ASCII.
     *
     * @param  string  $string
     * @return string
     */
    public static function ascii($string) {
        $foreign = [
            '/Ä/' => 'Ae',
            '/æ|ǽ|ä/' => 'ae',
            '/À|Á|Â|Ã|Å|Ǻ|Ā|Ă|Ą|Ǎ|А/' => 'A',
            '/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª|а/' => 'a',
            '/Б/' => 'B',
            '/б/' => 'b',
            '/Ç|Ć|Ĉ|Ċ|Č|Ц/' => 'C',
            '/ç|ć|ĉ|ċ|č|ц/' => 'c',
            '/Ð|Ď|Đ/' => 'Dj',
            '/ð|ď|đ/' => 'dj',
            '/Д/' => 'D',
            '/д/' => 'd',
            '/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě|Е|Ё|Э/' => 'E',
            '/è|é|ê|ë|ē|ĕ|ė|ę|ě|е|ё|э/' => 'e',
            '/Ф/' => 'F',
            '/ƒ|ф/' => 'f',
            '/Ĝ|Ğ|Ġ|Ģ|Г/' => 'G',
            '/ĝ|ğ|ġ|ģ|г/' => 'g',
            '/Ĥ|Ħ|Х/' => 'H',
            '/ĥ|ħ|х/' => 'h',
            '/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ|И/' => 'I',
            '/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı|и/' => 'i',
            '/Ĵ|Й/' => 'J',
            '/ĵ|й/' => 'j',
            '/Ķ|К/' => 'K',
            '/ķ|к/' => 'k',
            '/Ĺ|Ļ|Ľ|Ŀ|Ł|Л/' => 'L',
            '/ĺ|ļ|ľ|ŀ|ł|л/' => 'l',
            '/М/' => 'M',
            '/м/' => 'm',
            '/Ñ|Ń|Ņ|Ň|Н/' => 'N',
            '/ñ|ń|ņ|ň|ŉ|н/' => 'n',
            '/Ö/' => 'Oe',
            '/œ|ö/' => 'oe',
            '/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ|О/' => 'O',
            '/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º|о/' => 'o',
            '/П/' => 'P',
            '/п/' => 'p',
            '/Ŕ|Ŗ|Ř|Р/' => 'R',
            '/ŕ|ŗ|ř|р/' => 'r',
            '/Ś|Ŝ|Ş|Ș|Š|С/' => 'S',
            '/ś|ŝ|ş|ș|š|ſ|с/' => 's',
            '/Ţ|Ț|Ť|Ŧ|Т/' => 'T',
            '/ţ|ț|ť|ŧ|т/' => 't',
            '/Ü/' => 'Ue',
            '/ü/' => 'ue',
            '/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ|У/' => 'U',
            '/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ|у/' => 'u',
            '/В/' => 'V',
            '/в/' => 'v',
            '/Ý|Ÿ|Ŷ|Ы/' => 'Y',
            '/ý|ÿ|ŷ|ы/' => 'y',
            '/Ŵ/' => 'W',
            '/ŵ/' => 'w',
            '/Ź|Ż|Ž|З/' => 'Z',
            '/ź|ż|ž|з/' => 'z',
            '/Æ|Ǽ/' => 'AE',
            '/ß/'=> 'ss',
            '/Ĳ/' => 'IJ',
            '/ĳ/' => 'ij',
            '/Œ/' => 'OE',
            '/Ч/' => 'Ch',
            '/ч/' => 'ch',
            '/Ю/' => 'Ju',
            '/ю/' => 'ju',
            '/Я/' => 'Ja',
            '/я/' => 'ja',
            '/Ш/' => 'Sh',
            '/ш/' => 'sh',
            '/Щ/' => 'Shch',
            '/щ/' => 'shch',
            '/Ж/' => 'Zh',
            '/ж/' => 'zh',
        ];
        $string  = preg_replace(array_keys($foreign), array_values($foreign), $string);
        return preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $string);
    }


    /**
     * Convert a string to a safe version to be used in a URL
     *
     * @param  string  $string The unsafe string
     * @param  string  $separator To be used instead of space and other non-word characters.
     * @return string  The safe string
     */
    public static function slug($string, $separator = null, $allowed = null)
    {
        $separator = $separator !== null ? $separator : '-';
        $allowed   = $allowed   !== null ? $allowed   : 'a-z0-9';

        $string = trim($string);
        $string = static::lower($string);
        $string = static::ascii($string);

        // replace spaces with simple dashes
        $string = preg_replace('![^' . $allowed . ']!i', $separator, $string);

        if(strlen($separator) > 0) {
            // remove double separators
            $string = preg_replace('![' . preg_quote($separator) . ']{2,}!', $separator, $string);
        }

        // trim trailing and leading dashes
        $string = trim($string, $separator);

        // replace slashes with dashes
        $string = str_replace('/', $separator, $string);

        return $string;
    }


    /**
     * Update an array with a second array
     * The second array can contain callbacks as values,
     * which will get the original values as argument
     *
     * @param array $array
     * @param array $update
     */
    public static function update($array, $update)
    {
        foreach($update as $key => $value) {
            if(is_a($value, 'Closure')) {
                $array[$key] = call($value, static::get($array, $key));
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }


    /**
     * Removes all html tags and encoded chars from a string
     *
     * <code>
     *
     * echo str::unhtml('some <em>crazy</em> stuff');
     * // output: some uber crazy stuff
     *
     * </code>
     *
     * @param  string  $string
     * @return string  The html string
     */
    public static function unhtml($string)
    {
        $string = strip_tags($string);
        return html_entity_decode($string, ENT_COMPAT, 'utf-8');
    }


    /**
     * Checks for missing elements in an array
     *
     * This is very handy to check for missing
     * user values in a request for example.
     *
     * <code>
     *
     * $array = [
     *   'cat' => 'miao',
     *   'dog' => 'wuff',
     *   'bird' => 'tweet',
     *   'hippo' => null
     * ];
     *
     * $required = ['cat', 'elephant', 'hippo'];
     *
     * $missing = a::missing($array, $required);
     * // missing: ['elephant', 'hippo'];
     *
     * </code>
     *
     * @param   array  $array The source array
     * @param   array  $required An array of required keys
     * @return  array  An array of missing fields. If this is empty, nothing is missing.
     */
    public static function missing($array, $required = [])
    {
        $missing = [];

        foreach($required as $r) {
            if(!isset($array[$r])) $missing[] = $r;
        }

        return $missing;
    }


    /**
     * Gets an element of an array by key
     *
     * <code>
     *
     * $array = array(
     *   'cat'  => 'miao',
     *   'dog'  => 'wuff',
     *   'bird' => 'tweet'
     * );
     *
     * echo a::get($array, 'cat');
     * // output: 'miao'
     *
     * echo a::get($array, 'elephant', 'shut up');
     * // output: 'shut up'
     *
     * $catAndDog = a::get(array('cat', 'dog'));
     * // result: array(
     * //   'cat' => 'miao',
     * //   'dog' => 'wuff'
     * // );
     *
     * </code>
     *
     * @param   array  $array The source array
     * @param   mixed  $key The key to look for
     * @param   mixed  $default Optional default value, which should be returned if no element has been found
     * @return  mixed
     */
    public static function get($array, $key, $default = null)
    {
        // get an array of keys
        if(is_array($key)) {
            $result = array();
            foreach($key as $k) $result[$k] = static::get($array, $k);
            return $result;

        // get a single
        } else if(isset($array[$key])) {
            return $array[$key];

        // return the entire array if the key is null
        } else if(is_null($key)) {
            return $array;

        // get the default value if nothing else worked out
        } else {
            return $default;
        }
    }


    /**
     * Sorts a multi-dimensional array by a certain column
     *
     * <code>
     *
     * $array[0] = array(
     *   'id' => 1,
     *   'username' => 'bastian',
     * );
     *
     * $array[1] = array(
     *   'id' => 2,
     *   'username' => 'peter',
     * );
     *
     * $array[3] = array(
     *   'id' => 3,
     *   'username' => 'john',
     * );
     *
     * $sorted = a::sort($array, 'username ASC');
     * // Array
     * // (
     * //      [0] => Array
     * //          (
     * //              [id] => 1
     * //              [username] => bastian
     * //          )
     * //      [1] => Array
     * //          (
     * //              [id] => 3
     * //              [username] => john
     * //          )
     * //      [2] => Array
     * //          (
     * //              [id] => 2
     * //              [username] => peter
     * //          )
     * // )
     *
     * </code>
     *
     * @param   array   $array The source array
     * @param   string  $field The name of the column
     * @param   string  $direction desc (descending) or asc (ascending)
     * @param   const   $method A PHP sort method flag or 'natural' for natural sorting, which is not supported in PHP by sort flags
     * @return  array   The sorted array
     */
    public static function sort($array, $field, $direction = 'desc', $method = SORT_REGULAR)
    {
        $direction = strtolower($direction) == 'desc' ? SORT_DESC : SORT_ASC;
        $helper    = array();
        $result    = array();

        // build the helper array
        foreach($array as $key => $row) $helper[$key] = $row[$field];

        // natural sorting
        if($method === SORT_NATURAL) {
            natsort($helper);
            if($direction === SORT_DESC) $helper = array_reverse($helper);
        } else if($direction === SORT_DESC) {
            arsort($helper, $method);
        } else {
            asort($helper, $method);
        }

        // rebuild the original array
        foreach($helper as $key => $val) $result[$key] = $array[$key];

        return $result;
    }


    /**
     * Returns the first element of an array
     *
     * I always have to lookup the names of that function
     * so I decided to make this shortcut which is
     * easier to remember.
     *
     * <code>
     *
     * $array = array(
     *   'cat',
     *   'dog',
     *   'bird',
     * );
     *
     * $first = a::first($array);
     * // first: 'cat'
     *
     * </code>
     *
     * @param   array  $array The source array
     * @return  mixed  The first element
     */
    public static function first($array)
    {
        return array_shift($array);
    }


    /**
     * Returns the last element of an array
     *
     * I always have to lookup the names of that function
     * so I decided to make this shortcut which is
     * easier to remember.
     *
     * <code>
     *
     * $array = array(
     *   'cat',
     *   'dog',
     *   'bird',
     * );
     *
     * $last = a::last($array);
     * // first: 'bird'
     *
     * </code>
     *
     * @param   array  $array The source array
     * @return  mixed  The last element
     */
    public static function last($array) {
        return array_pop($array);
    }


    /**
     * Checks whether given ISBN consists of 10 or 13 digits
     * For more advanced ways to detect valid ISBNs,
     * see https://github.com/biblys/isbn
     *
     * @param string $isbn - International Standard Book Number
     * @return boolean|InvalidArgumentException
     */
    public static function validateISBN(string $isbn)
    {
        $cleanISBN = static::replace($isbn, '-', '');
        $length = static::length($cleanISBN);

        if ($length === 10 || $length === 13) {
            return true;
        }

        throw new \InvalidArgumentException('ISBN must consist of 10 or 13 digits, ' . $length . ' given (' . $isbn . ').');
    }


    /**
     * Checks if `*.login.json` file for given provider exists an returns
     * array with login information if that's the case
     *
     * @param string $provider - Provider name, eg 'KNV', 'Google', etc
     * @return array|boolean
     */
    public static function getLogin(string $provider)
    {
        if (file_exists($file = realpath('./' . static::lower($provider) . '.login.json'))) {
            $json = file_get_contents($file);
            $array = json_decode($json, true);

            return $array;
        }

        return false;
    }
}
