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
	use ItsMiegerLaraDbExtTest\Model\TestBulkImport;
	use ItsMiegerLaraDbExtTest\Model\TestModelMassInsert;
	use ItsMiegerLaraDbExtTest\Model\TestModelMassInsertMutated;
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

		public function testInsertDuplicateKey_noDuplicates_singleRow_mutated() {

			TestModelMassInsertMutated::insertOnDuplicateKey([
				'name' => 'name 1',
				'u'    => 'a',
				'data' => [
					'x' => 1,
					'y' => 99,
				]
			]);


			$ret = TestModelMassInsertMutated::where('name', 'name 1')->first();
			$this->assertEquals('name 1', $ret->name);
			$this->assertEquals('a', $ret->u);
			$this->assertEquals([
				'x' => 1,
				'y' => 99,
			], $ret->data);

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

		public function testInsertDuplicateKey_noDuplicates_mutated() {

			TestModelMassInsertMutated::insertOnDuplicateKey([
				[
					'name' => 'name 1',
					'u'    => 'a',
					'data' => [
						'x' => 1,
						'y' => 99,
					]
				],
				[
					'name' => 'name 2',
					'u'    => 'b',
					'data' => [
						'x' => 2,
						'y' => 12,
					]
				],
			]);

			$ret = TestModelMassInsertMutated::where('name', 'name 1')->first();
			$this->assertEquals('name 1', $ret->name);
			$this->assertEquals('a', $ret->u);
			$this->assertEquals([
				'x' => 1,
				'y' => 99,
			], $ret->data);

			$ret = TestModelMassInsertMutated::where('name', 'name 2')->first();
			$this->assertEquals('name 2', $ret->name);
			$this->assertEquals('b', $ret->u);
			$this->assertEquals([
				'x' => 2,
				'y' => 12,
			], $ret->data);

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

		public function testInsertDuplicateKey_mutated() {

			$r1 = factory(TestModelMassInsertMutated::class)->create();

			TestModelMassInsertMutated::insertOnDuplicateKey([
				[
					'name' => 'updatedName',
					'u'    => $r1->u,
					'data' => [
						'x' => 1,
						'y' => 99,
					]
				],
				[
					'name' => 'name 2',
					'u'    => 'another',
					'data' => [
						'x' => 2,
						'y' => 12,
					]
				],
			]);

			$ret = TestModelMassInsertMutated::where('name', 'updatedName')->first();
			$this->assertEquals('updatedName', $ret->name);
			$this->assertEquals($r1->u, $ret->u);
			$this->assertEquals([
				'x' => 1,
				'y' => 99,
			], $ret->data);

			$ret = TestModelMassInsertMutated::where('name', 'name 2')->first();
			$this->assertEquals('name 2', $ret->name);
			$this->assertEquals('another', $ret->u);
			$this->assertEquals([
				'x' => 2,
				'y' => 12,
			], $ret->data);

			$ret = TestModelMassInsertMutated::where('name', $r1->name)->first();
			$this->assertNull($ret);

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

		public function testInsertDuplicateKey_updateWithMutatedConst() {

			$r1 = factory(TestModelMassInsertMutated::class)->create();

			TestModelMassInsertMutated::insertOnDuplicateKey([
				[
					'name' => 'name 1',
					'u'    => $r1->u,
					'data' => [
						'x' => 1,
						'y' => 99,
					]
				],
				[
					'name' => 'name 2',
					'u'    => 'another',
					'data' => [
						'x' => 2,
						'y' => 12,
					]
				],
			], [
				'name' => 'x',
				'data' => [
					'x' => 11,
					'y' => 11,
				]
			]);

			$ret = TestModelMassInsertMutated::where('name', 'x')->first();
			$this->assertEquals('x', $ret->name);
			$this->assertEquals($r1->u, $ret->u);
			$this->assertEquals([
				'x' => 11,
				'y' => 11,
			], $ret->data);

			$ret = TestModelMassInsertMutated::where('name', 'name 2')->first();
			$this->assertEquals('name 2', $ret->name);
			$this->assertEquals('another', $ret->u);
			$this->assertEquals([
				'x' => 2,
				'y' => 12,
			], $ret->data);

			$ret = TestModelMassInsertMutated::where('name', $r1->name)->first();
			$this->assertNull($ret);

			$ret = TestModelMassInsertMutated::where('name', 'name 1')->first();
			$this->assertNull($ret);

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

		public function testInsertIgnore_mutated() {

			$r1 = factory(TestModelMassInsertMutated::class)->create();

			TestModelMassInsertMutated::insertIgnore([
				[
					'name' => 'updatedName',
					'u'    => $r1->u,
					'data' => [
						'x' => 1,
						'y' => 99,
					]
				],
				[
					'name' => 'name 2',
					'u'    => 'another',
					'data' => [
						'x' => 2,
						'y' => 12,
					]
				],
			]);


			$ret = TestModelMassInsertMutated::where('name', $r1->name)->first();
			$this->assertEquals($r1->name, $ret->name);
			$this->assertEquals($r1->u, $ret->u);
			$this->assertEquals($r1->data, $ret->data);

			$ret = TestModelMassInsertMutated::where('name', 'name 2')->first();
			$this->assertEquals('name 2', $ret->name);
			$this->assertEquals('another', $ret->u);
			$this->assertEquals([
				'x' => 2,
				'y' => 12,
			], $ret->data);

			$ret = TestModelMassInsertMutated::where('name', 'updatedName')->first();
			$this->assertNull($ret);

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

		public function testReplace_mutated() {

			$r1 = factory(TestModelMassInsertMutated::class)->create();

			TestModelMassInsertMutated::replace([
				[
					'name' => 'updatedName',
					'u'    => $r1->u,
					'data' => [
						'x' => 1,
						'y' => 99,
					]
				],
				[
					'name' => 'name 2',
					'u'    => 'another',
					'data' => [
						'x' => 2,
						'y' => 12,
					]
				],
			]);

			$ret = TestModelMassInsertMutated::where('name', 'updatedName')->first();
			$this->assertEquals('updatedName', $ret->name);
			$this->assertEquals($r1->u, $ret->u);
			$this->assertEquals([
				'x' => 1,
				'y' => 99,
			], $ret->data);

			$ret = TestModelMassInsertMutated::where('name', 'name 2')->first();
			$this->assertEquals('name 2', $ret->name);
			$this->assertEquals('another', $ret->u);
			$this->assertEquals([
				'x' => 2,
				'y' => 12,
			], $ret->data);

			$ret = TestModelMassInsertMutated::where('name', $r1->name)->first();
			$this->assertNull($ret);
		}

		public function testUpdateJoined() {

			$r1 = factory(TestBulkImport::class)->create();
			$r2 = factory(TestBulkImport::class)->create();
			$r3 = factory(TestBulkImport::class)->create();


			TestBulkImport::updateJoined(
				[
					['id' => $r1->id, 'a' => 'v1.1', 'b' => 'v1.2', 'u' => 'v1.3'],
					['id' => $r2->id, 'a' => 'v2.1', 'b' => 'v2.2', 'u' => 'v2.3'],
					['id' => $r3->id, 'a' => 'v3.1', 'b' => 'v3.2', 'u' => 'v3.3'],
					['id' => 0, 'a' => 'v4.1', 'b' => 'v4.2', 'u' => 'v4.3'],
				]
			);


			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r1->id, 'a' => 'v1.1', 'b' => 'v1.2', 'u' => 'v1.3']);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r2->id, 'a' => 'v2.1', 'b' => 'v2.2', 'u' => 'v2.3']);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r3->id, 'a' => 'v3.1', 'b' => 'v3.2', 'u' => 'v3.3']);
			$this->assertDatabaseMissing(TestBulkImport::table(), ['id' => 0]);
		}

		public function testUpdateJoined_joinOnExpression() {

			$r1 = factory(TestBulkImport::class)->create();
			$r2 = factory(TestBulkImport::class)->create();
			$r3 = factory(TestBulkImport::class)->create();


			TestBulkImport::updateJoined(
				[
					['id' => $r1->id, 'a' => 'v1.1', 'b' => 'v1.2', 'u' => 'v1.3'],
					['id' => $r2->id, 'a' => 'v2.1', 'b' => 'v2.2', 'u' => 'v2.3'],
					['id' => $r3->id, 'a' => 'v3.1', 'b' => 'v3.2', 'u' => 'v3.3'],
					['id' => 0, 'a' => 'v4.1', 'b' => 'v4.2', 'u' => 'v4.3'],
				],
				[
					new Expression(TestBulkImport::table() . '.id = data.id')
				]
			);


			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r1->id, 'a' => 'v1.1', 'b' => 'v1.2', 'u' => 'v1.3']);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r2->id, 'a' => 'v2.1', 'b' => 'v2.2', 'u' => 'v2.3']);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r3->id, 'a' => 'v3.1', 'b' => 'v3.2', 'u' => 'v3.3']);
			$this->assertDatabaseMissing(TestBulkImport::table(), ['id' => 0]);
		}

		public function testUpdateJoined_joinOnColumnExpression() {

			$r1 = factory(TestBulkImport::class)->create();
			$r2 = factory(TestBulkImport::class)->create();
			$r3 = factory(TestBulkImport::class)->create();


			TestBulkImport::updateJoined(
				[
					['id' => $r1->id + 1, 'a' => 'v1.1', 'b' => 'v1.2', 'u' => 'v1.3'],
					['id' => $r2->id + 1, 'a' => 'v2.1', 'b' => 'v2.2', 'u' => 'v2.3'],
					['id' => $r3->id + 1, 'a' => 'v3.1', 'b' => 'v3.2', 'u' => 'v3.3'],
					['id' => 0, 'a' => 'v4.1', 'b' => 'v4.2', 'u' => 'v4.3'],
				],
				[
					'id' => new Expression('data.id - 1')
				],
				[
					'a',
					'b',
					'u',
				]
			);


			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r1->id, 'a' => 'v1.1', 'b' => 'v1.2', 'u' => 'v1.3']);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r2->id, 'a' => 'v2.1', 'b' => 'v2.2', 'u' => 'v2.3']);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r3->id, 'a' => 'v3.1', 'b' => 'v3.2', 'u' => 'v3.3']);
			$this->assertDatabaseMissing(TestBulkImport::table(), ['id' => 0]);
		}

		public function testUpdateJoined_joinOnDifferentColumn() {

			$r1 = factory(TestBulkImport::class)->create();
			$r2 = factory(TestBulkImport::class)->create();
			$r3 = factory(TestBulkImport::class)->create();


			TestBulkImport::updateJoined(
				[
					['id2' => $r1->id, 'a' => 'v1.1', 'b' => 'v1.2', 'u' => 'v1.3'],
					['id2' => $r2->id, 'a' => 'v2.1', 'b' => 'v2.2', 'u' => 'v2.3'],
					['id2' => $r3->id, 'a' => 'v3.1', 'b' => 'v3.2', 'u' => 'v3.3'],
					['id2' => 0, 'a' => 'v4.1', 'b' => 'v4.2', 'u' => 'v4.3'],
				],
				[
					'id' => 'id2',
				]
			);


			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r1->id, 'a' => 'v1.1', 'b' => 'v1.2', 'u' => 'v1.3']);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r2->id, 'a' => 'v2.1', 'b' => 'v2.2', 'u' => 'v2.3']);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r3->id, 'a' => 'v3.1', 'b' => 'v3.2', 'u' => 'v3.3']);
			$this->assertDatabaseMissing(TestBulkImport::table(), ['id' => 0]);
		}

		public function testUpdateJoined_updateFieldsOnlySomeColumns() {

			$r1 = factory(TestBulkImport::class)->create();
			$r2 = factory(TestBulkImport::class)->create();
			$r3 = factory(TestBulkImport::class)->create();


			TestBulkImport::updateJoined(
				[
					['id' => $r1->id, 'a' => 'v1.1', 'b' => 'v1.2', 'u' => 'v1.3'],
					['id' => $r2->id, 'a' => 'v2.1', 'b' => 'v2.2', 'u' => 'v2.3'],
					['id' => $r3->id, 'a' => 'v3.1', 'b' => 'v3.2', 'u' => 'v3.3'],
					['id' => 0, 'a' => 'v4.1', 'b' => 'v4.2', 'u' => 'v4.3'],
				],
				[
					'id',
				],
				[
					'a',
					'b',
				]
			);


			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r1->id, 'a' => 'v1.1', 'b' => 'v1.2', 'u' => $r1->u]);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r2->id, 'a' => 'v2.1', 'b' => 'v2.2', 'u' => $r2->u]);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r3->id, 'a' => 'v3.1', 'b' => 'v3.2', 'u' => $r3->u]);
			$this->assertDatabaseMissing(TestBulkImport::table(), ['id' => 0]);
		}

		public function testUpdateJoined_updateFieldsExpression() {

			$r1 = factory(TestBulkImport::class)->create();
			$r2 = factory(TestBulkImport::class)->create();
			$r3 = factory(TestBulkImport::class)->create();


			TestBulkImport::updateJoined(
				[
					['id' => $r1->id, 'a' => 'v1.1', 'b' => 'v1.2', 'u' => 'v1.3'],
					['id' => $r2->id, 'a' => 'v2.1', 'b' => 'v2.2', 'u' => 'v2.3'],
					['id' => $r3->id, 'a' => 'v3.1', 'b' => 'v3.2', 'u' => 'v3.3'],
					['id' => 0, 'a' => 'v4.1', 'b' => 'v4.2', 'u' => 'v4.3'],
				],
				[
					'id',
				],
				[
					new Expression(TestBulkImport::tableRaw() . '.b = concat(data.b, ' . TestBulkImport::tableRaw() . '.id)')
				]
			);


			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r1->id, 'a' => $r1->a, 'b' => 'v1.2' . $r1->id, 'u' => $r1->u]);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r2->id, 'a' => $r2->a, 'b' => 'v2.2' . $r2->id, 'u' => $r2->u]);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r3->id, 'a' => $r3->a, 'b' => 'v3.2' . $r3->id, 'u' => $r3->u]);
			$this->assertDatabaseMissing(TestBulkImport::table(), ['id' => 0]);
		}

		public function testUpdateJoined_updateFieldsColumnWithExpression() {

			$r1 = factory(TestBulkImport::class)->create();
			$r2 = factory(TestBulkImport::class)->create();
			$r3 = factory(TestBulkImport::class)->create();


			TestBulkImport::updateJoined(
				[
					['id' => $r1->id, 'a' => 'v1.1', 'b' => 'v1.2', 'u' => 'v1.3'],
					['id' => $r2->id, 'a' => 'v2.1', 'b' => 'v2.2', 'u' => 'v2.3'],
					['id' => $r3->id, 'a' => 'v3.1', 'b' => 'v3.2', 'u' => 'v3.3'],
					['id' => 0, 'a' => 'v4.1', 'b' => 'v4.2', 'u' => 'v4.3'],
				],
				[
					'id',
				],
				[
					'a',
					'b' => new Expression('concat(data.b, ' . TestBulkImport::tableRaw() . '.id)'),
					'u'
				]
			);


			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r1->id, 'a' => 'v1.1', 'b' => 'v1.2' . $r1->id, 'u' => 'v1.3']);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r2->id, 'a' => 'v2.1', 'b' => 'v2.2' . $r2->id, 'u' => 'v2.3']);
			$this->assertDatabaseHas(TestBulkImport::table(), ['id' => $r3->id, 'a' => 'v3.1', 'b' => 'v3.2' . $r3->id, 'u' => 'v3.3']);
			$this->assertDatabaseMissing(TestBulkImport::table(), ['id' => 0]);
		}

	}
