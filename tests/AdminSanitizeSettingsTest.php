<?php
/**
 * @package MemberPress_Forward_Only
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests for Mepr_Forward_Only_Admin::sanitize_settings.
 */
class AdminSanitizeSettingsTest extends TestCase {

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
	public function test_parses_rule_ids_from_mixed_text(): void {
		$out = Mepr_Forward_Only_Admin::sanitize_settings(
			array(
				'rule_ids_text' => "12\n34, 12",
				'message'       => '<p>Hello</p>',
			)
		);

		$this->assertSame( array( 12, 34 ), $out['rule_ids'] );
		$this->assertSame( '<p>Hello</p>', $out['message'] );
	}

	/**
	 * @return void
	 */
	public function test_empty_rule_text_clears_rule_ids(): void {
		$GLOBALS['wp_test_options'][ Mepr_Forward_Only_Bootstrap::OPTION_NAME ] = array(
			'rule_ids' => array( 99 ),
			'message'  => 'prev',
		);

		$out = Mepr_Forward_Only_Admin::sanitize_settings(
			array(
				'message' => '<p>ok</p>',
			)
		);

		$this->assertSame( array(), $out['rule_ids'] );
	}

	/**
	 * @return void
	 */
	public function test_blank_message_falls_back_to_previous_or_default(): void {
		$GLOBALS['wp_test_options'][ Mepr_Forward_Only_Bootstrap::OPTION_NAME ] = array(
			'message' => '<p>Keep me</p>',
		);

		$out = Mepr_Forward_Only_Admin::sanitize_settings(
			array(
				'message' => '   ',
			)
		);

		$this->assertSame( '<p>Keep me</p>', $out['message'] );
	}

	/**
	 * @return void
	 */
	public function test_sanitizes_exclusion_fields(): void {
		$out = Mepr_Forward_Only_Admin::sanitize_settings(
			array(
				'excluded_post_types' => "page\npost",
				'excluded_categories' => 'news, updates',
			)
		);

		$this->assertSame( "page\npost", $out['excluded_post_types'] );
		$this->assertSame( 'news, updates', $out['excluded_categories'] );
	}
}
