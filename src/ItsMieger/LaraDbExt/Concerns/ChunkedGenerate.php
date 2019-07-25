<?php


	namespace ItsMieger\LaraDbExt\Concerns;


	use Generator;
	use Illuminate\Support\Collection;

	trait ChunkedGenerate
	{
		/**
		 * Fetches the query results in chunks and returns them (using a generator)
		 *
		 * @param int $queryChunkSize The size of the queried chunks
		 * @return Generator
		 */
		public function generate(int $queryChunkSize = 500) {
			$this->enforceOrderBy();

			$page = 1;

			do {
				/** @var Collection $results */
				$results = $this->forPage($page, $queryChunkSize)->get();

				$countResults = $results->count();

				yield from $results;

				unset($results);

				++$page;

			} while ($countResults == $queryChunkSize);
		}
	}