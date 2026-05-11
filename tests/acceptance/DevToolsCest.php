<?php
/**
 * Tests for the Altis Dev Tools Module.
 *
 * phpcs:disable WordPress.Files, WordPress.NamingConventions, PSR1.Classes.ClassDeclaration.MissingNamespace, HM.Functions.NamespacedFunctions
 */
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
	 * Confirm the Altis-specific Query Monitor panels are rendered.
	 *
	 * Query Monitor v4 mounts its UI in a React app inside a shadow DOM
	 * (#query-monitor-container) and moves the server-rendered panel HTML
	 * from `<div id="query-monitor-fallbacks">` into the shadow tree once
	 * React boots. That means Codeception's `see()`/`seeInPageSource()`
	 * can't reach the rendered panel content — `see()` only walks the
	 * light DOM, and `getPageSource()` returns the post-React serialized
	 * DOM where the fallback nodes have already been moved.
	 *
	 * Instead, fetch the raw server response over a same-session XHR and
	 * assert against that. We're verifying the three Altis-specific panel
	 * outputters (Altis Config, ElasticPress, AWS X-Ray) emit the expected
	 * markup; QM itself owns the client-side rendering and is tested
	 * upstream.
	 *
	 * @param AcceptanceTester $I Tester
	 */
	public function testQueryMonitor( AcceptanceTester $I ) {
		$I->wantToTest( 'Altis-specific Query Monitor panels render server-side.' );
		$I->loginAsAdmin();
		$I->amOnAdminPage( '/' );

		// Grab the raw server-rendered HTML response within the authenticated
		// browser session. Synchronous XHR is deprecated but still supported
		// in Chrome and works here without needing to thread cookies into a
		// separate PHP HTTP client.
		$source = $I->executeJS(
			'const xhr = new XMLHttpRequest();' .
			'xhr.open("GET", window.location.href, false);' .
			'xhr.send();' .
			'return xhr.responseText;'
		);

		// Altis Config panel: "Module" column heading.
		PHPUnit\Framework\Assert::assertStringContainsString(
			'id="qm-altis-config-container"',
			$source,
			'Altis Config QM panel fallback container missing from page source.'
		);
		PHPUnit\Framework\Assert::assertStringContainsString(
			'<th>Module</th>',
			$source,
			'Altis Config panel missing expected "Module" column header.'
		);

		// ElasticPress panel.
		PHPUnit\Framework\Assert::assertStringContainsString(
			'id="qm-debug_bar_ep_debug_bar_elasticpress-container"',
			$source,
			'ElasticPress QM panel fallback container missing from page source.'
		);
		PHPUnit\Framework\Assert::assertStringContainsString(
			'Total ElasticPress Queries:',
			$source,
			'ElasticPress panel missing expected "Total ElasticPress Queries:" text.'
		);

		// AWS X-Ray panel: "Segment Name" column heading.
		PHPUnit\Framework\Assert::assertStringContainsString(
			'id="qm-aws-xray-container"',
			$source,
			'AWS X-Ray QM panel fallback container missing from page source.'
		);
		PHPUnit\Framework\Assert::assertStringContainsString(
			'<th>Segment Name</th>',
			$source,
			'AWS X-Ray panel missing expected "Segment Name" column header.'
		);
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
