<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.12.18
	 * Time: 09:09
	 */

	namespace ItsMiegerLaraDbExtTest\Model;


	class TestModelDummyConnectionPrefixed extends BaseTestModel
	{
		protected $connection = 'dummyMockedPrefixed';

		protected $table = 'test_table';
	}