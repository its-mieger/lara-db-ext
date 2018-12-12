<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.12.18
	 * Time: 07:23
	 */

	namespace ItsMiegerLaraDbExtTest\Model;


	class TestModelDummyConnection extends BaseTestModel
	{
		protected $connection = 'dummyMocked';

		protected $table = 'test_table';

	}