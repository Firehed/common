<?php

namespace Firehed\Common;

use InvalidArgumentException;

/**
 * Tool for performing a regex search against an array's keys and getting back
 * the found value and an array of matches. A common use-case is to search
 * a generated list of routes and get back the matched controller.
 *
 * To get back matched data, it is necessary to use named subpatterns in the
 * regex, since numeric matches will be stripped from the output data.
 *
 * See http://php.net/preg_match#example-4904
 *
 * Regexes will be delimited with # (rather than the more common /) since the
 * most common case is URI routing in order to minimize escape characters
 *
 * Example:
 * ```lang=php
 * <?php
 * $map = [
 *   'user/profile/(?P<id>\d+)' => 'UserProfileController',
 *   'user/me' => 'UserMeController',
 * ];
 * $search_url = 'user/profile/12345'; // Comes from input somewhere
 * list($class, $data) = (new ClassMapper($map))->search($search_url);
 * var_dump($class, $data);
 * // Output:
 * //   string(21) "UserProfileController"
 * //   array(1) {
 * //     'id' =>
 * //     string(5) "12345"
 * //   }
 * ```
 *
 * Filtering is also supported, and is treated as a simple index into the map.
 * The filter will be reset after each search. Internally filtering is
 * performed similarly to the recursive search so it is recommendeda to not use
 * user-supplied data
 */
class ClassMapper
{

    private $map;
    private $filters = [];

    /**
     * @param array|string Map or path to file returning it
     * @return this
     * @throws InvalidArgumentException
     */
    public function __construct($map)
    {
        if (is_array($map)) {
            $this->map = $map;
        } elseif (is_string($map) && file_exists($map)) {
            $this->map = $this->loadFile($map);
        } else {
            throw new InvalidArgumentException(
                "Map was not a valid array or file path"
            );
        }
    } // __construct

    public static function getDelimiter()
    {
        return '#';
    } // getDelimiter

    public static function getQuotedString($string)
    {
        return preg_quote($string, self::getDelimiter());
    } // getQuotedString

    /**
     * Apply a filter to the map
     * @param string Filter to apply
     * @return $this
     */
    public function filter($filter)/*: this*/
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * @param string Search term
     * @return pair<string, array> FQ class name and matched data
     * @return pair<null, null> if no match is found
     */
    public function search($path)
    {
        $target = $this->map;
        // Since this function works by-reference, filters are automatically
        // reset after a search. This is the desired behavior.
        while ($filter = array_shift($this->filters)) {
            $target = array_key_exists($filter, $target)
                ? $target[$filter]
                : [];
        }
        return $this->searchRecursive($path, '', $target);
    } // search

    private function loadFile($file)
    {
        $info = pathinfo($file);
        switch (strtolower($info['extension'])) {
            case 'php':
                return require $file;
            case 'json':
                $raw = file_get_contents($file);
                $data = json_decode($raw, true);
                if (json_last_error()) {
                    throw new InvalidArgumentException(sprintf(
                        "Could not parse JSON file %s",
                        $info['extension']
                    ));
                }
                return $data;

            default:
                throw new InvalidArgumentException(sprintf(
                    "Not sure how to parse file of type %s",
                    $info['extension']
                ));
        }
    } // loadFile

    private function searchRecursive($path, $base_rule, array $classes)
    {
        foreach ($classes as $rule => $class) {
            $end = is_array($class) ? '' : '$';
            $pattern = "#^" . $base_rule . $rule . $end . "#";
            // This will allow named matches with (?P<foo>PATTERN) syntax get
            // passed down to an associative array of foo=PATTERN_MATCH
            if (preg_match($pattern, $path, $match)) {
                foreach ($match as $k => $v) {
                    if (is_numeric($k)) {
                        unset($match[$k]);
                    }
                }
                if (is_array($class)) {
                    return $this->searchRecursive($path, $base_rule . $rule, $class);
                }
                return [$class, $match];
            }
        }
        return [null, null];
    } // search
}
