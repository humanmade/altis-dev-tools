<?php
/**
 * Tests for the Altis Dev Tools Module.
 *
 * phpcs:disable WordPress.Files, WordPress.NamingConventions, PSR1.Classes.ClassDeclaration.MissingNamespace, HM.Functions.NamespacedFunctions
 */
use Codeception\Util\Locator;

/**
 * Test Query Monitor and it's tabs render correctly.
 */
class DevToolsCest {

	/**
	 * Rollback callback for the Dev Tools activation bootstrap call.
	 *
	 * @var callable
	 */
	protected $rollback = null;

	/**
	 * Make sure Dev Tools is activated.
	 *
	 * @param AcceptanceTester $I Actor object.
	 *
	 * @return void
	 */
	public function _before( AcceptanceTester $I ) {
		$this->rollback = $I->bootstrapWith( [ __CLASS__, '_enableDevTools' ] );
	}

	/**
	 * Deactivate Dev Tools after tests are finished.
	 *
	 * @param AcceptanceTester $I Actor object.
	 *
	 * @return void
	 */
	public function _after( AcceptanceTester $I ) {
		call_user_func( $this->rollback );
	}

	/**
	 * Open Query Monitor / Dev Tools panel and check tabs are working.
	 *
	 * @param AcceptanceTester $I Tester
	 */
	public function testQueryMonitor( AcceptanceTester $I ) {
		$I->wantToTest( 'Open Query Monitor / Dev Tools panel and check all tabs are working.' );
		$I->loginAsAdmin();
		$I->amOnAdminPage( '/' );

		// See the Query Monitor link in menu.
		$I->moveMouseOver( '#wp-admin-bar-query-monitor' );

		// Check the Altis Config tab and content renders, using the "Module" column heading.
		$I->seeLink( 'Altis Config' );
		$I->click( 'Altis Config' );
		$I->see( 'Module', 'th' );

		// Test and confirm the Hooks & Actions tab and content renders, using the "Action" column heading.
		$I->click( Locator::find( 'button', ['data-qm-href' => '#qm-hooks'] ) );
		$I->see( 'Action', 'th' );

		// Test and confirm the ElasticPress tab and content renders, using the "Total ElasticPress Queries" span.
		$I->click( Locator::find( 'button', ['data-qm-href' => '#qm-debug_bar_ep_debug_bar_elasticpress'] ) );
		$I->see( 'Total ElasticPress Queries:', 'span' );

		// Test and confrim AWS X-Ray tab and content renders, using the "Segment Name" column heading.
		$I->click( Locator::find( 'button', ['data-qm-href' => '#qm-aws-xray'] ) );
		$I->see( 'Segment Name', 'th' );
	}

	/**
	 * Activate Dev Tools.
	 *
	 * @return void
	 */
	public static function _enableDevTools() {
		add_filter( 'altis.config', function( $config ) {
			$config['modules']['dev-tools']['enabled'] = true;
			return $config;
		} );
	}

}
