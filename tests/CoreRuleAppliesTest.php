<?php
/**
 * @package MemberPress_Forward_Only
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests for Mepr_Forward_Only_Core::rule_applies (options-based).
 */
class CoreRuleAppliesTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_test_options'] = array();
		Mepr_Forward_Only_Settings::flush_cache();
	}

	/**
	 * @return void
	 */
	public function test_empty_configured_rule_list_applies_to_all_rules(): void {
		$GLOBALS['wp_test_options'][ Mepr_Forward_Only_Bootstrap::OPTION_NAME ] = array(
			'rule_ids' => array(),
		);

		$this->assertTrue( Mepr_Forward_Only_Core::rule_applies( 1 ) );
		$this->assertTrue( Mepr_Forward_Only_Core::rule_applies( 999 ) );
	}

	/**
	 * @return void
	 */
	public function test_restricted_list_only_matches_ids(): void {
		$GLOBALS['wp_test_options'][ Mepr_Forward_Only_Bootstrap::OPTION_NAME ] = array(
			'rule_ids' => array( 5, 10 ),
		);

		$this->assertTrue( Mepr_Forward_Only_Core::rule_applies( 5 ) );
		$this->assertTrue( Mepr_Forward_Only_Core::rule_applies( 10 ) );
		$this->assertFalse( Mepr_Forward_Only_Core::rule_applies( 3 ) );
	}
}
