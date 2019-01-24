<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 10.12.18
	 * Time: 01:14
	 */

	namespace ItsMieger\LaraDbExt\Connection;


	class SQLiteConnection extends \Illuminate\Database\SQLiteConnection implements Forkable
	{
		use ResolvesQuery;
		use ForksSelf;
		use AdaptsTimezone;
	}