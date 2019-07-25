<?php


	namespace ItsMiegerLaraDbExtTest\Unit\Cases\Model;


	use Carbon\Carbon;
	use Illuminate\Database\Eloquent\Collection;
	use Illuminate\Database\MySqlConnection;
	use Illuminate\Foundation\Testing\DatabaseTransactions;
	use ItsMiegerLaraDbExtTest\Model\TestBulkImport;
	use ItsMiegerLaraDbExtTest\Model\TestBulkImportWithoutCreatedMark;
	use ItsMiegerLaraDbExtTest\Model\TestBulkImportWithoutModifiedMark;
	use ItsMiegerLaraDbExtTest\Unit\TestCase;
	use MehrIt\Buffer\FlushingBuffer;
	use PHPUnit\Framework\SkippedTestError;

	class BulkImportTest extends TestCase
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

		protected function createExisting(array $data) {
			TestBulkImport::bulkImport()
				->updateFields(['a', 'b'])
				->modifiedWhen(['a', 'b'])
				->perform(function (FlushingBuffer $buffer) use ($data) {
					$buffer->addMultiple($data);
				});
		}

		public function testWhen_true() {

			$import = TestBulkImport::bulkImport();

			$invoked = false;

			$import->when(true, function($v) use (&$invoked, $import) {

				$this->assertSame($import, $v);

				$invoked = true;

			});

			$this->assertTrue($invoked);
		}

		public function testWhen_false() {

			$import = TestBulkImport::bulkImport();

			$invoked = false;

			$import->when(false, function() use (&$invoked) {

				$invoked = true;

			});

			$this->assertFalse($invoked);
		}


		public function testImport_onePerModificationType() {

			$now1 = new Carbon('-1 minute');
			$now2 = new Carbon();


			Carbon::setTestNow($now1);
			$this->createExisting([
				[
					'a' => 'vA1',
					'b' => 'vB1',
					'u' => 'toBeUpdated',
				],
				[
					'a' => 'vA2',
					'b' => 'vB2',
					'u' => 'toBeUnchanged',
				],
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing',
				],
			]);


			$createdCalled = 0;
			$created = [];

			$modifiedCalled = 0;
			$modified = [];

			$missingCalled = 0;
			$missing = [];

			Carbon::setTestNow($now2);

			TestBulkImport::bulkImport()
				->updateFields(['a', 'b'])
				->modifiedWhen(['a', 'b'])
				->callbackFields(['a', 'b', 'u'])
				->onCreated(function($records) use (&$created, &$createdCalled) {
					++$createdCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$created = array_merge($created, $records->map(function($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray());
				})
				->onModified(function($records) use (&$modified, &$modifiedCalled) {
					++$modifiedCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$modified = array_merge($modified, array_merge($modified, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->onMissing(function($records) use (&$missing, &$missingCalled) {
					++$missingCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$missing = array_merge($missing, array_merge($missing, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->perform(function(FlushingBuffer $buffer) {

					$buffer->addMultiple([
						[
							'a' => 'vA1a',
							'b' => 'vB1',
							'u' => 'toBeUpdated',
						],
						[
							'a' => 'vA2',
							'b' => 'vB2',
							'u' => 'toBeUnchanged',
						],
						[
							'a' => 'vA3a',
							'b' => 'vB3',
							'u' => 'toBeCreated',
						],
					]);

				});

			$this->assertSame(1, $createdCalled);
			$this->assertSame(1, $modifiedCalled);
			$this->assertSame(1, $missingCalled);
			$this->assertEquals([
				[
					'a' => 'vA3a',
					'b' => 'vB3',
					'u' => 'toBeCreated',
				],
			], $created);
			$this->assertEquals([
				[
					'a' => 'vA1a',
					'b' => 'vB1',
					'u' => 'toBeUpdated',
				]
			], $modified);
			$this->assertEquals([
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing',
				]
			], $missing);

			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3a',
				'b'          => 'vB3',
				'u'          => 'toBeCreated',
				'created_at' => $now2,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1a',
				'b'          => 'vB1',
				'u'          => 'toBeUpdated',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'vB2',
				'u'          => 'toBeUnchanged',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3',
				'b'          => 'vB3',
				'u'          => 'missing',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
		}

		public function testImport_multiplePerModificationType() {

			$now1 = new Carbon('-1 minute');
			$now2 = new Carbon();

			Carbon::setTestNow($now1);

			$this->createExisting([
				[
					'a' => 'vA1',
					'b' => 'vB1',
					'u' => 'toBeUpdated1',
				],
				[
					'a' => 'vA1',
					'b' => 'vB1',
					'u' => 'toBeUpdated2',
				],
				[
					'a' => 'vA2',
					'b' => 'vB2',
					'u' => 'toBeUnchanged1',
				],
				[
					'a' => 'vA2',
					'b' => 'vB2',
					'u' => 'toBeUnchanged2',
				],
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing1',
				],
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing2',
				],
			]);


			$createdCalled = 0;
			$created = [];

			$modifiedCalled = 0;
			$modified = [];

			$missingCalled = 0;
			$missing = [];

			Carbon::setTestNow($now2);

			TestBulkImport::bulkImport()
				->updateFields(['a', 'b'])
				->modifiedWhen(['a', 'b'])
				->callbackFields(['a', 'b', 'u'])
				->onCreated(function($records) use (&$created, &$createdCalled) {
					++$createdCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$created = array_merge($created, $records->map(function($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray());
				})
				->onModified(function($records) use (&$modified, &$modifiedCalled) {
					++$modifiedCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$modified = array_merge($modified, array_merge($modified, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->onMissing(function($records) use (&$missing, &$missingCalled) {
					++$missingCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$missing = array_merge($missing, array_merge($missing, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->perform(function(FlushingBuffer $buffer) {

					$buffer->addMultiple([
						[
							'a' => 'vA1a',
							'b' => 'vB1',
							'u' => 'toBeUpdated1',
						],
						[
							'a' => 'vA1a',
							'b' => 'vB1',
							'u' => 'toBeUpdated2',
						],
						[
							'a' => 'vA2',
							'b' => 'vB2',
							'u' => 'toBeUnchanged1',
						],
						[
							'a' => 'vA2',
							'b' => 'vB2',
							'u' => 'toBeUnchanged2',
						],
						[
							'a' => 'vA3a',
							'b' => 'vB3',
							'u' => 'toBeCreated1',
						],
						[
							'a' => 'vA3a',
							'b' => 'vB3',
							'u' => 'toBeCreated2',
						],
					]);

				});

			$this->assertSame(1, $createdCalled);
			$this->assertSame(1, $modifiedCalled);
			$this->assertSame(1, $missingCalled);
			$this->assertEquals([
				[
					'a' => 'vA3a',
					'b' => 'vB3',
					'u' => 'toBeCreated1',
				],
				[
					'a' => 'vA3a',
					'b' => 'vB3',
					'u' => 'toBeCreated2',
				],
			], $created);
			$this->assertEquals([
				[
					'a' => 'vA1a',
					'b' => 'vB1',
					'u' => 'toBeUpdated1',
				],
				[
					'a' => 'vA1a',
					'b' => 'vB1',
					'u' => 'toBeUpdated2',
				],
			], $modified);
			$this->assertEquals([
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing1',
				],
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing2',
				],
			], $missing);

			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3a',
				'b'          => 'vB3',
				'u'          => 'toBeCreated1',
				'created_at' => $now2,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3a',
				'b'          => 'vB3',
				'u'          => 'toBeCreated2',
				'created_at' => $now2,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1a',
				'b'          => 'vB1',
				'u'          => 'toBeUpdated1',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1a',
				'b'          => 'vB1',
				'u'          => 'toBeUpdated2',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'vB2',
				'u'          => 'toBeUnchanged1',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'vB2',
				'u'          => 'toBeUnchanged1',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3',
				'b'          => 'vB3',
				'u'          => 'missing1',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3',
				'b'          => 'vB3',
				'u'          => 'missing2',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
		}

		public function testImport_modifiedWhen() {

			$now1 = new Carbon('-1 minute');
			$now2 = new Carbon();

			Carbon::setTestNow($now1);

			$this->createExisting([
				[
					'a' => 'vA1',
					'b' => 'vB1',
					'u' => 'r1',
				],
				[
					'a' => 'vA2',
					'b' => 'vB2',
					'u' => 'r2',
				],
			]);


			$createdCalled = 0;
			$created       = [];

			$modifiedCalled = 0;
			$modified       = [];

			$missingCalled = 0;
			$missing       = [];

			Carbon::setTestNow($now2);

			TestBulkImport::bulkImport()
				->updateFields(['a', 'b'])
				->modifiedWhen(['a'])
				->callbackFields(['a', 'b', 'u'])
				->onCreated(function ($records) use (&$created, &$createdCalled) {
					++$createdCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$created = array_merge($created, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray());
				})
				->onModified(function ($records) use (&$modified, &$modifiedCalled) {
					++$modifiedCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$modified = array_merge($modified, array_merge($modified, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->onMissing(function ($records) use (&$missing, &$missingCalled) {
					++$missingCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$missing = array_merge($missing, array_merge($missing, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->perform(function (FlushingBuffer $buffer) {

					$buffer->addMultiple([
						[
							'a' => 'vA1_updated',
							'b' => 'vB1',
							'u' => 'r1',
						],
						[
							'a' => 'vA2',
							'b' => 'vB2_updated',
							'u' => 'r2',
						],
					]);

				});

			$this->assertSame(0, $createdCalled);
			$this->assertSame(1, $modifiedCalled);
			$this->assertSame(0, $missingCalled);
			$this->assertEquals([], $created);
			$this->assertEquals([
				[
					'a' => 'vA1_updated',
					'b' => 'vB1',
					'u' => 'r1',
				]
			], $modified);
			$this->assertEquals([], $missing);

			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1_updated',
				'b'          => 'vB1',
				'u'          => 'r1',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'vB2_updated',
				'u'          => 'r2',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
		}

		public function testImport_targetWhere() {

			$now1 = new Carbon('-1 minute');
			$now2 = new Carbon();



			Carbon::setTestNow($now1);
			$this->createExisting([
				[
					'a' => 'vA1',
					'b' => 'groupA',
					'u' => 'toBeUpdatedA',
				],
				[
					'a' => 'vA2',
					'b' => 'groupA',
					'u' => 'toBeUnchangedA',
				],
				[
					'a' => 'vA3',
					'b' => 'groupA',
					'u' => 'missingA',
				],
				[
					'a' => 'vA1',
					'b' => 'groupB',
					'u' => 'toBeUpdatedB',
				],
				[
					'a' => 'vA2',
					'b' => 'groupB',
					'u' => 'toBeUnchangedB',
				],
				[
					'a' => 'vA3',
					'b' => 'groupB',
					'u' => 'missingB',
				],
			]);


			$createdCalled = 0;
			$created       = [];

			$modifiedCalled = 0;
			$modified       = [];

			$missingCalled = 0;
			$missing       = [];

			Carbon::setTestNow($now2);

			TestBulkImport::bulkImport()
				->updateFields(['a', 'b'])
				->modifiedWhen(['a', 'b'])
				->targetWhere('b', 'groupA')
				->callbackFields(['a', 'b', 'u'])
				->onCreated(function ($records) use (&$created, &$createdCalled) {
					++$createdCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$created = array_merge($created, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray());
				})
				->onModified(function ($records) use (&$modified, &$modifiedCalled) {
					++$modifiedCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$modified = array_merge($modified, array_merge($modified, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->onMissing(function ($records) use (&$missing, &$missingCalled) {
					++$missingCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$missing = array_merge($missing, array_merge($missing, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->perform(function (FlushingBuffer $buffer) {

					$buffer->addMultiple([
						[
							'a' => 'vA1a',
							'b' => 'groupA',
							'u' => 'toBeUpdatedA',
						],
						[
							'a' => 'vA2',
							'b' => 'groupA',
							'u' => 'toBeUnchangedA',
						],
						[
							'a' => 'vA3a',
							'b' => 'groupA',
							'u' => 'toBeCreatedA',
						],
						// out of target where
						[
							'a' => 'vA1a',
							'b' => 'groupB',
							'u' => 'toBeUpdatedB',
						],
						[
							'a' => 'vA2',
							'b' => 'groupB',
							'u' => 'toBeUnchangedB',
						],
						[
							'a' => 'vA3a',
							'b' => 'groupB',
							'u' => 'toBeCreatedB',
						],
					]);

				});

			$this->assertSame(1, $createdCalled);
			$this->assertSame(1, $modifiedCalled);
			$this->assertSame(1, $missingCalled);

			$this->assertEquals([
				[
					'a' => 'vA3a',
					'b' => 'groupA',
					'u' => 'toBeCreatedA',
				],
			], $created);
			$this->assertEquals([
				[
					'a' => 'vA1a',
					'b' => 'groupA',
					'u' => 'toBeUpdatedA',
				]
			], $modified);
			$this->assertEquals([
				[
					'a' => 'vA3',
					'b' => 'groupA',
					'u' => 'missingA',
				]
			], $missing);

			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3a',
				'b'          => 'groupA',
				'u'          => 'toBeCreatedA',
				'created_at' => $now2,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1a',
				'b'          => 'groupA',
				'u'          => 'toBeUpdatedA',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'groupA',
				'u'          => 'toBeUnchangedA',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3',
				'b'          => 'groupA',
				'u'          => 'missingA',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);

			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3a',
				'b'          => 'groupB',
				'u'          => 'toBeCreatedB',
				'created_at' => $now2,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1a',
				'b'          => 'groupB',
				'u'          => 'toBeUpdatedB',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'groupB',
				'u'          => 'toBeUnchangedB',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3',
				'b'          => 'groupB',
				'u'          => 'missingB',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
		}

		public function testImport_targetWhere_multiple() {

			$now1 = new Carbon('-1 minute');
			$now2 = new Carbon();



			Carbon::setTestNow($now1);
			$this->createExisting([
				[
					'a' => 'vA1',
					'b' => 'groupA',
					'u' => 'toBeUpdatedA',
				],
				[
					'a' => 'vA2',
					'b' => 'groupA',
					'u' => 'toBeUnchangedA',
				],
				[
					'a' => 'vA3',
					'b' => 'groupA',
					'u' => 'missingA',
				],
				[
					'a' => 'vA1',
					'b' => 'groupB',
					'u' => 'toBeUpdatedB',
				],
				[
					'a' => 'vA2',
					'b' => 'groupB',
					'u' => 'toBeUnchangedB',
				],
				[
					'a' => 'vA3',
					'b' => 'groupB',
					'u' => 'missingB',
				],
			]);


			$createdCalled = 0;
			$created       = [];

			$modifiedCalled = 0;
			$modified       = [];

			$missingCalled = 0;
			$missing       = [];

			Carbon::setTestNow($now2);

			TestBulkImport::bulkImport()
				->updateFields(['a', 'b'])
				->modifiedWhen(['a', 'b'])
				->targetWhere('b', ['groupA', 'groupC'])
				->callbackFields(['a', 'b', 'u'])
				->onCreated(function ($records) use (&$created, &$createdCalled) {
					++$createdCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$created = array_merge($created, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray());
				})
				->onModified(function ($records) use (&$modified, &$modifiedCalled) {
					++$modifiedCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$modified = array_merge($modified, array_merge($modified, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->onMissing(function ($records) use (&$missing, &$missingCalled) {
					++$missingCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$missing = array_merge($missing, array_merge($missing, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->perform(function (FlushingBuffer $buffer) {

					$buffer->addMultiple([
						[
							'a' => 'vA1a',
							'b' => 'groupA',
							'u' => 'toBeUpdatedA',
						],
						[
							'a' => 'vA2',
							'b' => 'groupA',
							'u' => 'toBeUnchangedA',
						],
						[
							'a' => 'vA3a',
							'b' => 'groupA',
							'u' => 'toBeCreatedA',
						],
						// out of target where
						[
							'a' => 'vA1a',
							'b' => 'groupB',
							'u' => 'toBeUpdatedB',
						],
						[
							'a' => 'vA2',
							'b' => 'groupB',
							'u' => 'toBeUnchangedB',
						],
						[
							'a' => 'vA3a',
							'b' => 'groupB',
							'u' => 'toBeCreatedB',
						],
					]);

				});

			$this->assertSame(1, $createdCalled);
			$this->assertSame(1, $modifiedCalled);
			$this->assertSame(1, $missingCalled);

			$this->assertEquals([
				[
					'a' => 'vA3a',
					'b' => 'groupA',
					'u' => 'toBeCreatedA',
				],
			], $created);
			$this->assertEquals([
				[
					'a' => 'vA1a',
					'b' => 'groupA',
					'u' => 'toBeUpdatedA',
				]
			], $modified);
			$this->assertEquals([
				[
					'a' => 'vA3',
					'b' => 'groupA',
					'u' => 'missingA',
				]
			], $missing);

			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3a',
				'b'          => 'groupA',
				'u'          => 'toBeCreatedA',
				'created_at' => $now2,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1a',
				'b'          => 'groupA',
				'u'          => 'toBeUpdatedA',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'groupA',
				'u'          => 'toBeUnchangedA',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3',
				'b'          => 'groupA',
				'u'          => 'missingA',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);

			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3a',
				'b'          => 'groupB',
				'u'          => 'toBeCreatedB',
				'created_at' => $now2,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1a',
				'b'          => 'groupB',
				'u'          => 'toBeUpdatedB',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'groupB',
				'u'          => 'toBeUnchangedB',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3',
				'b'          => 'groupB',
				'u'          => 'missingB',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
		}


		public function testImport_onlyCreatedCallback() {

			$now1 = new Carbon('-1 minute');
			$now2 = new Carbon();


			Carbon::setTestNow($now1);
			$this->createExisting([
				[
					'a' => 'vA1',
					'b' => 'vB1',
					'u' => 'toBeUpdated',
				],
				[
					'a' => 'vA2',
					'b' => 'vB2',
					'u' => 'toBeUnchanged',
				],
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing',
				],
			]);


			$createdCalled = 0;
			$created       = [];

			Carbon::setTestNow($now2);

			TestBulkImport::bulkImport()
				->updateFields(['a', 'b'])
				->modifiedWhen(['a', 'b'])
				->callbackFields(['a', 'b', 'u'])
				->onCreated(function ($records) use (&$created, &$createdCalled) {
					++$createdCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$created = array_merge($created, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray());
				})
				->perform(function (FlushingBuffer $buffer) {

					$buffer->addMultiple([
						[
							'a' => 'vA1a',
							'b' => 'vB1',
							'u' => 'toBeUpdated',
						],
						[
							'a' => 'vA2',
							'b' => 'vB2',
							'u' => 'toBeUnchanged',
						],
						[
							'a' => 'vA3a',
							'b' => 'vB3',
							'u' => 'toBeCreated',
						],
					]);

				});

			$this->assertSame(1, $createdCalled);
			$this->assertEquals([
				[
					'a' => 'vA3a',
					'b' => 'vB3',
					'u' => 'toBeCreated',
				],
			], $created);

			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3a',
				'b'          => 'vB3',
				'u'          => 'toBeCreated',
				'created_at' => $now2,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1a',
				'b'          => 'vB1',
				'u'          => 'toBeUpdated',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'vB2',
				'u'          => 'toBeUnchanged',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3',
				'b'          => 'vB3',
				'u'          => 'missing',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
		}

		public function testImport_onlyModifiedCallback() {

			$now1 = new Carbon('-1 minute');
			$now2 = new Carbon();


			Carbon::setTestNow($now1);
			$this->createExisting([
				[
					'a' => 'vA1',
					'b' => 'vB1',
					'u' => 'toBeUpdated',
				],
				[
					'a' => 'vA2',
					'b' => 'vB2',
					'u' => 'toBeUnchanged',
				],
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing',
				],
			]);



			$modifiedCalled = 0;
			$modified       = [];

			Carbon::setTestNow($now2);

			TestBulkImport::bulkImport()
				->updateFields(['a', 'b'])
				->modifiedWhen(['a', 'b'])
				->callbackFields(['a', 'b', 'u'])
				->onModified(function ($records) use (&$modified, &$modifiedCalled) {
					++$modifiedCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$modified = array_merge($modified, array_merge($modified, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->perform(function (FlushingBuffer $buffer) {

					$buffer->addMultiple([
						[
							'a' => 'vA1a',
							'b' => 'vB1',
							'u' => 'toBeUpdated',
						],
						[
							'a' => 'vA2',
							'b' => 'vB2',
							'u' => 'toBeUnchanged',
						],
						[
							'a' => 'vA3a',
							'b' => 'vB3',
							'u' => 'toBeCreated',
						],
					]);

				});

			$this->assertSame(1, $modifiedCalled);

			$this->assertEquals([
				[
					'a' => 'vA1a',
					'b' => 'vB1',
					'u' => 'toBeUpdated',
				]
			], $modified);

			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3a',
				'b'          => 'vB3',
				'u'          => 'toBeCreated',
				'created_at' => $now2,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1a',
				'b'          => 'vB1',
				'u'          => 'toBeUpdated',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'vB2',
				'u'          => 'toBeUnchanged',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3',
				'b'          => 'vB3',
				'u'          => 'missing',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
		}

		public function testImport_onlyMissingCallback() {

			$now1 = new Carbon('-1 minute');
			$now2 = new Carbon();


			Carbon::setTestNow($now1);
			$this->createExisting([
				[
					'a' => 'vA1',
					'b' => 'vB1',
					'u' => 'toBeUpdated',
				],
				[
					'a' => 'vA2',
					'b' => 'vB2',
					'u' => 'toBeUnchanged',
				],
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing',
				],
			]);

			$missingCalled = 0;
			$missing       = [];

			Carbon::setTestNow($now2);

			TestBulkImport::bulkImport()
				->updateFields(['a', 'b'])
				->modifiedWhen(['a', 'b'])
				->callbackFields(['a', 'b', 'u'])
				->onMissing(function ($records) use (&$missing, &$missingCalled) {
					++$missingCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$missing = array_merge($missing, array_merge($missing, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->perform(function (FlushingBuffer $buffer) {

					$buffer->addMultiple([
						[
							'a' => 'vA1a',
							'b' => 'vB1',
							'u' => 'toBeUpdated',
						],
						[
							'a' => 'vA2',
							'b' => 'vB2',
							'u' => 'toBeUnchanged',
						],
						[
							'a' => 'vA3a',
							'b' => 'vB3',
							'u' => 'toBeCreated',
						],
					]);

				});

			$this->assertSame(1, $missingCalled);
			$this->assertEquals([
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing',
				]
			], $missing);

			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3a',
				'b'          => 'vB3',
				'u'          => 'toBeCreated',
				'created_at' => $now2,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1a',
				'b'          => 'vB1',
				'u'          => 'toBeUpdated',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'vB2',
				'u'          => 'toBeUnchanged',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3',
				'b'          => 'vB3',
				'u'          => 'missing',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
		}


		public function testImport_onePerModificationType_withoutCreatedMarkField() {

			$now1 = new Carbon('-1 minute');
			$now2 = new Carbon();


			Carbon::setTestNow($now1);
			$this->createExisting([
				[
					'a' => 'vA1',
					'b' => 'vB1',
					'u' => 'toBeUpdated',
				],
				[
					'a' => 'vA2',
					'b' => 'vB2',
					'u' => 'toBeUnchanged',
				],
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing',
				],
			]);

			$modifiedCalled = 0;
			$modified       = [];

			$missingCalled = 0;
			$missing       = [];

			Carbon::setTestNow($now2);

			TestBulkImportWithoutCreatedMark::bulkImport()
				->updateFields(['a', 'b'])
				->modifiedWhen(['a', 'b'])
				->callbackFields(['a', 'b', 'u'])
				->onModified(function ($records) use (&$modified, &$modifiedCalled) {
					++$modifiedCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$modified = array_merge($modified, array_merge($modified, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->onMissing(function ($records) use (&$missing, &$missingCalled) {
					++$missingCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$missing = array_merge($missing, array_merge($missing, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->perform(function (FlushingBuffer $buffer) {

					$buffer->addMultiple([
						[
							'a' => 'vA1a',
							'b' => 'vB1',
							'u' => 'toBeUpdated',
						],
						[
							'a' => 'vA2',
							'b' => 'vB2',
							'u' => 'toBeUnchanged',
						],
						[
							'a' => 'vA3a',
							'b' => 'vB3',
							'u' => 'toBeCreated',
						],
					]);

				});

			$this->assertSame(1, $modifiedCalled);
			$this->assertSame(1, $missingCalled);
			$this->assertEquals([
				[
					'a' => 'vA1a',
					'b' => 'vB1',
					'u' => 'toBeUpdated',
				]
			], $modified);
			$this->assertEquals([
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing',
				]
			], $missing);

			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3a',
				'b'          => 'vB3',
				'u'          => 'toBeCreated',
				'created_at' => $now2,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1a',
				'b'          => 'vB1',
				'u'          => 'toBeUpdated',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'vB2',
				'u'          => 'toBeUnchanged',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3',
				'b'          => 'vB3',
				'u'          => 'missing',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
		}

		public function testImport_onePerModificationType_withoutModifiedMarkField() {

			$now1 = new Carbon('-1 minute');
			$now2 = new Carbon();


			Carbon::setTestNow($now1);
			$this->createExisting([
				[
					'a' => 'vA1',
					'b' => 'vB1',
					'u' => 'toBeUpdated',
				],
				[
					'a' => 'vA2',
					'b' => 'vB2',
					'u' => 'toBeUnchanged',
				],
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing',
				],
			]);


			$createdCalled = 0;
			$created       = [];

			$missingCalled = 0;
			$missing       = [];

			Carbon::setTestNow($now2);

			TestBulkImportWithoutModifiedMark::bulkImport()
				->updateFields(['a', 'b'])
				->modifiedWhen(['a', 'b'])
				->callbackFields(['a', 'b', 'u'])
				->onCreated(function ($records) use (&$created, &$createdCalled) {
					++$createdCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$created = array_merge($created, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray());
				})
				->onMissing(function ($records) use (&$missing, &$missingCalled) {
					++$missingCalled;

					$this->assertInstanceOf(Collection::class, $records);
					$missing = array_merge($missing, array_merge($missing, $records->map(function ($v) {
						return [
							'a' => $v['a'],
							'b' => $v['b'],
							'u' => $v['u'],
						];
					})->toArray()));
				})
				->perform(function (FlushingBuffer $buffer) {

					$buffer->addMultiple([
						[
							'a' => 'vA1a',
							'b' => 'vB1',
							'u' => 'toBeUpdated',
						],
						[
							'a' => 'vA2',
							'b' => 'vB2',
							'u' => 'toBeUnchanged',
						],
						[
							'a' => 'vA3a',
							'b' => 'vB3',
							'u' => 'toBeCreated',
						],
					]);

				});

			$this->assertSame(1, $createdCalled);
			$this->assertSame(1, $missingCalled);
			$this->assertEquals([
				[
					'a' => 'vA3a',
					'b' => 'vB3',
					'u' => 'toBeCreated',
				],
			], $created);
			$this->assertEquals([
				[
					'a' => 'vA3',
					'b' => 'vB3',
					'u' => 'missing',
				]
			], $missing);

			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3a',
				'b'          => 'vB3',
				'u'          => 'toBeCreated',
				'created_at' => $now2,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA1a',
				'b'          => 'vB1',
				'u'          => 'toBeUpdated',
				'created_at' => $now1,
				'updated_at' => $now2,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA2',
				'b'          => 'vB2',
				'u'          => 'toBeUnchanged',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
			$this->assertDatabaseHas(TestBulkImport::table(), [
				'a'          => 'vA3',
				'b'          => 'vB3',
				'u'          => 'missing',
				'created_at' => $now1,
				'updated_at' => $now1,
			]);
		}

		// TODO: test buffer sizes

		// TODO: test without lock

		// TODO: test without transaction

		// TODO: test update fields
	}