<?php
	/** @noinspection SqlDialectInspection */
	/** @noinspection SqlNoDataSourceInspection */

	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 28.11.18
	 * Time: 14:11
	 */

	namespace ItsMiegerLaraDbExtTest\Cases\Unit\Query;


	use Illuminate\Database\Events\StatementPrepared;
	use Illuminate\Database\Query\Expression;
	use Illuminate\Events\Dispatcher;
	use Illuminate\Foundation\Testing\DatabaseTransactions;
	use Illuminate\Support\Facades\DB;
	use ItsMieger\LaraDbExt\Connection\Forkable;
	use ItsMieger\LaraDbExt\Connection\MySqlConnection;
	use ItsMiegerLaraDbExtTest\Model\TestModelQueryBuilder;
	use PHPUnit\Framework\SkippedTestError;
	use stdClass;
	use Mockery as m;
	use InvalidArgumentException;
	use ItsMieger\LaraDbExt\Query\Builder;
	use Illuminate\Database\ConnectionInterface;
	use Illuminate\Database\Query\Grammars\Grammar;
	use Illuminate\Pagination\LengthAwarePaginator;
	use Illuminate\Database\Query\Expression as Raw;
	use Illuminate\Database\Query\Processors\Processor;
	use Illuminate\Database\Query\Grammars\MySqlGrammar;
	use Illuminate\Database\Query\Grammars\SQLiteGrammar;
	use Illuminate\Database\Query\Grammars\PostgresGrammar;
	use Illuminate\Database\Query\Grammars\SqlServerGrammar;
	use Illuminate\Database\Query\Processors\MySqlProcessor;
	use Illuminate\Pagination\AbstractPaginator as Paginator;
	use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

	use ItsMiegerLaraDbExtTest\Unit\TestCase;

	class BuilderTest extends TestCase
	{
		use DatabaseTransactions;

		public function testWhere_valuesArray_2args() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('name', [
					'name A',
					'name B'
				]);
			$this->assertEquals('select * from "test_table" where "name" in (?, ?)', $builder->toSql());
			$this->assertSame(['name A', 'name B'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testWhere_valuesArray_multipleColumns_2args() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where(['name', 'x'], [
					['name A', '2'],
					['name B', '4'],
				]);
			$this->assertEquals('select * from "test_table" where ("name", "x") in ((?, ?), (?, ?))', $builder->toSql());
			$this->assertSame(['name A', '2', 'name B', '4'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testWhere_valuesArray_3args() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('name', '=', [
					'name A',
					'name B'
				]);
			$this->assertEquals('select * from "test_table" where "name" in (?, ?)', $builder->toSql());
			$this->assertSame(['name A', 'name B'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testWhere_valuesArray_multipleColumns_3args() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where(['name', 'x'], '=', [
					['name A', '2'],
					['name B', '4'],
				]);
			$this->assertEquals('select * from "test_table" where ("name", "x") in ((?, ?), (?, ?))', $builder->toSql());
			$this->assertSame(['name A', '2', 'name B', '4'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testWhere_valuesArray_notEqual() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('name', '!=', [
					'name A',
					'name B'
				]);
			$this->assertEquals('select * from "test_table" where "name" not in (?, ?)', $builder->toSql());
			$this->assertSame(['name A', 'name B'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testWhere_valuesArray_multipleColumns_notEqual() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where(['name', 'x'], '!=', [
					['name A', '2'],
					['name B', '4'],
				]);
			$this->assertEquals('select * from "test_table" where ("name", "x") not in ((?, ?), (?, ?))', $builder->toSql());
			$this->assertSame(['name A', '2', 'name B', '4'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testWhere_valuesArray_greaterLower() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('name', '<>', [
					'name A',
					'name B'
				]);
			$this->assertEquals('select * from "test_table" where "name" not in (?, ?)', $builder->toSql());
			$this->assertSame(['name A', 'name B'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testWhere_valuesArray_multipleColumns_greaterLower() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where(['name', 'x'], '<>', [
					['name A', '2'],
					['name B', '4'],
				]);
			$this->assertEquals('select * from "test_table" where ("name", "x") not in ((?, ?), (?, ?))', $builder->toSql());
			$this->assertSame(['name A', '2', 'name B', '4'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testWhereIn_multipleColumns() {

			$builder = $this->getBuilder();
			$self = $builder
				->select('*')
				->from('test_table')
				->whereIn(['name', 'x'], [
					['name A', '2'],
					['name B', '4'],
				]);
			$this->assertEquals('select * from "test_table" where ("name", "x") in ((?, ?), (?, ?))', $builder->toSql());
			$this->assertSame(['name A', '2', 'name B', '4'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}


		public function testWhereIn_multipleColumns_subSelect() {

			$builder = $this->getBuilder();
			$self = $builder
				->select('*')
				->from('test_table')
				->whereIn(['name', 'x'], function ($query) {
					return $query->select(['name', 'x'])
						->from('test_table')
						->where('name', 'name A');
				});
			$this->assertEquals('select * from "test_table" where ("name", "x") in (select "name", "x" from "test_table" where "name" = ?)', $builder->toSql());
			$this->assertSame(['name A'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testWhereMultiIn() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('x', 5)
				->whereMultiIn(['name', 'x'], [
					['name A', '2'],
					['name B', '4'],
				]);
			$this->assertEquals('select * from "test_table" where "x" = ? and ("name", "x") in ((?, ?), (?, ?))', $builder->toSql());
			$this->assertSame([5, 'name A', '2', 'name B', '4'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testOrWhereMultiIn() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('x', 5)
				->orWhereMultiIn(['name', 'x'], [
					['name A', '2'],
					['name B', '4'],
				]);
			$this->assertEquals('select * from "test_table" where "x" = ? or ("name", "x") in ((?, ?), (?, ?))', $builder->toSql());
			$this->assertSame([5, 'name A', '2', 'name B', '4'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testWhereMultiNotIn() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('x', 5)
				->whereMultiNotIn(['name', 'x'], [
					['name A', '2'],
					['name B', '4'],
				]);
			$this->assertEquals('select * from "test_table" where "x" = ? and ("name", "x") not in ((?, ?), (?, ?))', $builder->toSql());
			$this->assertSame([5, 'name A', '2', 'name B', '4'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testOrOrWhereMultiNotIn() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('x', 5)
				->orWhereMultiNotIn(['name', 'x'], [
					['name A', '2'],
					['name B', '4'],
				]);
			$this->assertEquals('select * from "test_table" where "x" = ? or ("name", "x") not in ((?, ?), (?, ?))', $builder->toSql());
			$this->assertSame([5, 'name A', '2', 'name B', '4'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testWhereMultiColumns_2Args() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('x', 5)
				->whereMultiColumns(['name', 'x'], ['c1', 'c2']);
			$this->assertEquals('select * from "test_table" where "x" = ? and ("name" = "c1" and "x" = "c2")', $builder->toSql());
			$this->assertSame($builder, $self);
		}

		public function testWhereMultiColumns_3Args() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('x', 5)
				->whereMultiColumns(['name', 'x'], '=', ['c1', 'c2']);
			$this->assertEquals('select * from "test_table" where "x" = ? and ("name" = "c1" and "x" = "c2")', $builder->toSql());
			$this->assertSame($builder, $self);

		}

		public function testWhereMultiColumns_Not() {
			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('x', 5)
				->whereMultiColumns(['name', 'x'], '!=', ['c1', 'c2']);
			$this->assertEquals('select * from "test_table" where "x" = ? and not ("name" = "c1" and "x" = "c2")', $builder->toSql());
			$this->assertSame($builder, $self);
		}

		public function testOrWhereMultiColumns_2Args() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('x', 5)
				->orWhereMultiColumns(['name', 'x'], ['c1', 'c2']);
			$this->assertEquals('select * from "test_table" where "x" = ? or ("name" = "c1" and "x" = "c2")', $builder->toSql());
			$this->assertSame($builder, $self);
		}

		public function testOrWhereMultiColumns_3Args() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('x', 5)
				->orWhereMultiColumns(['name', 'x'], '=', ['c1', 'c2']);
			$this->assertEquals('select * from "test_table" where "x" = ? or ("name" = "c1" and "x" = "c2")', $builder->toSql());
			$this->assertSame($builder, $self);

		}

		public function testOrWhereMultiColumns_Not() {
			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('x', 5)
				->orWhereMultiColumns(['name', 'x'], '!=', ['c1', 'c2']);
			$this->assertEquals('select * from "test_table" where "x" = ? or not ("name" = "c1" and "x" = "c2")', $builder->toSql());
			$this->assertSame($builder, $self);
		}

		public function testWhereColumn_multipleColumns_2Args() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->whereColumn(['name', 'x'], ['c1', 'c2']);
			$this->assertEquals('select * from "test_table" where ("name" = "c1" and "x" = "c2")', $builder->toSql());
			$this->assertSame($builder, $self);
		}

		public function testWhereColumn_multipleColumns_3Args() {

			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->whereColumn(['name', 'x'], '=', ['c1', 'c2']);
			$this->assertEquals('select * from "test_table" where ("name" = "c1" and "x" = "c2")', $builder->toSql());
			$this->assertSame($builder, $self);

		}

		public function testWhereColumn_multipleColumns_Not() {
			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->whereColumn(['name', 'x'], '!=', ['c1', 'c2']);
			$this->assertEquals('select * from "test_table" where not ("name" = "c1" and "x" = "c2")', $builder->toSql());
			$this->assertSame($builder, $self);
		}

		public function testWhereNotNested() {
			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->whereNotNested(function($query) {
					return $query->where('name', 'hans');
				});
			$this->assertEquals('select * from "test_table" where not ("name" = ?)', $builder->toSql());
			$this->assertSame(['hans'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testWhereNotNestedAnd() {
			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('name', '!=', 'peter')
				->whereNotNested(function($query) {
					return $query->where('name', 'hans');
				});
			$this->assertEquals('select * from "test_table" where "name" != ? and not ("name" = ?)', $builder->toSql());
			$this->assertSame(['peter', 'hans'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testOrWhereNotNestedOr() {
			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('name', 'peter')
				->whereNotNested(function($query) {
					return $query->where('name', 'hans');
				}, 'or');
			$this->assertEquals('select * from "test_table" where "name" = ? or not ("name" = ?)', $builder->toSql());
			$this->assertSame(['peter', 'hans'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testOrWhereNotNested() {
			$builder = $this->getBuilder();
			$self    = $builder
				->select('*')
				->from('test_table')
				->where('name', 'peter')
				->orWhereNotNested(function($query) {
					return $query->where('name', 'hans');
				});
			$this->assertEquals('select * from "test_table" where "name" = ? or not ("name" = ?)', $builder->toSql());
			$this->assertSame(['peter', 'hans'], $builder->getBindings());
			$this->assertSame($builder, $self);
		}

		public function testSelectEmpty() {

			$builder = new Builder(DB::connection());
			$result  = $builder
				->selectPrefixed('test_query_table.*', 'myPfx__')
				->from('test_query_table')
				->get();

			$this->assertEquals([], $result->toArray());
		}

		public function testSelectPrefixed() {
			$m1 = factory(TestModelQueryBuilder::class)->create();

			$builder = new Builder(DB::connection());
			$result  = $builder
				->selectPrefixed('test_query_table.*', 'myPfx__')
				->from('test_query_table')
				->get()
			;

			$this->assertEquals([
				'myPfx__id'         => $m1->id,
				'myPfx__name'       => $m1->name,
				'myPfx__x'          => $m1->x,
				'myPfx__dt'         => $m1->dt,
				'myPfx__created_at' => $m1->created_at,
				'myPfx__updated_at' => $m1->updated_at,
			], $result->first());
		}

		public function testSelectPrefixed_multipleColumns() {
			$m1 = factory(TestModelQueryBuilder::class)->create();

			$builder = new Builder(DB::connection());
			$result  = $builder
				->selectPrefixed(['id', 'name'], 'myPfx__')
				->from('test_query_table')
				->get()
			;

			$this->assertEquals([
				'myPfx__id'         => $m1->id,
				'myPfx__name'       => $m1->name,
			], $result->first());
		}


		public function testSelectPrefixed_alias() {
			$m1 = factory(TestModelQueryBuilder::class)->create();

			$builder = new Builder(DB::connection());
			$result  = $builder
				->selectPrefixed(['id as myID', 'name'], 'myPfx__')
				->from('test_query_table')
				->get()
			;

			$this->assertEquals([
				'myPfx__myID'       => $m1->id,
				'myPfx__name'       => $m1->name,
			], $result->first());
		}


		public function testSelectPrefixed_expression_withAlias() {
			$m1 = factory(TestModelQueryBuilder::class)->create();

			$builder = new Builder(DB::connection());
			$result  = $builder
				->selectPrefixed(['id', new Expression('lower(name) as lName')], 'myPfx__')
				->from('test_query_table')
				->get()
			;

			$this->assertEquals([
				'myPfx__id'       => $m1->id,
				'myPfx__lName'    => strtolower($m1->name),
			], $result->first());
		}

		public function testSelectPrefixed_multiplePrefixes() {
			$m1 = factory(TestModelQueryBuilder::class)->create();

			$builder = new Builder(DB::connection());
			$result  = $builder
				->selectPrefixed([
					'pfx1_' => 'id',
					'pfx2_' => ['name', 'x'],
					'pfx3_' => 'x as myX',
				])
				->from('test_query_table')
				->get();

			$this->assertEquals([
				'pfx1_id'    => $m1->id,
				'pfx2_name' => $m1->name,
				'pfx2_x'    => $m1->x,
				'pfx3_myX'  => $m1->x,
			], $result->first());
		}

		public function testSelectPrefixed_specialChars() {
			$m1 = factory(TestModelQueryBuilder::class)->create();

			$builder = new Builder(DB::connection());
			$result  = $builder
				->selectPrefixed(['id'], 'my.')
				->addSelectPrefixed(['id'], 'my:')
				->addSelectPrefixed(['id'], 'my.:')
				->addSelectPrefixed(['id'], 'my\\')
				->from('test_query_table')
				->get();

			$this->assertEquals([
				'my.id'   => $m1->id,
				'my:id'   => $m1->id,
				'my.:id'   => $m1->id,
				'my\\id'   => $m1->id,
			], $result->first());
		}

		public function testAddSelectPrefixed() {
			$m1 = factory(TestModelQueryBuilder::class)->create();

			$builder = new Builder(DB::connection());
			$result  = $builder
				->select('*')
				->addSelectPrefixed('id', 'myPfx__')
				->from('test_query_table')
				->get()
			;

			$this->assertEquals([
				'id'         => $m1->id,
				'name'       => $m1->name,
				'x'          => $m1->x,
				'dt'         => $m1->dt,
				'created_at' => $m1->created_at,
				'updated_at' => $m1->updated_at,
				'myPfx__id'  => $m1->id,
			], $result->first());
		}

		public function testAddSelectPrefixed_multipleColumns() {
			$m1 = factory(TestModelQueryBuilder::class)->create();

			$builder = new Builder(DB::connection());
			$result  = $builder
				->select('*')
				->addSelectPrefixed(['id', 'name'], 'myPfx__')
				->from('test_query_table')
				->get()
			;

			$this->assertEquals([
				'id'          => $m1->id,
				'name'        => $m1->name,
				'x'           => $m1->x,
				'dt'          => $m1->dt,
				'created_at'  => $m1->created_at,
				'updated_at'  => $m1->updated_at,
				'myPfx__id'   => $m1->id,
				'myPfx__name' => $m1->name,
			], $result->first());
		}

		public function testAddSelectPrefixed_alias() {
			$m1 = factory(TestModelQueryBuilder::class)->create();

			$builder = new Builder(DB::connection());
			$result  = $builder
				->select('*')
				->addSelectPrefixed(['id as myID', 'name'], 'myPfx__')
				->from('test_query_table')
				->get()
			;

			$this->assertEquals([
				'id'          => $m1->id,
				'name'        => $m1->name,
				'x'           => $m1->x,
				'dt'          => $m1->dt,
				'created_at'  => $m1->created_at,
				'updated_at'  => $m1->updated_at,
				'myPfx__myID' => $m1->id,
				'myPfx__name' => $m1->name,
			], $result->first());
		}

		public function testAddSelectPrefixed_expression_withAlias() {
			$m1 = factory(TestModelQueryBuilder::class)->create();

			$builder = new Builder(DB::connection());
			$result  = $builder
				->select('*')
				->addSelectPrefixed(['id', new Expression('lower(name) as lName')], 'myPfx__')
				->from('test_query_table')
				->get()
			;

			$this->assertEquals([
				'id'           => $m1->id,
				'name'         => $m1->name,
				'x'            => $m1->x,
				'dt'           => $m1->dt,
				'created_at'   => $m1->created_at,
				'updated_at'   => $m1->updated_at,
				'myPfx__id'    => $m1->id,
				'myPfx__lName' => strtolower($m1->name),
			], $result->first());
		}

		public function testAddSelectPrefixed_multiplePrefixes() {
			$m1 = factory(TestModelQueryBuilder::class)->create();

			$builder = new Builder(DB::connection());
			$result  = $builder
				->select('*')
				->addSelectPrefixed([
					'pfx1_' => 'id',
					'pfx2_' => ['name', 'x'],
					'pfx3_' => 'x as myX',
				])
				->from('test_query_table')
				->get();

			$this->assertEquals([
				'id'         => $m1->id,
				'name'       => $m1->name,
				'x'          => $m1->x,
				'dt'         => $m1->dt,
				'created_at' => $m1->created_at,
				'updated_at' => $m1->updated_at,
				'pfx1_id'    => $m1->id,
				'pfx2_name'  => $m1->name,
				'pfx2_x'     => $m1->x,
				'pfx3_myX'   => $m1->x,
			], $result->first());
		}

		public function testForkedConnection() {

			$builder = new Builder(DB::connection());

			$connectionBefore = $builder->getConnection();

			$forked = $builder->forkedConnection();
			$this->assertSame($builder, $forked);
			$this->assertNotSame($connectionBefore, $forked->getConnection());
			$this->assertNotSame($connectionBefore->getPdo(), $forked->getConnection()->getPdo());
		}

		public function testWithForkedConnection() {

			$builder = new Builder(DB::connection());

			$connectionBefore = $builder->getConnection();

			$f = null;
			$ret = $builder->withForkedConnection(function($forked) use ($builder, $connectionBefore, &$f) {
				$this->assertNotSame($builder, $forked);
				$this->assertNotSame($connectionBefore, $forked->getConnection());
				$this->assertNotSame($connectionBefore->getPdo(), $forked->getConnection()->getPdo());

				$f = $forked;

				return 'x';
			});

			$this->assertEquals('x', $ret);
			$this->assertNull($f->getConnection()->getPdo());
		}


		public function testForkUnbuffered() {

			$builder = new Builder(DB::connection());

			$connection = $builder->getConnection();
			if (!($connection instanceof MySqlConnection))
				throw new SkippedTestError('This test requires a MySQL connection');

			$connectionBefore = $builder->getConnection();

			$forked = $builder->forkUnbuffered();
			$this->assertSame($builder, $forked);
			$this->assertNotSame($connectionBefore, $forked->getConnection());
			$this->assertNotSame($connectionBefore->getPdo(), $forked->getConnection()->getPdo());
			$this->assertEquals(true, $connectionBefore->getPdo()->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY));
			$this->assertEquals(false, $forked->getConnection()->getPdo()->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY));
		}

		public function testWithUnbufferedFork() {

			$builder = new Builder(DB::connection());


			$connection = $builder->getConnection();
			if (!($connection instanceof MySqlConnection))
				throw new SkippedTestError('This test requires a MySQL connection');

			$connectionBefore = $builder->getConnection();

			$f   = null;
			$ret = $builder->withUnbufferedFork(function ($forked) use ($builder, $connectionBefore, &$f) {
				$this->assertNotSame($builder, $forked);
				$this->assertNotSame($connectionBefore, $forked->getConnection());
				$this->assertNotSame($connectionBefore->getPdo(), $forked->getConnection()->getPdo());
				$this->assertEquals(true, $connectionBefore->getPdo()->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY));
				$this->assertEquals(false, $forked->getConnection()->getPdo()->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY));

				$f = $forked;

				return 'x';
			});

			$this->assertEquals(true, $builder->getConnection()->getPdo()->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY));
			$this->assertEquals('x', $ret);
			$this->assertNull($f->getConnection()->getPdo());
		}

		protected function getBuilder() {
			$grammar   = new Grammar;
			$processor = m::mock(Processor::class);

			return new Builder(m::mock(ConnectionInterface::class), $grammar, $processor);
		}
	}