<?php

// phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
namespace Tests\Acceptance\Framework\View;

class GoogleSearchApiCest {

    public function viewLoads(\AcceptanceTester $I) {

        $I->wantTo('Ensure the view loads correctly');
        $I->amOnPage('/');

        $I->seeElement('#keywords');
        $I->seeElement('#url');
        $I->fillField('keywords', 'sharpdressedcodes');
        $I->fillField('url', 'sharpdressedcodes.com');
        // $I->click('.form .form-submit');

    }

}
