<?php

namespace Tests\Unit\Framework\Component\Api\Google;

require_once __DIR__ . '/../../../../../../src/framework/autoload.php';

use \Codeception\Util\Stub;
use \Keletos\Component\Api\Google\Search;

class SearchTest extends \Codeception\TestCase\Test {

    public function testSearchApi() {

        // Create fake responses
        $fakeResponses = [
            [
                'kind' => 'customsearch#search',
                'url' => [
                    'type' => 'application/json',
                    // phpcs:disable Generic.Files.LineLength.TooLong
                    'template' => 'https://www.googleapis.com/customsearch/v1?q={searchTerms}&num={count?}&start={startIndex?}&lr={language?}&safe={safe?}&cx={cx?}&sort={sort?}&filter={filter?}&gl={gl?}&cr={cr?}&googlehost={googleHost?}&c2coff={disableCnTwTranslation?}&hq={hq?}&hl={hl?}&siteSearch={siteSearch?}&siteSearchFilter={siteSearchFilter?}&exactTerms={exactTerms?}&excludeTerms={excludeTerms?}&linkSite={linkSite?}&orTerms={orTerms?}&relatedSite={relatedSite?}&dateRestrict={dateRestrict?}&lowRange={lowRange?}&highRange={highRange?}&searchType={searchType}&fileType={fileType?}&rights={rights?}&imgSize={imgSize?}&imgType={imgType?}&imgColorType={imgColorType?}&imgDominantColor={imgDominantColor?}&alt=json',
                ],
                'queries' => [
                    'nextPage' => [
                        ['startIndex' => 11],
                    ],
                ],
                'items' => [
                    ['a' => 'aatest.comaa'],
                    ['b' => 'bbfdfssdbb'],
                    ['c' => 'cctest.comcc'],
                ],
            ],
            [
                'kind' => 'customsearch#search',
                'url' => [
                    'type' => 'application/json',
                    // phpcs:disable Generic.Files.LineLength.TooLong
                    'template' => 'https://www.googleapis.com/customsearch/v1?q={searchTerms}&num={count?}&start={startIndex?}&lr={language?}&safe={safe?}&cx={cx?}&sort={sort?}&filter={filter?}&gl={gl?}&cr={cr?}&googlehost={googleHost?}&c2coff={disableCnTwTranslation?}&hq={hq?}&hl={hl?}&siteSearch={siteSearch?}&siteSearchFilter={siteSearchFilter?}&exactTerms={exactTerms?}&excludeTerms={excludeTerms?}&linkSite={linkSite?}&orTerms={orTerms?}&relatedSite={relatedSite?}&dateRestrict={dateRestrict?}&lowRange={lowRange?}&highRange={highRange?}&searchType={searchType}&fileType={fileType?}&rights={rights?}&imgSize={imgSize?}&imgType={imgType?}&imgColorType={imgColorType?}&imgDominantColor={imgDominantColor?}&alt=json',
                ],
                'queries' => [
                    // 'nextPage' => [
                    // ['startIndex' => 11]
                    // ],
                ],
                'items' => [
                    ['d' => 'bbfdfssdbb'],
                    ['e' => 'bbfdfssdbb'],
                    ['f' => 'cctest.comcc'],
                    ['g' => 'cctest.comcc'],
                ],
            ],
        ];

        // Stub the API call
        $api = Stub::make(Search::class, [
            'api' => Stub::consecutive($fakeResponses[0], $fakeResponses[1]),
        ], $this);

        // Now run a method that triggers the API call
        $result = $api->getResults('test', 100);

        $this->assertIsArray($result);
        $this->assertCount(7, $result);
        $this->assertEquals(array_merge($fakeResponses[0]['items'], $fakeResponses[1]['items']), $result);

        // Now make sure the appearance method works correctly
        $appearances = Search::getIndexOfAppearances('test.com', $result);

        $this->assertIsArray($appearances);
        $this->assertCount(4, $appearances);
        $this->assertEquals([1, 3, 6, 7], $appearances);

    }

}
