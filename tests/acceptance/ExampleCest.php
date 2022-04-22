<?php

class ExampleCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
        $I->loginAsAdmin();
        $I->amOnPage( '/' );
        $I->seeElement( '#wpadminbar' );
    }
}
