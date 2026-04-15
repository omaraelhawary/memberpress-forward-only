<?php
/**
 * @package MemberPress_Forward_Only
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests for Mepr_Forward_Only_Settings::parse_list.
 */
class SettingsParseListTest extends TestCase {

	/**
	 * @return void
	 */
	public function test_parse_list_splits_on_newlines_and_commas(): void {
		$out = Mepr_Forward_Only_Settings::parse_list( "Foo Bar, baz\nqux" );
		$this->assertSame( array( 'foo-bar', 'baz', 'qux' ), $out );
	}

	/**
	 * @return void
	 */
	public function test_parse_list_deduplicates_and_trims(): void {
		$out = Mepr_Forward_Only_Settings::parse_list( "page, page\npost" );
		$this->assertSame( array( 'page', 'post' ), $out );
	}

	/**
	 * @return void
	 */
	public function test_parse_list_empty_returns_empty_array(): void {
		$this->assertSame( array(), Mepr_Forward_Only_Settings::parse_list( '' ) );
		$this->assertSame( array(), Mepr_Forward_Only_Settings::parse_list( "  \n  , " ) );
	}
}
