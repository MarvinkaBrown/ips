//<?php

use IPS\Db\Select;

class convert_hook_Db extends _HOOK_CLASS_
{
	/**
	 * Build SELECT statement
	 *
	 * @param array|string $columns The columns (as an array) to select or an expression
	 * @param array|string $table The table to select from. Either (string) table_name or (array) ( name, alias ) or \IPS\Db\Select object
	 * @param array|string|null $where WHERE clause - see \IPS\Db::compileWhereClause() for details
	 * @param string|null $order ORDER BY clause
	 * @param int|array|null $limit Rows to fetch or array( offset, limit )
	 * @param string|NULL|array $group Column(s) to GROUP BY
	 * @param array|string|null $having HAVING clause (same format as WHERE clause)
	 * @param int $flags Bitwise flags
	 * @return    Select
	 * @li    \IPS\Db::SELECT_DISTINCT                Will use SELECT DISTINCT
	 * @li    \IPS\Db::SELECT_MULTIDIMENSIONAL_JOINS    Will return the result as a multidimensional array, with each joined table separately
	 */
	public function select( array|string $columns, array|string|Select $table, array|string $where=NULL, string $order=NULL, int|array $limit=NULL, array|string $group=NULL, array|string $having=NULL, int $flags=0 ): Select
    {
		switch( $table )
		{
			case 'custom_bbcode':
				$table = 'convert_custom_bbcode';
				break;
			
			case 'bbcode_mediatag':
				$table = 'convert_bbcode_mediatag';
				break;
		}
		
		return parent::select( $columns, $table, $where, $order, $limit, $group, $having, $flags );
	}
}