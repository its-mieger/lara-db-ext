<?php


	namespace ItsMieger\LaraDbExt\Model\Operations;


	use Carbon\Carbon;
	use Illuminate\Database\Eloquent\Collection;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Query\Expression;
	use InvalidArgumentException;
	use ItsMieger\LaraDbExt\Query\Builder;
	use MehrIt\Buffer\FlushingBuffer;
	use RuntimeException;
	use stdClass;

	/**
	 * Performs a bulk import with data change tracking using insertOnDuplicateKey
	 * @package App\Util\Database
	 */
	class BulkImport
	{

		/**
		 * @var Model
		 */
		protected $model;

		/**
		 * @var array
		 */
		protected $updateFields = [];


		/**
		 * @var array
		 */
		protected $modifiedWhen = [];

		/**
		 * @var string|null
		 */
		protected $modifiedMarkField;

		/**
		 * @var string|null
		 */
		protected $createdMarkField;

		/**
		 * @var string
		 */
		protected $batchIdField;


		/**
		 * @var array
		 */
		protected $targetConditionsArgs = [];

		/**
		 * @var int
		 */
		protected $batchId;

		/**
		 * @var int
		 */
		protected $bufferSize = 500;

		/**
		 * @var int
		 */
		protected $callbackBufferSize = 100;

		/**
		 * @var bool
		 */
		protected $lock = true;

		/**
		 * @var callable
		 */
		protected $createdCallback;

		/**
		 * @var callable
		 */
		protected $modifiedCallback;

		/**
		 * @var callable
		 */
		protected $missingCallback;

		/**
		 * @var string
		 */
		protected $callbackFields = [];

		/**
		 * @var string|null
		 */
		protected $createdAtField;

		/**
		 * @var string|null
		 */
		protected $updatedAtField;

		/**
		 * Creates a new instance
		 * @param Model $model The model
		 * @param int $batchId The next unique batch id to use for this import
		 * @param string $batchIdField The batch id field
		 * @param string|null $createdMarkField The created mark field
		 * @param string|null $modifiedMarkField The modified mark field
		 */
		public function __construct(Model $model, $batchId, string $batchIdField = 'last_batch_id', ?string $createdMarkField = 'last_batch_created', ?string $modifiedMarkField = 'last_batch_modified') {
			$this->model             = $model;
			$this->batchId           = $batchId;
			$this->batchIdField      = $batchIdField;
			$this->createdMarkField  = $createdMarkField;
			$this->modifiedMarkField = $modifiedMarkField;

			if ($model->usesTimestamps()) {
				$this->createdAtField = $model->getCreatedAtColumn();
				$this->updatedAtField = $model->getUpdatedAtColumn();
			}
		}

		/**
		 * Sets the buffer size. Default is 500.
		 * @param int $bufferSize The buffer size
		 * @return $this
		 */
		public function buffer(int $bufferSize): BulkImport {

			if ($bufferSize < 1)
				throw new InvalidArgumentException('Invalid buffer size');

			$this->bufferSize = $bufferSize;

			return $this;
		}

		/**
		 * Sets the buffer size for callbacks. Default is 100.
		 * @param int $bufferSize The buffer size
		 * @return $this
		 */
		public function bufferCallbacks(int $bufferSize) {
			if ($bufferSize < 1)
				throw new InvalidArgumentException('Invalid buffer size');

			$this->callbackBufferSize = $bufferSize;

			return $this;
		}

		/**
		 * Disables locking of records. Only call this if all records in scope are already locked.
		 * @return $this
		 */
		public function withoutLock() {
			$this->lock = false;

			return $this;
		}

		/**
		 * Adds a condition for the targeted records. Same arguments as query builder where
		 * @param string|array|\Closure $column
		 * @param string|null $operator
		 * @param mixed|null $value
		 * @param string $boolean
		 * @return $this
		 */
		public function targetWhere($column, $operator = null, $value = null, $boolean = 'and') {
			$this->targetConditionsArgs[] = func_get_args();

			return $this;
		}


		/**
		 * Specifies the fields to update on duplicate key
		 * @param string[]|Expression[]|mixed $fields The fields to update. For numeric keys the value is interpreted as column name and the column is updated with the corresponding field from the data array. For associative keys, the key is used as column name and the value can be a constant value or an expression which is used to update the column.
		 * @return $this
		 */
		public function updateFields(array $fields) {
			$this->updateFields = $fields;

			return $this;
		}

		/**
		 * Specifies the fields to return to callbacks
		 * @param string[]|Expression[] $fields The fields
		 * @return $this
		 */
		public function callbackFields(array $fields) {
			$this->callbackFields = $fields;

			return $this;
		}

		/**
		 * Sets the fields of which at least one must differ from existing record to mark the record as modified.
		 * @param string[]|Expression[] $fields The fields for boolean expressions
		 * @return $this
		 */
		public function modifiedWhen(array $fields) {
			$this->modifiedWhen = $fields;

			return $this;
		}

		/**
		 * Sets the callback to be called for newly created records
		 * @param callable $callback The callback. Receives a chunk of records. Might be called multiple times.
		 * @return $this
		 */
		public function onCreated(callable $callback) {

			if (!$this->createdMarkField)
				throw new RuntimeException('Tracking created records is not supported for model ' . get_class($this->model));

			$this->createdCallback = $callback;

			return $this;
		}

		/**
		 * Sets the callback to be called for modified records
		 * @param callable $callback The callback. Receives a chunk of records. Might be called multiple times.
		 * @return $this
		 */
		public function onModified(callable $callback) {

			if (!$this->modifiedMarkField)
				throw new RuntimeException('Tracking modified records is not supported for model ' . get_class($this->model));

			$this->modifiedCallback = $callback;

			return $this;
		}

		/**
		 * Sets the callback to be called for records existing (matching target conditions if any) but not existing in current import
		 * @param callable $callback The callback. Receives a chunk of records. Might be called multiple times.
		 * @return $this
		 */
		public function onMissing(callable $callback) {

			$this->missingCallback = $callback;

			return $this;
		}

		/**
		 * Invokes the given callback if condition is truthy
		 * @param mixed $condition The condition
		 * @param callable $callback The callback. Will receive this instance as argument
		 * @return $this
		 */
		public function when($condition, callable $callback) {
			if ($condition)
				call_user_func($callback, $this);

			return $this;
		}

		/**
		 * Prepares the bulk import
		 * @return PreparedBulkImport The prepared bulk import
		 */
		public function prepare() : PreparedBulkImport {
			if (!$this->model->getConnection()->transactionLevel())
				throw new RuntimeException('Bulk import cannot be performed without open transaction');
			if (empty($this->updateFields))
				throw new RuntimeException('Update fields not specified');


			$batchIdField      = $this->batchIdField;
			$createdMarkField  = $this->createdMarkField;
			$modifiedMarkField = $this->modifiedMarkField;

			$createAtField  = $this->createdAtField;
			$updatedAtField = $this->updatedAtField;

			$modelInstance = $this->model;

			$batchId = $this->batchId;

			// lock records (all of target scope)
			if ($this->lock) {
				$this->compileTargetConditions($modelInstance->newQuery())
					->lockForUpdate()
					->get([new Expression('max(' . $this->wrapField($modelInstance->getKeyName()) . ')')]);
			}


			$updateFields = [];
			if ($modifiedMarkField)
				$updateFields[$modifiedMarkField] = new Expression($this->compileModifiedWhen()); // This has to be the first field in update list! Otherwise the "values(...)" expression does not return the original values
			if ($createdMarkField)
				$updateFields[$createdMarkField] = false;
			$updateFields[$batchIdField] = $batchId;
			if ($updatedAtField) {
				// we only set updated_at if record is treated as "changed"
				if ($modifiedMarkField)
					$updateFields[$updatedAtField] = new Expression('if(' . $this->wrapField($modifiedMarkField) . ', values(' . $this->wrapField($updatedAtField) . '), ' . $this->wrapField($updatedAtField) . ')');
				else
					$updateFields[$updatedAtField] = new Expression($this->compileModifiedWhen('values(' . $this->wrapField($updatedAtField) . ')', $this->wrapField($updatedAtField)));
			}
			$updateFields = array_unique(array_merge($updateFields, $this->updateFields));


			// create buffer and return it
			$buffer = new FlushingBuffer($this->bufferSize, function ($data) use ($updateFields, $modelInstance, $createAtField, $updatedAtField, $batchIdField, $createdMarkField, $modifiedMarkField, $batchId) {

				$default = new stdClass();
				$now     = new Carbon();

				foreach ($data as &$curr) {

					// set timestamps
					if ($createAtField && ($curr[$createAtField] ?? $default) === $default)
						$curr[$createAtField] = $now;
					if ($updatedAtField && ($curr[$updatedAtField] ?? $default) === $default)
						$curr[$updatedAtField] = $now;

					// set batch evaluation fields
					$curr[$batchIdField] = $batchId;
					if ($createdMarkField)
						$curr[$createdMarkField] = true;
					if ($modifiedMarkField)
						$curr[$modifiedMarkField] = false;
				}


				call_user_func([get_class($modelInstance), 'insertOnDuplicateKey'], $data, $updateFields);
			});

			$afterCallback = function() use ($buffer, $modelInstance, $batchId, $batchIdField, $modifiedMarkField, $createdMarkField) {
				// flush buffer
				$buffer->flush();


				// evaluate data changes
				$evaluateCreated  = $this->createdCallback ? true : false;
				$evaluateModified = $this->modifiedCallback ? true : false;
				$evaluateMissing  = $this->missingCallback ? true : false;
				if ($evaluateMissing || $evaluateCreated || $evaluateModified) {

					// init fields to be returned to callbacks
					$evFields = $this->callbackFields ?: [$modelInstance->getKeyName()];

					// add batch id and mark fields to field list
					$evFields[] = $batchIdField;
					if ($evaluateModified) {
						$evFields[] = $modifiedMarkField;
					}
					if ($evaluateCreated) {
						$evFields[] = $createdMarkField;
					}

					// init callback buffers
					$collectionResolver = function () {
						return new Collection();
					};
					$createdBuffer      = $evaluateCreated ? new FlushingBuffer($this->callbackBufferSize, $this->createdCallback, $collectionResolver) : null;
					$modifiedBuffer     = $evaluateModified ? new FlushingBuffer($this->callbackBufferSize, $this->modifiedCallback, $collectionResolver) : null;
					$missingBuffer      = $evaluateMissing ? new FlushingBuffer($this->callbackBufferSize, $this->missingCallback, $collectionResolver) : null;


					// query data changes
					$this->compileTargetConditions($modelInstance->newQuery())
						// when no missing callback set, we do not have to query the whole scope
						// but only the records affected by this batch
						->when(!$missingBuffer, function ($query) use ($batchId) {
							/** @var Builder $query */
							return $query->where($this->batchIdField, $batchId);
						})
						->select($evFields)
						->chunk(500, function ($records) use ($batchId, $createdBuffer, $modifiedBuffer, $missingBuffer, $batchIdField, $createdMarkField, $modifiedMarkField) {

							foreach ($records as $curr) {
								if ($curr[$batchIdField] == $batchId) {

									if ($createdBuffer && $curr[$createdMarkField] ?? false) {
										// created
										$createdBuffer->add($curr);
									}
									elseif ($modifiedBuffer && $curr[$modifiedMarkField]) {
										// modified
										$modifiedBuffer->add($curr);
									}

								}
								elseif ($missingBuffer) {
									// missing
									$missingBuffer->add($curr);

								}
							}

						});

					// flush callback buffers
					if ($createdBuffer)
						$createdBuffer->flush();
					if ($modifiedBuffer)
						$modifiedBuffer->flush();
					if ($missingBuffer)
						$missingBuffer->flush();

				}
			};

			return new PreparedBulkImport($buffer, $afterCallback);
		}

		/**
		 * Performs the bulk import
		 * @param callable $callback Callback which fills the passed buffer with records to import
		 */
		public function perform(callable $callback) {

			$prepared = $this->prepare();

			// invoke user callback (which fills the buffer)
			call_user_func($callback, $prepared->getBuffer());

			// finish import
			$prepared->flush();
		}

		/**
		 * Compiles the modified when expression
		 * @param string $if The value to return if modified
		 * @param string $else The value to return if not modified
		 * @return string The SQL expression
		 */
		protected function compileModifiedWhen($if = '1', $else = '0') {

			if (empty($this->modifiedWhen))
				throw new RuntimeException('Modified when conditions are not specified');

			$ret     = [];
			foreach ($this->modifiedWhen as $curr) {
				if ($curr instanceof Expression) {
					$ret[] = (string)$curr;
				}
				else {
					$wrappedField = $this->wrapField($curr);
					$ret[]        = "$wrappedField != values($wrappedField)";
				}
			}

			return 'if(' . implode(' or ', $ret) . ", $if, $else)";
		}

		/**
		 * Compiles the scope conditions and adds them to the query
		 * @param \Illuminate\Database\Eloquent\Builder $query The query
		 * @return \Illuminate\Database\Eloquent\Builder|Builder The query
		 */
		protected function compileTargetConditions($query) {

			foreach ($this->targetConditionsArgs as $args) {
				$query->where(...$args);
			}

			return $query;

		}

		/**
		 * Wraps the field name for SQL
		 * @param string $field The field
		 * @return string The wrapped field
		 */
		protected function wrapField($field) {
			return $this->model->getConnection()->getQueryGrammar()->wrap($field);
		}


	}