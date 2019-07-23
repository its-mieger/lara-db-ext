<?php


	namespace ItsMieger\LaraDbExt\Model;


	use Illuminate\Database\Eloquent\Model;
	use ItsMieger\LaraDbExt\Model\Operations\BulkImport as BulkImportOperation;

	trait BulkImport
	{
		/**
		 * Creates a new bulk import
		 * @return \ItsMieger\LaraDbExt\Model\Operations\BulkImport
		 */
		public static function bulkImport() {

			/** @var Model $model */
			$model = new static();

			return (new BulkImportOperation($model, static::nextBatchId(), $model->batchIdField ?? 'last_batch_id', $model->batchCreatedMarkField ?? 'last_batch_created', $model->batchModifiedMarkField ?? 'last_batch_modified'));
		}

		/**
		 * Returns the next batch id for bulk imports
		 * @return int The batch id
		 */
		public static abstract function nextBatchId();

		/**
		 * Insert using ON DUPLICATE KEY UPDATE
		 * @param array $data The data to insert
		 * @param array $updateColumns The columns to update. For numeric keys the value is interpreted as column name and the column is updated with the corresponding field from the dat array. For associative keys, the key is used as column name and the value can be a constant value or an expression which is used to update the column.
		 * @return int The number of affected records
		 */
		public abstract static function insertOnDuplicateKey(array $data, array $updateColumns = []);
	}