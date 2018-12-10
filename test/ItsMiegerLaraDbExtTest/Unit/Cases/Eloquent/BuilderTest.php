<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 29.11.18
	 * Time: 12:17
	 */

	namespace ItsMiegerLaraDbExtTest\Unit\Cases\Eloquent;


	use Illuminate\Foundation\Testing\DatabaseTransactions;

	use ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilder;
	use ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilderBelongs;
	use ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilderBelongsBelongs;
	use ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilderHasManyChild;
	use ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilderHasManyRoot;
	use ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilderHasOneChild;
	use ItsMiegerLaraDbExtTest\Model\TestModelEloquentBuilderHasOneRoot;
	use ItsMiegerLaraDbExtTest\Unit\TestCase;

	class BuilderTest extends TestCase
	{
		use DatabaseTransactions;

		public function testBelongsTo() {
			$parent = factory(TestModelEloquentBuilderBelongsBelongs::class)->create();


			$ret = TestModelEloquentBuilderBelongsBelongs
				::withJoined('test')
				->withJoined('test.test')
				->get();

			$returnedParent = $ret[0];

			$this->assertEquals($parent->getAttributes(), $returnedParent->getAttributes());
			$this->assertInstanceOf(TestModelEloquentBuilderBelongs::class, $returnedParent->test);
			$this->assertEquals($parent->test->getAttributes(), $returnedParent->test->getAttributes());
			$this->assertInstanceOf(TestModelEloquentBuilder::class, $returnedParent->test->test);
			$this->assertEquals($parent->test->test->getAttributes(), $returnedParent->test->test->getAttributes());
		}


		public function testHasMany() {
			$root = factory(TestModelEloquentBuilderHasManyRoot::class)->create();

			$child1 = factory(TestModelEloquentBuilderHasManyChild::class)->create([
				'root_id' => $root->id,
			]);
			$child2 = factory(TestModelEloquentBuilderHasManyChild::class)->create([
				'root_id' => $root->id,
			]);

			$ret = TestModelEloquentBuilderHasManyRoot
				::withJoined('children')
				->orderByParent()
				->orderByRelated('children', 'id')
				->get();

			$returnedParent = $ret[0];
			$this->assertEquals($root->getAttributes(), $returnedParent->getAttributes());
			$this->assertInstanceOf(TestModelEloquentBuilderHasManyChild::class, $returnedParent->children->get(0));
			$this->assertInstanceOf(TestModelEloquentBuilderHasManyChild::class, $returnedParent->children->get(1));
			$this->assertEquals($child1->getAttributes(), $returnedParent->children->get(0)->getAttributes());
			$this->assertEquals($child2->getAttributes(), $returnedParent->children->get(1)->getAttributes());
		}

		public function testHasOne() {
			$root = factory(TestModelEloquentBuilderHasOneRoot::class)->create();

			$child1 = factory(TestModelEloquentBuilderHasOneChild::class)->create([
				'root_id' => $root->id,
			]);

			$ret = TestModelEloquentBuilderHasOneRoot
				::withJoined('child')
				->orderByParent()
				->get();

			$returnedParent = $ret[0];
			$this->assertEquals($root->getAttributes(), $returnedParent->getAttributes());
			$this->assertInstanceOf(TestModelEloquentBuilderHasOneChild::class, $returnedParent->child);
			$this->assertEquals($child1->getAttributes(), $returnedParent->child->getAttributes());

		}

	}
