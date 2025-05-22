<?php
/**
 * Database handling
 *
 * Handles all database operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the database functionality.
 *
 * Handles all database operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Database {

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Update table with current value line.
	 *
	 * @param   string $table_name The table to update.
	 * @param   array  $value  The values to update or insert in the table.
	 * @return integer The insert id if anny.
	 * @since 1.0.0
	 */
	public function insert_line( $table_name, $value ) {
		global $wpdb;
		// phpcs:ignore
		if ( $wpdb->insert( $wpdb->base_prefix . $table_name, $value ) ) {
			return $wpdb->insert_id;
		}
		return 0;
	}

	/**
	 * Update table with current value lines.
	 *
	 * @param   string $table_name The table to update.
	 * @param   array  $lines  The array of value lines to update or insert in the table.
	 * @return array The list of insert ids if anny.
	 * @since 1.0.0
	 */
	public function insert_lines( $table_name, $lines ) {
		global $wpdb;
		$result = [];
		foreach ( $lines as $line ) {
			// phpcs:ignore
			if ( $wpdb->insert( $wpdb->base_prefix . $table_name, $line ) ) {
				$id = $wpdb->insert_id;
				if ( 0 !== $id ) {
					$result[] = $id;
				}
			}
		}
		return $result;
	}

	/**
	 * Delete some lines in a table.
	 *
	 * @param string $table_name The table to update.
	 * @param string $field_name The name of the field containing ids.
	 * @param array  $value  The list of id to delete.
	 * @param string $sep Optional. Separator.
	 * @return int|false The number of rows deleted, or false on error.
	 * @since 1.0.0
	 */
	public function delete_lines( $table_name, $field_name, $value, $sep = '' ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . $table_name;
		$sql        = 'DELETE FROM ' . $table_name . ' WHERE ' . $field_name . ' IN (' . $sep . implode( $sep . ',' . $sep, $value ) . $sep . ')';
		// phpcs:ignore
		return $wpdb->query( $sql );
	}

	/**
	 * Load some lines from a table.
	 *
	 * @param string $table_name The table to load.
	 * @param string $field_name The name of the field containing ids.
	 * @param array  $value  The list of id to load.
	 * @param string $sep Optional. Separator.
	 * @return array The loaded lines.
	 * @since 1.0.0
	 */
	public function load_lines( $table_name, $field_name, $value, $sep = '' ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . $table_name;
		$sql        = 'SELECT * FROM ' . $table_name . ' WHERE ' . $field_name . ' IN (' . $sep . implode( $sep . ',' . $sep, $value ) . $sep . ')';
		// phpcs:ignore
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Update table with current value line.
	 *
	 * @param string $table_name The table name.
	 * @param array  $value The values to update or insert in the table.
	 * @since 1.0.0
	 */
	public function insert_update( $table_name, $value ) {
		$field_insert = [];
		$value_insert = [];
		$value_update = [];
		foreach ( $value as $k => $v ) {
			$field_insert[] = '`' . $k . '`';
			$value_insert[] = "'" . $v . "'";
			$value_update[] = '`' . $k . '`=' . "'" . $v . "'";
		}
		if ( count( $field_insert ) > 0 ) {
			global $wpdb;
			$sql  = 'INSERT INTO `' . $wpdb->base_prefix . $table_name . '` ';
			$sql .= '(' . implode( ',', $field_insert ) . ') ';
			$sql .= 'VALUES (' . implode( ',', $value_insert ) . ') ';
			$sql .= 'ON DUPLICATE KEY UPDATE ' . implode( ',', $value_update ) . ';';
			// phpcs:ignore
			$wpdb->query( $sql );
		}
	}

	/**
	 * Insert in a table with current value line.
	 *
	 * @param string $table_name The table name.
	 * @param array  $value The values to update or insert in the table.
	 * @since 1.0.0
	 */
	public function insert_ignore( $table_name, $value ) {
		$field_insert = [];
		$value_insert = [];
		foreach ( $value as $k => $v ) {
			$field_insert[] = '`' . $k . '`';
			$value_insert[] = "'" . $v . "'";
		}
		if ( count( $field_insert ) > 0 ) {
			global $wpdb;
			$sql  = 'INSERT IGNORE INTO `' . $wpdb->base_prefix . $table_name . '` ';
			$sql .= '(' . implode( ',', $field_insert ) . ') ';
			$sql .= 'VALUES (' . implode( ',', $value_insert ) . ');';
			// phpcs:ignore
			$wpdb->query( $sql );
		}
	}

	/**
	 * Delete some old lines in a table.
	 *
	 * @param   string  $table_name The table to update.
	 * @param   string  $field_name   The name of the field containing ids.
	 * @param   integer $limit  The number of lines to delete.
	 * @return int|false The number of rows deleted, or false on error.
	 * @since 1.0.0
	 */
	public function rotate( $table_name, $field_name, $limit ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . $table_name;
		$sql        = 'DELETE FROM ' . $table_name . ' ORDER BY ' . $field_name . ' ASC LIMIT ' . $limit;
		// phpcs:ignore
		return $wpdb->query( $sql );
	}

	/**
	 * Delete some old lines in a table.
	 *
	 * @param string  $table_name The table to update.
	 * @param string  $field_name The name of the field containing timestamp.
	 * @param integer $interval The number of hours of age to delete.
	 * @return int|false The number of rows deleted, or false on error.
	 * @since 1.0.0
	 */
	public function purge( $table_name, $field_name, $interval ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . $table_name;
		$sql        = 'DELETE FROM ' . $table_name . ' WHERE (' . $field_name . ' < NOW() - INTERVAL ' . $interval . ' HOUR);';
		// phpcs:ignore
		return $wpdb->query( $sql );
	}

	/**
	 * Drop a table.
	 *
	 * @param string $table_name The table to drop.
	 * @since 1.0.0
	 */
	protected static function drop( $table_name ) {
		global $wpdb;
		$sql = 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . $table_name;
		// phpcs:ignore
		$wpdb->query( $sql );
	}

	/**
	 * Count the number of records in a table.
	 *
	 * @param string $table_name The table to count.
	 * @return integer Count of records.
	 * @since 1.0.0
	 */
	public function count_lines( $table_name ) {
		return $this->count_filtered_lines( $table_name );
	}

	/**
	 * Count the number of records in a filtered table.
	 *
	 * @param   string      $table_name    The table to count.
	 * @param   array       $filters       The filters to apply.
	 * @return integer Count of records.
	 * @since 3.0.0
	 */
	public function count_filtered_lines( $table_name, $filters = [] ) {
		global $wpdb;
		$result = -1;
		if ( 0 !== strpos( $table_name, $wpdb->base_prefix ) ) {
			$table_name = $wpdb->base_prefix . $table_name;
		}
		$where_clause = '';
		if ( count( $filters ) > 0 ) {
			$wheres = [];
			foreach ( $filters as $key => $filter ) {
				if ( is_array( $filter ) ) {
					$w = [];
					foreach ( $filter as $f ) {
						if ( is_numeric( $f ) ) {
							$w[] = $f;
						} elseif ( is_string( $f ) ) {
							$w[] = "'" . $f . "'";
						}
					}
					$wheres[] = '`' . $key . '` IN (' . implode( ',', $w ) . ')';
				} elseif ( is_numeric( $filter ) ) {
					$wheres[] = '`' . $key . '` = ' . $filter;
				} elseif ( is_string( $filter ) ) {
					$wheres[] = '`' . $key . '` = \'' . $filter . '\'';
				}
			}
			if ( count( $wheres ) > 0 ) {
				$where_clause = ' WHERE ' . implode( ' AND ', $wheres );
			}
		}
		$sql = 'SELECT COUNT(*) as CNT FROM `' . $table_name . '`' . $where_clause . ';';
		// phpcs:ignore
		$cnt = $wpdb->get_results( $sql, ARRAY_A );
		if ( count( $cnt ) > 0 ) {
			if ( array_key_exists( 'CNT', $cnt[0] ) ) {
				$result = $cnt[0]['CNT'];
			}
		}
		return $result;
	}

	/**
	 * Performs a safe add column.
	 *
	 * @param string $table  Table name.
	 * @param string $column Column name.
	 * @param string $alter  Query to perform.
	 * @return boolean True if the operation was successful, false otherwise.
	 * @since 1.0.0
	 */
	public function safe_add_column( $table, $column, $alter ) {
		global $wpdb;
		$result = false;
		if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `" . $table . "` LIKE '" . $column . "'" ) ) {
			try {
				// phpcs:ignore
				$wpdb->query( $alter );
				$result = true;
			}
			catch ( \Exception $ex ) {
				$result = false;
			}
		}
		return $result;
	}

}
