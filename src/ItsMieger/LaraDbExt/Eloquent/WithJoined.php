<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 30.11.18
	 * Time: 00:26
	 */

	namespace ItsMieger\LaraDbExt\Eloquent;


	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;
	use Illuminate\Database\Eloquent\Relations\HasOne;
	use Illuminate\Database\Eloquent\Relations\Relation;
	use Illuminate\Database\Query\Expression;


	trait WithJoined
	{
		/**
		 * The relationships that have been joined.
		 *
		 * @var array
		 */
		protected $withJoins = [];

		protected $withJoinedReductions = [];

		protected $withJoinedOrdersState = [];

		protected $withJoinedRootSortedByKey = false;

		protected $withJoinedParentKeyName = null;



		/**
		 * Adds related models to be queried using SQL joins
		 * @param string|string[]|mixed[][] $relation The relation name(s). If multidimensional array is passed, this is equivalent to calling this function multiple times with each item interpreted as arguments
		 * @param bool $onlyExisting True if to query only models where the related model exists. This will use an inner join instead of left join
		 * @return $this
		 */
		public function withJoined($relation, $onlyExisting = false) {

			// if the first argument is an array, we will assume multiple
			// relations to be joined
			if (is_array($relation)) {
				foreach($relation as $args) {
					if (is_array($args))
						$this->withJoined(...$args);
					else
						$this->withJoined($args);
				}

				return $this;
			}

			$options = [
				'onlyExisting' => $onlyExisting,
			];


			foreach($this->extractWithJoinedNestedRelations($relation) as $currNestedRelation) {

				// is the relation already defined?
				if (($existingOptions = ($this->withJoins[$currNestedRelation] ?? null)) !== null) {

					// check if options of existing join match the current options. If this is not the case there is a conflict and we throw an exception
					if ($existingOptions['onlyExisting'] != $options['onlyExisting']) {
						throw new \InvalidArgumentException("Relation \"$relation\" already exists, but with option \"onlyExisting\"=\"" . ($existingOptions['onlyExisting'] ? 'true' : 'false') . "\"");
					}

					// the relation was already added with same options, there is nothing to be done here
					continue;
				}

				/** @var Relation $relation */
				$currRelInstance = $this->getWithJoinedModelRelation($currNestedRelation);

				// add joins
				$this->addWithJoinsToQuery($currNestedRelation, $currRelInstance, $options);
				// add fields
				$this->addWithJoinFieldsToQuery($currNestedRelation);

				$this->withJoins[$currNestedRelation] = $options;
			}

			return $this;
		}


		/**
		 * Adds an order by clause for a parent model's field
		 * @param string|Expression $column The column to sort. If this should be an expression the `allowUnsafe` parameter must be set to true. If omitted the parent model's unique key field is used as field
		 * @param string $direction The sort direction ('desc' or 'asc')
		 * @param bool $allowUnsafe True to allow usage of expressions. Warning: if you pass an expression which does not sort the parent model, the query result might be broken
		 * @return $this
		 */
		public function orderByParent($column = null, $direction = 'asc', $allowUnsafe = false) {

			// assert not external order changes destroyed the necessary ordering
			$this->assertWithJoinedQueryOrderIntact();

			$pkField = $this->getWithJoinedParentKeyName();

			// if column is omitted, we sort by model's primary key
			if ($column === null) {
				if (!$pkField)
					throw new \InvalidArgumentException('Column must be set, if parent model does not have a primary key');

				$column = $pkField;
			}

			// remember if we sorted the primary key field
			$sortsRootByKey = $column === $pkField;


			if ($column instanceof Expression) {
				if (!$allowUnsafe)
					throw new \InvalidArgumentException('Column parameter cannot be an expression, unless allowUnsafe is set. But this parameter should only be used if, you are sure that the expression really sorts the parent model.');
			}
			else {
				/** @var Model $model */
				$model = $this->model;

				$column = "{$model->getTable()}.$column";
			}

			/** @var \ItsMieger\LaraDbExt\Query\Builder $query */
			$query = $this->query;

			$query->orderBy($column, $direction);

			// if we sorted the root key, remember this
			if ($sortsRootByKey)
				$this->withJoinedRootSortedByKey = true;

			// remember ordering state, to detect external changes
			$this->withJoinedOrdersState = $query->orders;


			return $this;
		}

		/**
		 * Orders the query result by parent model's unique key
		 * @param string $direction The sort direction
		 * @return $this
		 */
		public function orderByParentKey($direction = 'asc') {
			return $this->orderByParent(null, $direction);
		}

		/**
		 * Adds an order by clause for a related model's field. For a query with 1:n relations this only possible after a unique key sort has been applied to parent model
		 * @param string $relation The relation to sort
		 * @param string|Expression $column The column to sort. If this should be an expression the `allowUnsafe` parameter must be set to true.
		 * @param string $direction The sort direction ('desc' or 'asc')
		 * @param bool $allowUnsafe True to allow usage of expressions. Warning: if you pass an expression which does not sort the specified related model, the query result might be broken
		 * @return $this
		 */
		public function orderByRelated($relation, $column, $direction = 'asc', $allowUnsafe = false) {

			// assert not external order changes destroyed the necessary ordering
			$this->assertWithJoinedQueryOrderIntact();

			if ($column instanceof Expression) {
				if (!$allowUnsafe)
					throw new \InvalidArgumentException('Column parameter cannot be an expression, unless allowUnsafe is set. But this parameter should only be used if, you are sure that the expression really sorts the given related model.');
			}
			else {
				$column = "{$this->getWithJoinedRelationAlias($relation)}.$column";
			}

			if ($this->withJoinedReductions && !$this->withJoinedRootSortedByKey) {

				foreach($this->extractWithJoinedNestedRelations($relation) as $currRelation) {
					$relInstance = $this->getWithJoinedModelRelation($currRelation);

					if (!($relInstance instanceof BelongsTo || $relInstance instanceof HasOne)) {
						throw new \InvalidArgumentException("Cannot apply sorting for 1:n relation \"$relation\" because parent model must be sorted by unique key first");
					}
				}

			}

			/** @var \ItsMieger\LaraDbExt\Query\Builder $query */
			$query = $this->query;

			$query->orderBy($column, $direction);

			$this->withJoinedOrdersState = $query->orders;


			return $this;
		}

		/**
		 * Marks the given parent model's column as unique key to use. This column will be used instead of the model's primary key to
		 * determine identity. Note: this column does not have to be part of the table as long as it is returned by the query
		 * @param string $column The column name
		 * @return $this
		 */
		public function markParentColumnAsUniqueKey(string $column) {
			$this->withJoinedParentKeyName = $column;

			return $this;
		}

		/**
		 * Returns if any model's are joined
		 * @return bool True if any model's are joined. Else false.
		 */
		protected function hasModelsJoined(): bool {
			return !empty($this->withJoins);
		}


		/**
		 * Gets the models with joined related models' data
		 * @param array $columns Additional columns to fetch
		 * @return \Illuminate\Database\Eloquent\Model[] The models
		 */
		protected function getModelsWithJoined($columns = ['*']) {

			/** @var Model $model */
			$model = $this->model;

			/** @noinspection PhpUndefinedMethodInspection */
			return $model->hydrate(
				iterator_to_array($this->applyScopes()->runWithJoinedSelect($columns), false)
			)->all();

		}


		/**
		 * Returns a cursor to receive the models with joined related models' data
		 * @param array $columns Additional columns to fetch
		 * @return \Generator \Illuminate\Database\Eloquent\Model[] The models
		 */
		protected function cursorWithJoined($columns = ['*']) {

			/** @var Model $model */
			$model = $this->model;

			/** @noinspection PhpUndefinedMethodInspection */
			foreach ($this->applyScopes()->runWithJoinedSelect($columns) as $record) {
				yield $model->newFromBuilder($record);
			}
		}

		/**
		 * Runs a select for model and the joined models' data
		 * @param array $columns Additional columns to fetch
		 * @return \Generator The generator returning the data
		 */
		protected function runWithJoinedSelect($columns) {
			// if the result set has to be reduced (due to 1:n relations which
			// cause multiple rows in result per parent model) limit and offset
			// in a query will corrupt the result. If they exist, the developer
			// tries to accomplish a behaviour which we cannot provide here,
			// so we throw on exception. Otherwise unaware developers get
			// mousetrapped by the way model joining is implemented
			if (!empty($this->withJoinedReductions)) {
				/** @var \ItsMieger\LaraDbExt\Query\Builder $query */
				$query = $this->query;

				if (isset($query->limit) || isset($query->unionLimit) || isset($query->offset) || isset($query->unionOffset)) {
					throw new \RuntimeException('The query contains 1:n related models. Therefore it must not contain limit or offset clauses because they most likely cause unexpected results.');
				}

				// if yet not sorted by root key, sort it! This is necessary for 1:n relation queries
				if (!$this->withJoinedRootSortedByKey)
					$this->orderByParentKey();
			}


			/** @var Model $model */
			$model = $this->model;

			// add select fields
			$this->addSelect(array_merge([$model->getTable() . '.*'], array_filter($columns, function ($col) {
					// we already added the columns of all tables so we do not need the asterisk here which simply would
					// cause duplicate retrieval of same content
					return $col !== '*';
				})
			));


			/** @var \ItsMieger\LaraDbExt\Query\Builder $query */
			$query = $this->query;

			yield from $this->processWithJoinedResult($query->cursor());
		}

		/**
		 * This function reduces the result set for joined models
		 * @param \Iterator $result The result
		 * @return \Generator The generator outputs a single row per root record
		 */
		protected function processWithJoinedResult(\Iterator $result) {

			// iterator already closed? there is nothing to do
			if (!$result->valid())
				return;


			// first we collect some information about the relations which is necessary to extract
			// fields later on
			$reductions    = $this->withJoinedReductions;
			$relationsInfo = [];
			foreach (array_keys($this->withJoins) as $currRelation) {
				$lastSeparatorPos = strrpos($currRelation, '.');

				$relationsInfo[$this->getWithJoinedRelationAlias($currRelation)] = [
					// will hold the parent relation's alias if this is a nested relation
					/* 'parent'    => */$this->getWithJoinedRelationAlias(substr($currRelation, 0, $lastSeparatorPos)),
					// will be the name of the model attribute where we put the relation data
					/* 'attr'      => */ '::' . ($lastSeparatorPos === false ? $currRelation : substr($currRelation, $lastSeparatorPos + 1)),
					// if this is a relation which has to be reduced (hasMany) this will hold the primary key field
					/* 'reduceKey' => */($reductions[$currRelation] ?? null)
				];
			}


			// here we group result columns by their relation and determine the
			// target attribute within their model
			$relatedAttributes = [];
			$rootAttributes = [];
			foreach (array_keys($result->current()) as $columnName) {
				$lastSeparatorPos = strrpos($columnName, '::');

				$relationAlias = substr($columnName, 0, $lastSeparatorPos);
				$attrName      = $lastSeparatorPos === false ? $columnName : substr($columnName, $lastSeparatorPos + 2);

				if ($relationAlias)
					$relatedAttributes[$relationAlias][$columnName] = $attrName;
				else
					$rootAttributes[$columnName] = $attrName;
			}


			// obtain primary key name of the root model
			$pkField = $this->getWithJoinedParentKeyName();


			// The following loop iterates the result set and reduces the rows to one for each
			// root record. Each combined row will contain all (nested) relations data and is
			// yielded as soon as a row for another root record comes up. Therefore the result
			// set must be ordered in a way that the rows for a root record are listed right
			// after each other
			$reducedRowKey   = null;
			$reducedRow      = null;
			$relatedIndexMap = null;
			foreach($result as $currRow) {

				// first we check if the current row belongs to another root record by comparing the last
				// and the current primary key. If we have a new root record, we yield the last row and
				// init a new reduced one
				$currRowKey = $currRow[$pkField];
				if ($currRowKey !== $reducedRowKey) {

					// yield last row if exists. On First loop iteration it will be empty.
					if ($reducedRow)
						yield $reducedRow;


					// create a new row with root attributes from result set
					$reducedRow = array_combine($rootAttributes, array_intersect_key($currRow, $rootAttributes));

					// remember the new primary key
					$reducedRowKey = $currRowKey;
				}


				// this is the core part of reducing the result set. Here we extract new data for related models
				// from the current data row and put it to the reduced row data
				$relationDataReferences = [];
				foreach ($relationsInfo as $relationAlias => $currRelation) {

					[$relationParent, $relationAttribute, $relationReduceKeyField] = $currRelation;

					// first we set our "pointer" to the relation's parent's data. Here we will
					// create a new attribute for the relation data. The parent could either be
					// the root model's data or a parent relation's data set
					if ($relationParent) {

						// if no reference to parent relation's data exists, this means the
						// parent relation is unset. Any nested relations therefore will be
						// unset either, and we can immediately skip processing of this
						// nested relation
						if (($relationDataReferences[$relationParent] ?? false) === false)
							continue;

						$currRef = &$relationDataReferences[$relationParent];
					}
					else {
						$currRef = &$reducedRow;
					}

					// check if relation attribute already exists. If this is
					// not the case create it. Then set "pointer" to it.
					if (($currRef[$relationAttribute] ?? false) === false) {

						if ($relationReduceKeyField) {
							// has many

							// we create the relation's attribute of the parent model as empty array.
							// A new item will be added to this array for each related record.
							$currRef[$relationAttribute] = [];
						}
						else {
							// one to one

							// we fill the one-to-one relation by extracting the relation data from current row
							// to the relation's attribute of the parent model
							$relAttr                     = $relatedAttributes[$relationAlias];
							$currRef[$relationAttribute] = array_combine($relAttr, array_intersect_key($currRow, $relAttr));
						}
					}
					// update the reference, so nested relations can access the current relation's data
					$currRef = &$currRef[$relationAttribute];


					// for has many, the relation's attribute of the parent model contains a list
					// of related records. So far only the list has been created and now has to be
					// populated
					if ($relationReduceKeyField) {

						// we need to obtain the value of the related model's primary key
						// to avoid duplicates
						$currKeyValue = $currRow[$relationReduceKeyField];

						// if the primary key of related data is null, there is nothing to fill
						// for this and relation and it's children. We can continue with next relation.
						if ($currKeyValue === null)
							continue;

						// as the list of related models uses the primary key of related models as index,
						// we just check for existence of current record by checking if index exists
						if (($currRef[$currKeyValue] ?? false) === false) {

							// we create a new related record by extracting the relation data from current row to
							// a new list item using primary key as index
							$relAttr                = $relatedAttributes[$relationAlias];
							$currRef[$currKeyValue] = array_combine($relAttr, array_intersect_key($currRow, $relAttr));
						}
						// update the reference, so nested relations can access the current relation's data
						$currRef = &$currRef[$currKeyValue];
					}


					// we keep a reference to relation's data, so nested relations data can be inserted later
					$relationDataReferences[$relationAlias] = &$currRef;

				}


			}

			// the last reduced row was not yielded yet, so do so
			if ($reducedRow)
				yield $reducedRow;
		}


		/**
		 * Adds the join for a joined model to the query
		 * @param string $relationName The relation name
		 * @param Relation $relation The relation instance
		 * @param array $withOptions The join options
		 */
		protected function addWithJoinsToQuery($relationName, Relation $relation, array $withOptions) {


			/** @var \ItsMieger\LaraDbExt\Query\Builder $query */
			$query = $this->query;


			$parentTable = $this->getWithJoinedRelationParentAlias($relationName);

			$relationAlias = $this->getWithJoinedRelationAlias($relationName);

			if ($relation instanceof BelongsTo) {
				$query->join(
					$relation->getRelated()->getTable() . ' as ' . $relationAlias,
					$parentTable . '.' . $relation->getForeignKeyName(),
					'=',
					$relationAlias . '.' . $relation->getOwnerKeyName(),
					($withOptions['onlyExisting'] ? 'inner' : 'left')
				);
			}
			elseif ($relation instanceof HasOne) {
				$query->join(
					$relation->getRelated()->getTable() . ' as ' . $relationAlias,
					$parentTable . '.' . $relation->getLocalKeyName(),
					'=',
					$relationAlias . '.' . $relation->getForeignKeyName(),
					($withOptions['onlyExisting'] ? 'inner' : 'left')
				);
			}
			elseif ($relation instanceof HasMany) {

				// check parent model's unique key
				if (!$this->getWithJoinedParentKeyName())
					throw new \InvalidArgumentException("Cannot join \"$relationName\" because root model " . get_class($this->model) . " has no unique key. But this is required for 1:n related models. You may explicitly mark a queried column as unique key using markParentColumnAsUniqueKey() method. Otherwise the model's primary key will be used.");

				// obtain related model's unique key
				$relatedUniqueKey = $withOptions['uniqueKey'] ?? false;
				if (!$relatedUniqueKey)
					$relatedUniqueKey = $relation->getRelated()->getKeyName();
				if (!$relatedUniqueKey)
					throw new \InvalidArgumentException("Cannot join \"$relationName\" because related model" . get_class($relation->getRelated()). " has no unique key. But this is required for 1:n related models. You may explicitly mark a queried column as unique key using the \"uniqueKey\" parameter. Otherwise the model's primary key will be used.");


				// refuse to join, if ordering already applied
				if ($query->orders)
					throw new \InvalidArgumentException("Cannot join \"$relationName\" because ordering has already been applied. 1:n relations must be added before any ordering is applied");


				$query->join(
					$relation->getRelated()->getTable() . ' as ' . $relationAlias,
					$parentTable . '.' . $relation->getLocalKeyName(),
					'=',
					$relationAlias . '.' . $relation->getForeignKeyName(),
					($withOptions['onlyExisting'] ? 'inner' : 'left')
				);


				$this->withJoinedReductions[$relationName] = $this->prefixWithJoinedRelatedField($relationAlias, $relatedUniqueKey);
			}


			else {
				throw new \InvalidArgumentException("Using join for relation \"$relationName\" is not possible since relation type \"" . get_class($relation) . "\" is not supported");
			}
		}

		/**
		 * Adds the fields to select for a joined model to the query
		 * @param string $relationName The relation name
		 */
		protected function addWithJoinFieldsToQuery($relationName) {

			// alis for relation
			$relationAlias = $this->getWithJoinedRelationAlias($relationName);

			$this->addSelectPrefixed("$relationAlias.*", $this->prefixWithJoinedRelatedField($relationAlias, ''));
		}

		/**
		 * Extracts all nested relations from a given relation name. This means the relation path and all implicit relation paths are returned
		 * @param string $relation The relation name
		 * @return string[] All (nested) relations within the given relation name
		 */
		protected function extractWithJoinedNestedRelations(string $relation): array {
			$ret = [];
			$segments = explode('.', $relation);
			foreach($segments as $index => $curr) {
				$ret[] = implode('.', array_slice($segments, 0, $index + 1));
			}

			return $ret;
		}

		/**
		 * Gets the model's (nested) relation with the given path
		 * @param string $relation The relation path
		 * @return Relation The relation
		 * @throws \InvalidArgumentException
		 */
		protected function getWithJoinedModelRelation(string $relation): Relation {
			$segments = explode('.', $relation);

			$model = $this->model;
			/** @var Relation $relation */
			$relation = null;
			foreach($segments as $index => $currRelation) {

				// use last nested model as new model
				if ($relation)
					$model = $relation->getRelated();

				try {
					// get the relation of the model
					$relation = $model->{$currRelation}();
					if (!($relation instanceof Relation))
						throw new \BadMethodCallException();
				}
				catch (\BadMethodCallException $ex) {
					throw new \InvalidArgumentException('Relation "' . implode('.', array_slice($segments, 0, $index + 1)) . '" does not exist');
				}
			}

			return $relation;
		}

		/**
		 * Checks that the queries "order by" clauses have not been modified externally for 1:n relation queries.
		 * @param bool $force True if to force check, regardless if 1:n relations exist or not
		 * @throws \RuntimeException
		 */
		protected function assertWithJoinedQueryOrderIntact($force = false) {

			if ($force || $this->withJoinedReductions) {

				// first we check if the developer already inserted own order by statements. If this is the case
				// it could corrupt our hasMany-required ordering, so we throw an exception
				/** @var \ItsMieger\LaraDbExt\Query\Builder $query */
				$query = $this->query;
				if ($this->withJoinedOrdersState != $query->orders)
					throw new \RuntimeException('Could not apply ordering because ordering has already been applied externally to this 1:n query. This could lead to corrupted results. Therefore always use orderByParent() and orderByRelated() if you want to apply ordering to joined relations query.');
			}
		}

		/**
		 * Prefixes a field name with a given table alias
		 * @param string $alias The table alias
		 * @param string $field The field
		 * @return string The field prefix with the table alias
		 */
		protected function prefixWithJoinedRelatedField($alias, $field) {
			return "$alias::$field";
		}

		/**
		 * Gets the alias for the given relation's table
		 * @param string $relationName The relation name
		 * @return string The alias
		 */
		protected function getWithJoinedRelationAlias($relationName) {
			return str_replace('.', '::', $relationName);
		}

		/**
		 * Gets the alias for the given relation's parent table.
		 * @param string $relationName The relation name
		 * @return string The relation's parent table alias. If relation is not nested, the root model's table will be returned
		 */
		protected function getWithJoinedRelationParentAlias(string $relationName) {
			$segments = explode('.', $relationName);

			// get the path to joined parent
			$joinedParentPath = implode('.', array_slice($segments, 0, count($segments) - 1));


			// if joined parent path is empty, the root model is the parent
			if (!$joinedParentPath) {

				/** @var Model $model */
				$model = $this->model;

				// return root model's table
				return $model->getTable();
			}

			// encode parent path as alias
			return $this->getWithJoinedRelationAlias($joinedParentPath);
		}

		protected function getWithJoinedParentKeyName() {

			$key = $this->withJoinedParentKeyName;

			if (!$key) {
				/** @var Model $model */
				$model = $this->model;

				$key = $model->getKeyName();
			}

			return $key;
		}


	}