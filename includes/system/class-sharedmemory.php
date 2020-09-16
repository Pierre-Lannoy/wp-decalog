<?php
/**
 * APCu handling
 *
 * Handles all APCu operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

use Decalog\System\Option;
use Decalog\System\File;
use Decalog\Logger;

/**
 * Define the APCu functionality.
 *
 * Handles all APCu operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class SharedMemory {

	/**
	 * Holds the system id for the shared memory block
	 *
	 * @var int
	 * @access protected
	 */
	protected $id;

	/**
	 * Holds the shared memory block id returned by shmop_open
	 *
	 * @var int
	 * @access protected
	 */
	protected $shmid = null;

	/**
	 * Holds the default permission (octal) that will be used in created memory blocks
	 *
	 * @var int
	 * @access protected
	 */
	protected $perms = 0766;

	/**
	 * Shared memory block instantiation
	 *
	 * In the constructor we'll check if the block we're going to manipulate
	 * already exists or needs to be created. If it exists, let's open it.
	 *
	 * @access public
	 * @param string $id ID of the shared memory block you want to manipulate
	 */
	public function __construct($id)
	{
		$this->id = $id;
	}

	/**
	 * Checks if a shared memory block with the provided id exists or not
	 *
	 * In order to check for shared memory existance, we have to open it with
	 * reading access. If it doesn't exist, warnings will be cast, therefore we
	 * suppress those with the @ operator.
	 *
	 * @access public
	 * @return boolean True if the block exists, false if it doesn't
	 */
	public function exists()
	{
		error_log('testing');
		//set_error_handler( function() { /* ignore errors */ } );
		try {
			$status = \shmop_open($this->id, "a", 0, 0);
		}  catch ( \Throwable $t ) {
			error_log('--' . $t->getMessage());
			return false;
		}

		error_log('passed');
		//restore_error_handler();
		return is_resource( $status );
	}

	/**
	 * Writes on a shared memory block
	 *
	 * First we check for the block existance, and if it doesn't, we'll create it. Now, if the
	 * block already exists, we need to delete it and create it again with a new byte allocation that
	 * matches the size of the data that we want to write there. We mark for deletion,  close the semaphore
	 * and create it again.
	 *
	 * @access public
	 * @param string $data The data that you wan't to write into the shared memory block
	 */
	public function write($data)
	{

		$size = mb_strlen($data, 'UTF-8');

		//set_error_handler( function() { /* ignore errors */ } );
		if($this->exists()) {
			error_log('already exists');
			$this->shmid = @shmop_open($this->id, "w", 0, 0);
			@shmop_delete($this->shmid);
			@shmop_close($this->shmid);
			$this->shmid = @shmop_open($this->id, "c", $this->perms, $size);
			@shmop_write($this->shmid, $data, 0);
		} else {
			error_log('creation needed');
			$this->shmid = @shmop_open($this->id, "c", $this->perms, $size);
			@shmop_write($this->shmid, $data, 0);
		}
		//restore_error_handler();
	}

	/**
	 * Reads from a shared memory block
	 *
	 * @access public
	 * @return string The data read from the shared memory block
	 */
	public function read()
	{
		return '';
		if ( ! is_resource( $this->shmid ) ) {
			return '';
		}
		//set_error_handler( function() { /* ignore errors */ } );
		if($this->exists()) {
			$size = shmop_size( $this->shmid );
			$data = shmop_read( $this->shmid, 0, $size );
		}
		//restore_error_handler();

		return $data;
	}

	/**
	 * Mark a shared memory block for deletion
	 *
	 * @access public
	 */
	public function delete()
	{
		shmop_delete($this->shmid);
	}

	/**
	 * Gets the current shared memory block id
	 *
	 * @access public
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Gets the current shared memory block permissions
	 *
	 * @access public
	 */
	public function getPermissions()
	{
		return $this->perms;
	}

	/**
	 * Sets the default permission (octal) that will be used in created memory blocks
	 *
	 * @access public
	 * @param string $perms Permissions, in octal form
	 */
	public function setPermissions($perms)
	{
		$this->perms = $perms;
	}

	/**
	 * Closes the shared memory block and stops manipulation
	 *
	 * @access public
	 */
	public function __destruct()
	{
		if ( is_resource( $this->shmid ) ) {
			//set_error_handler( function() { /* ignore errors */ } );
			@shmop_close($this->shmid);
			//restore_error_handler();
		}
	}

}
