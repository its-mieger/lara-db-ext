<?php


	namespace ItsMiegerLaraDbExtTest\Model;


	class TestBulkImportWithoutModifiedMark extends BaseTestModel
	{

		protected $table = 'bulk_import_tests';

		// disable modified mark
		protected $batchModifiedMarkField = false;

	}