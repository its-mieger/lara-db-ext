<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 06.12.18
	 * Time: 19:24
	 */

	namespace ItsMiegerLaraDbExtTest\Model;


	class TestModelEloquentBuilderHasOneRoot extends BaseTestModel
	{
		protected $table = 'test_eloquent_has_one_root_table';

		public function child() {
			return $this->hasOne(TestModelEloquentBuilderHasOneChild::class, 'root_id');
		}
	}