<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 10.12.18
	 * Time: 01:13
	 */

	namespace ItsMieger\LaraDbExt\Connection;


	class PostgresConnection extends \Illuminate\Database\PostgresConnection
	{
		use ResolvesQuery;
	}