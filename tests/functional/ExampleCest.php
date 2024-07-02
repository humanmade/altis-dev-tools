<?php

class ExampleCest
{
	public function _before(FunctionalTester $I)
	{
	}

	// tests
	public function tryToTest(FunctionalTester $I)
	{
		$I->assertTrue( true );
	}
}
