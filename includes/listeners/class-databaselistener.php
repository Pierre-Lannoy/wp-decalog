<?php
/**
 * WP database listener for DecaLog.
 *
 * Defines class for WP database listener.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Listener;

use Decalog\System\Environment;
use Decalog\System\Option;

if ( ! defined( 'DECALOG_SLOW_QUERY' ) ) {
	define( 'DECALOG_SLOW_QUERY', 0.05 );
}

/**
 * WP database listener for DecaLog.
 *
 * Defines methods and properties for WP database listener class.
 *
 * @package Listeners
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class DatabaseListener extends AbstractListener {

	/**
	 * Failed queries per request.
	 *
	 * @since 3.0.0
	 * @var    integer    $fails    Maintains the number of fails.
	 */
	private $fails = 0;

	/**
	 * Monitored SQL statements.
	 *
	 * @since 3.0.0
	 * @var    array    $statements    Monitored SQL statements.
	 */
	private $statements = [ 'select', 'show', 'update', 'insert', 'delete', 'import', 'load', 'replace', 'set', 'alter', 'create', 'drop', 'rename', 'truncate', 'call', 'do', 'transaction', 'savepoint', 'unlock', 'lock' ];

	/**
	 * Monitored SQL clauses.
	 *
	 * @since 3.0.0
	 * @var    array    $clauses    Monitored SQL clauses.
	 */
	private $clauses = [ 'join' ];

	/**
	 * Sets the listener properties.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		$this->id      = 'wpdb';
		$this->name    = esc_html__( 'Database', 'decalog' );
		$this->class   = 'db';
		$this->product = Environment::mysql_model();
		$this->version = Environment::mysql_version();
		sort( $this->statements );
	}

	/**
	 * Verify if this listener is needed, mainly by verifying if the listen plugin/theme is loaded.
	 *
	 * @return  boolean     True if listener is needed, false otherwise.
	 * @since    1.0.0
	 */
	protected function is_available() {
		return true;
	}

	/**
	 * "Launch" the listener.
	 *
	 * @return  boolean     True if listener was launched, false otherwise.
	 * @since    1.0.0
	 */
	protected function launch() {
		add_action( 'wp_loaded', [ $this, 'version_check' ] );
		add_action( 'shutdown', [ $this, 'shutdown' ], 10, 0 );
		add_filter( 'wp_die_ajax_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_xmlrpc_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_json_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_jsonp_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		add_filter( 'wp_die_xml_handler', [ $this, 'wp_die_handler' ], 10, 1 );
		return true;
	}

	/**
	 * Performs post-launch operations if needed.
	 *
	 * @since    2.4.0
	 */
	protected function launched() {
		$query_latencies = [ 0.0001, 0.00025, 0.0005, 0.00075, 0.001, 0.0025, 0.005, 0.0075, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 0.75, 1.0 ];
		$row_counts      = [ 1, 10, 100, 1000, 10000, 100000, 1000000 ];
		$bytes           = [ 1024, 10 * 1024, 100 * 1024, 1024 * 1024, 10 * 1024 * 1024, 100 * 1024 * 1024 ];
		$this->monitor->create_dev_counter( 'query_fail', 'Number of queries in error per request - [count]' );
		$this->monitor->create_dev_counter( 'query_success', 'Number of successful queries per request - [count]' );
		$this->monitor->create_dev_counter( 'query_duplicate', 'Number of duplicate queries per request - [count]' );
		$this->monitor->create_dev_counter( 'query_slow', 'Number of slow queries per request - [count]' );
		$this->monitor->create_prod_counter( 'query_total', 'Total number of queries per request - [count]' );
		$this->monitor->create_prod_gauge( 'query_total_latency_avg', 0, 'Average execution time per query - [second]' );
		$this->monitor->create_prod_gauge( 'query_total_latency_sum', 0, 'Execution time for all queries - [second]' );
		foreach ( $this->statements as $statement ) {
			$idx = 'statement_' . str_replace( ' ', '', $statement );
			$this->monitor->create_dev_counter( $idx, 'Number of `' . $statement . '` statements per request - [count]' );
			$this->monitor->create_dev_gauge( $idx . '_latency_avg', 0, 'Average execution time of `' . $statement . '` statements per request - [second]' );
		}
		foreach ( $this->clauses as $clause ) {
			$idx = 'clause_' . str_replace( ' ', '', $clause );
			$this->monitor->create_dev_counter( $idx, 'Number of `' . $clause . '` clause per request - [count]' );
		}
		$this->monitor->create_dev_histogram( 'query_detail_latency', $query_latencies, 'Detailed query latencies request - [second]' );
		$this->monitor->create_dev_counter( 'table_site', 'Number of tables belonging to this WordPress site - [count]' );
		$this->monitor->create_dev_counter( 'table_other', 'Number of tables not belonging to this WordPress site - [count]' );
		$this->monitor->create_dev_histogram( 'table_site_row', $row_counts, 'Number of rows per table belonging to this WordPress site - [count]' );
		$this->monitor->create_dev_histogram( 'table_other_row', $row_counts, 'Number of rows per table not belonging to this WordPress site - [count]' );
		$this->monitor->create_dev_histogram( 'table_site_data', $bytes, 'Data size of tables belonging to this WordPress site - [byte]' );
		$this->monitor->create_dev_histogram( 'table_site_index', $bytes, 'Index size of tables belonging to this WordPress site - [byte]' );
		$this->monitor->create_dev_histogram( 'table_other_data', $bytes, 'Data size of tables not belonging to this WordPress site - [byte]' );
		$this->monitor->create_dev_histogram( 'table_other_index', $bytes, 'Index size of tables not belonging to this WordPress site - [byte]' );
	}

	/**
	 * Check versions modifications.
	 *
	 * @since    1.2.0
	 */
	public function version_check() {
		$db_version  = Environment::mysql_version();
		$old_version = Option::network_get( 'db_version', 'x' );
		if ( 'x' === $old_version ) {
			Option::network_set( 'db_version', $db_version );
			return;
		}
		if ( $db_version === $old_version ) {
			return;
		}
		Option::network_set( 'db_version', $db_version );
		if ( version_compare( $db_version, $old_version, '<' ) ) {
			$this->logger->warning( sprintf( '%s version downgraded from %s to %s.', Environment::mysql_version(), $old_version, $db_version ) );
			return;
		}
		$this->logger->notice( sprintf( '%s version upgraded from %s to %s.', Environment::mysql_version(), $old_version, $db_version ) );
	}

	/**
	 * "shutdown" event.
	 *
	 * @since    1.0.0
	 */
	public function shutdown() {
		global $EZSQL_ERROR;
		if ( isset( $this->logger ) && is_array( $EZSQL_ERROR ) && 0 < count( $EZSQL_ERROR ) ) {
			foreach ( $EZSQL_ERROR as $error ) {
				$this->monitor->inc_dev_counter( 'query_fail', 1 );
				$this->fails++;
				$this->logger->critical( sprintf( 'A database error was detected during the page rendering: "%s" in the query "%s".', $error['error_str'], $error['query'] ) );
			}
		}
	}

	/**
	 * "wp_die_*" events.
	 *
	 * @since    1.0.0
	 */
	public function wp_die_handler( $handler ) {
		$dberror = array_filter(
			// phpcs:ignore
			debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 6 ),
			function ( $item ) {
				return isset( $item['function'] )
				&& isset( $item['class'] )
				&& ( 'bail' === $item['function'] || 'print_error' === $item['function'] )
				&& 'wpdb' === $item['class'];
			}
		);
		if ( ! $handler || ! is_callable( $handler ) || ! $dberror ) {
			return $handler;
		}
		$this->monitor->inc_dev_counter( 'query_fail', 1 );
		$this->fails++;
		return function ( $message, $title = '', $args = [] ) use ( $handler ) {
			$msg  = '';
			$code = 0;
			if ( is_string( $title ) && '' !== $title ) {
				$title = '"' . $title . '", ';
			}
			if ( is_numeric( $title ) ) {
				$code  = $title;
				$title = '';
			}
			if ( function_exists( 'is_wp_error' ) && is_wp_error( $message ) ) {
				$msg  = $title . $message->get_error_message();
				$code = $message->get_error_code();
			} elseif ( is_string( $message ) ) {
				$msg = $title . $message;
			}
			if ( '' !== $msg ) {
				$this->logger->critical( sprintf( 'Database error: %s.', wp_kses( $msg, [] ) ), $code );
			}
			return $handler( $message, $title, $args );
		};
	}

	/**
	 * Monitors queries.
	 *
	 * @since    3.0.0
	 */
	private function query_monitoring_close() {
		global $wpdb;
		if ( isset( $wpdb->queries ) && is_array( $wpdb->queries ) ) {
			$qtotal = count( $wpdb->queries );
		} elseif ( isset( $wpdb->num_queries ) && is_numeric( $wpdb->num_queries ) ) {
			$qtotal = (int) $wpdb->num_queries;
		} else {
			$qtotal = $this->fails;
		}
		$this->monitor->inc_prod_counter( 'query_total', $qtotal );
		$this->monitor->inc_dev_counter( 'query_success', $qtotal - $this->fails );
		if ( isset( $wpdb->queries ) && is_array( $wpdb->queries ) ) {
			$latencies  = [];
			$duplicates = [];
			foreach ( $this->statements as $statement ) {
				$latencies[ $statement ] = [];
			}
			foreach ( $wpdb->queries as $query ) {
				if ( is_array( $query ) && 1 < count( $query ) ) {
					$sql = str_replace( [ "\r\n", "\r", "\n" ], ' ', strtoupper( $query[0] ) );
					$sql = str_replace( [ "\t", '`' ], '', $sql );
					$sql = preg_replace( '/ +/', ' ', $sql );
					$sql = trim( $sql );
					$sql = rtrim( $sql, ';' );
					$sql = ' ' . $sql . ' ';
					foreach ( $this->statements as $statement ) {
						$count = substr_count( $sql, ' ' . strtoupper( $statement ) . ' ' );
						if ( 0 < $count ) {
							$this->monitor->inc_dev_counter( 'statement_' . str_replace( ' ', '', $statement ), $count );
						}
						if ( 0 === strpos( $sql, ' ' . strtoupper( $statement ) ) ) {
							$latencies[ $statement ][] = $query[1];
						}
					}
					foreach ( $this->clauses as $clause ) {
						$count = substr_count( $sql, ' ' . strtoupper( $clause ) . ' ' );
						if ( 0 < $count ) {
							$this->monitor->inc_dev_counter( 'clause_' . str_replace( ' ', '', $clause ), $count );
						}
					}
					if ( in_array( $sql, $duplicates, true ) ) {
						$this->monitor->inc_dev_counter( 'query_duplicate', 1 );
					} else {
						$duplicates[] = $sql;
					}
					if ( DECALOG_SLOW_QUERY <= $query[1] ) {
						$this->monitor->inc_dev_counter( 'query_slow', 1 );
					}
					$this->monitor->observe_dev_histogram( 'query_detail_latency', $query[1] );
				}
			}
			$total_latencies = [];
			foreach ( $this->statements as $statement ) {
				$avg = 0.0;
				if ( 0 !== count( $latencies[ $statement ] ) ) {
					$avg             = array_sum( $latencies[ $statement ] ) / count( $latencies[ $statement ] );
					$total_latencies = array_merge( $total_latencies, $latencies[ $statement ] );
				}
				$this->monitor->set_dev_gauge( 'statement_' . str_replace( ' ', '', $statement ) . '_latency_avg', $avg );
			}
			if ( 0 < count( $total_latencies ) ) {
				$sum = array_sum( $total_latencies );
				$this->monitor->set_prod_gauge( 'query_total_latency_avg', $sum / count( $total_latencies ) );
				$this->monitor->set_prod_gauge( 'query_total_latency_sum', $sum );
			}
		}
	}

	/**
	 * Monitors queries.
	 *
	 * @since    3.0.0
	 */
	private function db_monitoring_close() {
		global $wpdb;
		$sql = "SELECT * FROM information_schema.tables WHERE table_schema='" . $wpdb->dbname . "';";
		//phpcs:ignore
		$lines = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $lines as $line ) {
			if ( array_key_exists( 'TABLE_NAME', $line ) ) {
				if ( 0 === strpos( $line['TABLE_NAME'], $wpdb->prefix ) ) {
					$this->monitor->inc_dev_counter( 'table_site', 1 );
					if ( array_key_exists( 'TABLE_ROWS', $line ) ) {
						$this->monitor->observe_dev_histogram( 'table_site_row', (int) $line['TABLE_ROWS'] );
					}
					if ( array_key_exists( 'DATA_LENGTH', $line ) ) {
						$this->monitor->observe_dev_histogram( 'table_site_data', (int) $line['DATA_LENGTH'] );
					}
					if ( array_key_exists( 'INDEX_LENGTH', $line ) ) {
						$this->monitor->observe_dev_histogram( 'table_site_index', (int) $line['INDEX_LENGTH'] );
					}
				} else {
					$this->monitor->inc_dev_counter( 'table_other', 1 );
					if ( array_key_exists( 'TABLE_ROWS', $line ) ) {
						$this->monitor->observe_dev_histogram( 'table_other_row', (int) $line['TABLE_ROWS'] );
					}
					if ( array_key_exists( 'DATA_LENGTH', $line ) ) {
						$this->monitor->observe_dev_histogram( 'table_other_data', (int) $line['DATA_LENGTH'] );
					}
					if ( array_key_exists( 'INDEX_LENGTH', $line ) ) {
						$this->monitor->observe_dev_histogram( 'table_other_index', (int) $line['INDEX_LENGTH'] );
					}
				}
			}
		}
	}

	/**
	 * Monitor database.
	 *
	 * @since    3.0.0
	 */
	public function monitoring_close() {
		$this->query_monitoring_close();
		$this->db_monitoring_close();
	}

}
