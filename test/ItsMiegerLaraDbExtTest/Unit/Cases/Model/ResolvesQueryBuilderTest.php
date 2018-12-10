<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 12:08
	 */

	namespace ItsMiegerLaraDbExtTest\Unit\Cases\Model;


	use Illuminate\Database\Eloquent\Model;
	use ItsMiegerLaraDbExtTest\Unit\TestCase;
	use ItsMieger\LaraDbExt\Eloquent\Builder as EloquentBuilder;
	use ItsMieger\LaraDbExt\Query\Builder as QueryBuilder;
	use ItsMieger\LaraDbExt\Provider\RegistersEloquentBuilder;
	use ItsMieger\LaraDbExt\Provider\RegistersQueryBuilder;


	class ResolvesQueryBuilderTest extends TestCase
	{
		use RegistersEloquentBuilder;
		use RegistersQueryBuilder;

		/**
		 * @before
		 */
		public function before() {
			$this->refreshApplication();
		}

		public function testDefaultsResolved() {

			$model = new TestModelResolvesQueryBuilder();

			$this->assertEquals(EloquentBuilder::class, get_class($model->newQuery()));
			$this->assertEquals(QueryBuilder::class, get_class($model->newQuery()->getQuery()));

		}

		public function testCustomQueryBuilderResolved() {

			$queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();

			$this->registerQueryBuilder(function ($connection, $grammar, $postProcessor) use ($queryBuilderMock) {
				$this->assertSame((new TestModelResolvesQueryBuilder())->getConnection(), $connection);
				$this->assertSame((new TestModelResolvesQueryBuilder())->getConnection()->getQueryGrammar(), $grammar);
				$this->assertSame((new TestModelResolvesQueryBuilder())->getConnection()->getPostProcessor(), $postProcessor);

				return $queryBuilderMock;
			});

			$model = new TestModelResolvesQueryBuilder();

			$this->assertEquals(get_class($queryBuilderMock), get_class($model->newQuery()->getQuery()));

		}

		public function testCustomEloquentBuilderResolved() {

			$eloquentBuilderMock = null;


			$this->registerEloquentBuilder(function ($query) use (&$eloquentBuilderMock) {
				$this->assertSame(QueryBuilder::class, get_class($query));

				$eloquentBuilderMock = $this->getMockBuilder(EloquentBuilder::class)
					->disableOriginalConstructor()
					->getMock();

				// we need to maintain the fluent interface
				$eloquentBuilderMock->method('setModel')->willReturnSelf();
				$eloquentBuilderMock->method('with')->willReturnSelf();
				$eloquentBuilderMock->method('withCount')->willReturnSelf();

				return $eloquentBuilderMock;
			});

			$model = new TestModelResolvesQueryBuilder();
			$query = $model->newQuery();

			$this->assertSame(get_class($eloquentBuilderMock), get_class($query));
			$this->assertSame($eloquentBuilderMock, $query);

		}
	}

	use ItsMieger\LaraDbExt\Model\ResolvesBuilders;

	class TestModelResolvesQueryBuilder extends Model
	{
		use ResolvesBuilders;

		protected $table = 'test_table';
	}