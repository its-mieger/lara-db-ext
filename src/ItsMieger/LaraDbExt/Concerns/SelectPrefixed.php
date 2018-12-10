<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 05.12.18
	 * Time: 09:43
	 */

	namespace ItsMieger\LaraDbExt\Concerns;

	use Illuminate\Database\Connection;
	use Illuminate\Database\Grammar;
	use Illuminate\Database\Query\Expression;
	use Illuminate\Support\Str;
	use ItsMieger\LaraDbExt\Query\QueryManager;

	trait SelectPrefixed
	{
		/**
		 * @var QueryManager
		 */
		protected $queryManager;

		/**
		 * Run the query as a "select" statement against the connection.
		 *
		 * @return array
		 */
		protected function runSelect() {
			return iterator_to_array($this->runSelectPrefixed());
		}

		/**
		 * Get a generator for the given query.
		 *
		 * @return \Generator
		 */
		public function cursor() {
			if (is_null($this->columns)) {
				$this->columns = ['*'];
			}

			yield from $this->runSelectPrefixed();
		}


		/**
		 * Set the columns to be selected. The given prefix is prepended to every returned column name
		 *
		 * @param array|mixed|array[] $columns The column(s). If no prefix is passed the array keys must not be numeric and are used as column prefix
		 * @param string $prefix The prefix for returned column names
		 * @return $this
		 */
		public function selectPrefixed($columns, string $prefix = null) {

			return $this->select($this->wrapColumnsPrefixed((array)$columns, $prefix));
		}

		/**
		 * Adds new select column(s) to the query with their returned name being prepended with the given prefix
		 *
		 * @param array|mixed|array[] $column The column(s). If no prefix is passed the array keys must not be numeric and are used as column prefix
		 * @param string $prefix The prefix for returned column names
		 * @return $this
		 */
		public function addSelectPrefixed($column, string $prefix = null) {

			return $this->addSelect($this->wrapColumnsPrefixed((array)$column, $prefix));
		}

		/**
		 * This function adds two dummy columns around the column list which indicate the use of the given prefix for column the columns in between
		 * @param array $columns The columns
		 * @param string $prefix The prefix to use
		 * @return array The column list with dummy columns indicating the prefix usage
		 */
		protected function wrapColumnsPrefixed(array $columns, $prefix) {

			// if no prefix is set, we expect associative array of columns. The key will be used as column prefix
			if ($prefix === null) {
				$cols = [];

				$i = 0;
				foreach($columns as $pfx => $pfxColumns) {
					if (is_int($pfx))
						throw new \InvalidArgumentException("If no prefix is passed, columns is expected to be an array of columns where key is used as prefix. But key at index $i is not a string as expected.");

					$cols = array_merge($cols, $this->wrapColumnsPrefixed((array)$pfxColumns, $pfx));

					++$i;
				}

				return $cols;
			}


			// we wrap our columns with prefix markers, so we later on can
			// set the correct prefix. This allows us set correct prefix later on instead
			// of generating a unique column name for each queried column which is
			// not possible without querying the table structure

			/** @var Grammar $grammar */
			$grammar = $this->getGrammar();

			// encode prefix
			$prefix = $this->encodeSelectPrefix($prefix);

			// unshift the start wrap indicating the start of prefix usage
			array_unshift($columns, new Expression("null as " . $grammar->wrap("<group prefix=\"$prefix\">")));

			// append the end wrap indicating the end of prefix usage
			$columns[] = new Expression("null as " . $grammar->wrap("</group>"));

			return $columns;
		}

		/**
		 * Encodes the column prefix. It must not contain any dots (.) because they are interpreted as identifier separator by grammar's wrap function
		 * @param string $prefix The prefix
		 * @return string The encoded column prefix
		 */
		protected function encodeSelectPrefix($prefix) {
			return str_replace(['\\', '.'], ['\\\\', '\\:'], $prefix);
		}

		/**
		 * Decodes a column prefix which was encoded using encodeSelectPrefix()
		 * @param string $prefix The encoded prefix
		 * @return string The decoded prefixs
		 */
		protected function decodeSelectPrefix($prefix) {
			return str_replace(['\\:', '\\\\'], ['.', '\\'], $prefix);
		}

		/**
		 * Get a generator for the given query which handles prefixed column names
		 * @return \Generator The cursor
		 */
		protected function runSelectPrefixed() {
			/** @var \PDOStatement $statement */
			$statement = null;

			// hook in to prepared event, so we can set another fetch mode and can access the statement later on
			$this->getQueryManager()->onPrepared(function (\PDOStatement $stmt) use (&$statement) {
				$statement = $stmt;

				// we use num fetch mode to avoid conflicting column names
				$statement->setFetchMode(\PDO::FETCH_NUM);
			});


			// we use a cursor here, so we can fetch the column meta data. This is because
			// the column meta data is only available while result set is open. Since we
			// cannot hook in to the connection class here, we use a cursor which allows us
			// to access the result set while it is open
			/** @var \Generator $cursor */
			/** @var Connection $connection */
			$connection = $this->connection;
			$cursor = $connection->cursor(
				$this->toSql(), $this->getBindings(), !$this->useWritePdo
			);
			$cursor->rewind();  // we must call rewind, because the result set is not available until the cursor was accessed


			// here we build the names for returned columns. We recognize column groups with "<group>" prefix which allows
			// us to prefix columns from wildcard selector as we need it
			$columnNames = [];
			if (!$statement)
				throw new \RuntimeException('The prepared statement could not be set to different fetch mode. This bug might be caused by the framework not dispatching the "' . \Illuminate\Database\Events\StatementPrepared::class . '" event as expected by this library.');

			// cursor is empty, simply return empty iterator
			if (!$cursor->valid())
				return;

			$currPrefix = null;
			$colCount   = $statement->columnCount();
			for ($i = 0; $i < $colCount; ++$i) {

				$colName = $statement->getColumnMeta($i)['name'];

				if ($colName === '</group>') {
					// end if column group, we reset the prefix and do not return a column name
					$currPrefix = null;
					$colName    = 0; // we set col name to (int)0, so we can easily delete it later on
				}
				elseif (Str::startsWith($colName, '<group prefix="')) {
					// start of a column group, we extract the prefix and set if for all following columns
					$firstQuotePos = strpos($colName, '"');
					$lastQuotePos  = strrpos($colName, '"');

					$currPrefix = $this->decodeSelectPrefix(substr($colName, $firstQuotePos + 1, $lastQuotePos - $firstQuotePos - 1));
					$colName    = 0; // we set col name to (int)0, so we can easily delete it later on
				}
				elseif ($currPrefix) {
					// add prefix to column name
					$colName = "{$currPrefix}{$colName}";
				}

				$columnNames[$i] = $colName;
			}


			// iterate result and yield data with named (and prefixed) columns
			foreach ($cursor as $currRow) {


				$currRow = array_combine($columnNames, $currRow);

				// remove (int)0 index which is only a left over, of the column grouping functionality
				unset($currRow[0]);

				yield $currRow;
			}

		}

		/**
		 * Gets the query manager instance
		 * @return QueryManager the query manager instance
		 */
		protected function getQueryManager() {
			if (!$this->queryManager)
				$this->queryManager = app(QueryManager::class);

			return $this->queryManager;
		}

	}