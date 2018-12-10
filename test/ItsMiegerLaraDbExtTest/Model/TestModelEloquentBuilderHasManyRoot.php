<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 06.12.18
	 * Time: 19:24
	 */

	namespace ItsMiegerLaraDbExtTest\Model;


	class TestModelEloquentBuilderHasManyRoot extends BaseTestModel
	{
		protected $table = 'test_eloquent_has_many_root_table';

		public function children() {
			return $this->hasMany(TestModelEloquentBuilderHasManyChild::class, 'root_id');
		}
	}