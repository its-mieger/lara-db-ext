<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.12.18
	 * Time: 11:11
	 */

	namespace ItsMiegerLaraDbExtTest\Unit\Cases\Model;


	use Illuminate\Database\MySqlConnection;
	use Illuminate\Database\Query\Expression;
	use Illuminate\Foundation\Testing\DatabaseTransactions;
	use ItsMiegerLaraDbExtTest\Model\TestModelMassInsert;
	use ItsMiegerLaraDbExtTest\Unit\TestCase;
	use PHPUnit\Framework\SkippedTestError;

	class MassInsertsTest extends TestCase
	{
		use DatabaseTransactions;

		/**
		 * @inheritdoc
		 */
		public function setUp() {
			parent::setUp();

			// we need a MySQL connection
			if (!(\DB::connection() instanceof MySqlConnection))
				throw new SkippedTestError('This test requires a MySQL connection.');
		}

		public function testInsertDuplicateKey_noDuplicates_singleRow() {

			TestModelMassInsert::insertOnDuplicateKey([
				'name' => 'name 1',
				'u'    => 'a',
			]);


			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'name 1', 'u' => 'a']);
		}

		public function testInsertDuplicateKey_noDuplicates() {

			TestModelMassInsert::insertOnDuplicateKey([
				[
					'name' => 'name 1',
					'u'    => 'a',
				],
				[
					'name' => 'name 2',
					'u'    => 'b',
				],
			]);


			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'name 1', 'u' => 'a']);
			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'name 2', 'u' => 'b']);
		}

		public function testInsertDuplicateKey() {

			$r1 = factory(TestModelMassInsert::class)->create();

			TestModelMassInsert::insertOnDuplicateKey([
				[
					'name' => 'updatedName',
					'u'    => $r1->u,
				],
				[
					'name' => 'name 2',
					'u'    => 'another',
				],
			]);


			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'updatedName', 'u' => $r1->u]);
			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'name 2', 'u' => 'another']);
			$this->assertDatabaseMissing(TestModelMassInsert::table(), ['name' => $r1->name, 'u' => $r1->u]);
		}

		public function testInsertDuplicateKey_updateWithConst() {

			$r1 = factory(TestModelMassInsert::class)->create();

			TestModelMassInsert::insertOnDuplicateKey([
				[
					'name' => 'name 1',
					'u'    => $r1->u,
				],
				[
					'name' => 'name 2',
					'u'    => 'another',
				],
			], [
				'name' => 'x'
			]);


			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'x', 'u' => $r1->u]);
			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'name 2', 'u' => 'another']);
			$this->assertDatabaseMissing(TestModelMassInsert::table(), ['name' => $r1->name, 'u' => $r1->u]);
			$this->assertDatabaseMissing(TestModelMassInsert::table(), ['name' => 'name 1', 'u' => $r1->u]);
		}

		public function testInsertDuplicateKey_updateWithExpression() {

			$r1 = factory(TestModelMassInsert::class)->create();

			TestModelMassInsert::insertOnDuplicateKey([
				[
					'name' => 'name 1',
					'u'    => $r1->u,
				],
				[
					'name' => 'name 2',
					'u'    => 'another',
				],
			], [
				'name' => new Expression('substr(values(name), 1, 3)')
			]);


			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'nam', 'u' => $r1->u]);
			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'name 2', 'u' => 'another']);
			$this->assertDatabaseMissing(TestModelMassInsert::table(), ['name' => $r1->name, 'u' => $r1->u]);
			$this->assertDatabaseMissing(TestModelMassInsert::table(), ['name' => 'name 1', 'u' => $r1->u]);
		}

		public function testInsertDuplicateKey_updateOnlyGivenColumns() {

			$r1 = factory(TestModelMassInsert::class)->create();

			TestModelMassInsert::insertOnDuplicateKey([
				[
					'name' => 'name 1',
					'u'    => $r1->u,
				],
				[
					'name' => 'name 2',
					'u'    => 'another',
				],
			], [
				'u'
			]);


			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => $r1->name, 'u' => $r1->u]);
			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'name 2', 'u' => 'another']);
			$this->assertDatabaseMissing(TestModelMassInsert::table(), ['name' => 'name 1', 'u' => $r1->u]);
		}

		public function testInsertIgnore() {

			$r1 = factory(TestModelMassInsert::class)->create();

			TestModelMassInsert::insertIgnore([
				[
					'name' => 'updatedName',
					'u'    => $r1->u,
				],
				[
					'name' => 'name 2',
					'u'    => 'another',
				],
			]);

			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => $r1->name, 'u' => $r1->u]);
			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'name 2', 'u' => 'another']);
			$this->assertDatabaseMissing(TestModelMassInsert::table(), ['name' => 'updatedName', 'u' => $r1->u]);
		}

		public function testReplace() {

			$r1 = factory(TestModelMassInsert::class)->create();

			TestModelMassInsert::replace([
				[
					'name' => 'updatedName',
					'u'    => $r1->u,
				],
				[
					'name' => 'name 2',
					'u'    => 'another',
				],
			]);

			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'updatedName', 'u' => $r1->u]);
			$this->assertDatabaseHas(TestModelMassInsert::table(), ['name' => 'name 2', 'u' => 'another']);
			$this->assertDatabaseMissing(TestModelMassInsert::table(), ['name' => $r1->name, 'u' => $r1->u]);
		}

	}
