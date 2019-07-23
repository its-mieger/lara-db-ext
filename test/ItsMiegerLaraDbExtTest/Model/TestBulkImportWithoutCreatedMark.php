<?php


	namespace ItsMiegerLaraDbExtTest\Model;


	class TestBulkImportWithoutCreatedMark extends BaseTestModel
	{

		protected $table = 'bulk_import_tests';

		// disable created mark
		protected $batchCreatedMarkField = false;

	}