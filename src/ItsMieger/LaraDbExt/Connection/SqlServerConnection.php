<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 10.12.18
	 * Time: 01:15
	 */

	namespace ItsMieger\LaraDbExt\Connection;


	class SqlServerConnection extends \Illuminate\Database\SqlServerConnection implements Forkable
	{
		use ResolvesQuery;
		use ForksSelf;
		use AdaptsTimezone;
	}