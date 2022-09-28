<?php
/**
 * Tests for Altis Dev Tools Module.
 *
 * phpcs:disable WordPress.Files, WordPress.NamingConventions, PSR1.Classes.ClassDeclaration.MissingNamespace, HM.Functions.NamespacedFunctions
 */
use Codeception\Util\Locator;

/**
 * Test query monitor and it's tabs render correctly.
 */
class DevToolsCest {

	/**
	 * Open Query Monitor / Dev Tools panel and check all tabs are working.
	 *
	 * @param AcceptanceTester $I Tester
	 */
	public function testQueryMonitor( AcceptanceTester $I ) {
		$I->wantToTest( 'Open Query Monitor / Dev Tools panel and check all tabs are working.' );
		$I->loginAsAdmin();
		$I->amOnAdminPage( '/' );

		// See the query monitor link in menu.
		$I->moveMouseOver( '#wp-admin-bar-query-monitor' );

		// Test and confirm the Altis Config tab renders, using the module column name.
		$I->seeLink( 'Altis Config' );
		$I->click( 'Altis Config' );
		$I->see( 'Module', 'th' );

		// Test and confirm the Hooks & Actions Tab.
		$I->click( Locator::find( 'button', ['data-qm-href' => '#qm-hooks'] ) );
		$I->see( 'Action', 'th' );

		// Test and confirm the ElasticPress Tab.
		$I->click( Locator::find( 'button', ['data-qm-href' => '#qm-debug_bar_ep_debug_bar_elasticpress'] ) );
		$I->see( 'Total ElasticPress Queries:', 'span' );

		// Test and confrim AWS X-Ray tab,
		$I->click( Locator::find( 'button', ['data-qm-href' => '#qm-aws-xray'] ) );
		$I->see( 'Segment Name', 'th' );
	}

}