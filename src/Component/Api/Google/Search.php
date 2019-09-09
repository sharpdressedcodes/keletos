<?php

namespace Keletos\Component\Api\Google;

use \Keletos\Component\Stream\HTTPS;

class Search extends \Keletos\Component\Component {

    const DEFAULT_SERVER = 'www.googleapis.com';
    const DEFAULT_URL = '/customsearch/v1?key=%key%&cx=%cx%&q=%q%';

    protected $_https;
    protected $_apiKey;
    protected $_cseId; // The identifier of the custom search engine.

    public function __construct(string $apiKey, string $cseId, HTTPS $https = null) {

        $this->_https = $https ?? new HTTPS(self::DEFAULT_SERVER);
        $this->_apiKey = $apiKey;
        $this->_cseId = $cseId;

        parent::__construct();
    }

    public function getResults(string $query, int $total = 10) {

        $results = [];
        $startIndex = 1;

        while (1) {
            $result = $this->api($query, $startIndex);
            $results = array_merge($results, $result['items']);

            if (count($results) >= $total || !array_key_exists('nextPage', $result['queries'])) {
                break;
            }

            $startIndex = $result['queries']['nextPage'][0]['startIndex'];
        }

        return $results;

    }

    public static function getIndexOfAppearances(string $search, array $results, int $base = 1) : array {

        $index = $base;
        $result = [];

        if (is_array($results)) {
            foreach ($results as $item) {
                $str = json_encode($item);
                if (strpos($str, $search) !== false) {
                    $result[] = $index;
                }
                $index++;
            }
        }

        return $result;
    }

    protected function api(string $query, int $startIndex = 1) {

        $result = null;
        $url = str_replace('%key%', urlencode($this->_apiKey), self::DEFAULT_URL);
        $url = str_replace('%cx%', urlencode($this->_cseId), $url);
        $url = str_replace('%q%', urlencode($query), $url);

        if ($startIndex > 1) {
            $url .= "&start={$startIndex}";
        }

        if ($this->_https->open(['url' => $url])) {
            $result = json_decode($this->_https->get(), true);
            $this->_https->close();
        }

        return $result;
    }
}
