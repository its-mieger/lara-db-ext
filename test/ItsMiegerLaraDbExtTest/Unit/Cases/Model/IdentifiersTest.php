<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.12.18
	 * Time: 07:17
	 */

	namespace ItsMiegerLaraDbExtTest\Unit\Cases\Model;



	use ItsMiegerLaraDbExtTest\Model\TestModelDummyConnection;
	use ItsMiegerLaraDbExtTest\Model\TestModelDummyConnectionPrefixed;
	use ItsMiegerLaraDbExtTest\Unit\TestCase;

	class IdentifiersTest extends TestCase
	{
		/**
		 * @inheritDoc
		 */
		protected function setUp() {
			parent::setUp();
		}


		public function testTable() {
			$this->assertEquals('test_table', TestModelDummyConnection::table());
		}

		public function testTable_notPrefixed() {
			$this->assertEquals('test_table', TestModelDummyConnection::table(false));
		}

		public function testTableRaw() {
			$this->assertEquals('"test_table"', TestModelDummyConnection::tableRaw());
		}

		public function testField() {
			$this->assertEquals('test_table.id', TestModelDummyConnection::field('id'));
		}

		public function testField_notPrefixed() {
			$this->assertEquals('test_table.id', TestModelDummyConnection::field('id', false));
		}

		public function testFieldRaw() {
			$this->assertEquals('"test_table"."id"', TestModelDummyConnection::fieldRaw('id'));
		}

		public function testQuoteIdentifier() {
			$this->assertEquals('"id"', TestModelDummyConnection::quoteIdentifier('id'));
		}

		public function testQuoteIdentifier_segments() {
			$this->assertEquals('"a_table"."id"', TestModelDummyConnection::quoteIdentifier('a_table.id'));
		}

		public function testTable_connectionWithTablePrefix() {
			$this->assertEquals('myPfx_test_table', TestModelDummyConnectionPrefixed::table());
		}

		public function testTable_notPrefixed_connectionWithTablePrefix() {
			$this->assertEquals('test_table', TestModelDummyConnectionPrefixed::table(false));
		}

		public function testTableRaw_connectionWithTablePrefix() {
			$this->assertEquals('"myPfx_test_table"', TestModelDummyConnectionPrefixed::tableRaw());
		}

		public function testField_connectionWithTablePrefix() {
			$this->assertEquals('myPfx_test_table.id', TestModelDummyConnectionPrefixed::field('id'));
		}

		public function testField_notPrefixed_connectionWithTablePrefix() {
			$this->assertEquals('test_table.id', TestModelDummyConnectionPrefixed::field('id', false));
		}

		public function testFieldRaw_connectionWithTablePrefix() {
			$this->assertEquals('"myPfx_test_table"."id"', TestModelDummyConnectionPrefixed::fieldRaw('id'));
		}

		public function testQuoteIdentifier_connectionWithTablePrefix() {
			$this->assertEquals('"id"', TestModelDummyConnectionPrefixed::quoteIdentifier('id'));
		}

		public function testQuoteIdentifier_segments_connectionWithTablePrefix() {
			$this->assertEquals('"a_table"."id"', TestModelDummyConnectionPrefixed::quoteIdentifier('a_table.id'));
		}
	}