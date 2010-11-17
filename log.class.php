<?php
/**
 * Log class
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is available
 * through the world-wide-web at this URL:
 * http://www.opensource.org/licenses/bsd-license.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@simonedamico.com so I can send you a copy immediately.
 *
 * @category   Log
 * @package    Log
 * @copyright  Copyright (c) 2010 Simone D'Amico (http://simonedamico.com)
 * @license    http://www.opensource.org/licenses/bsd-license.php     New BSD License
 * @version    1.00 (2010-10-27)
 */

class Log {
	
	/**
	 * Directory where log file will be stored
	 * 
	 * @access private
	 */
	private $directory;
	
	/**
	 * Log filename
	 * 
	 * @access private
	 */
	private $filename;
	
	/**
	 * Complete Path
	 * 
	 * @access private
	 */
	private $logpath;
    	
	/**
	 * Handle for open logfile 
	 * 
	 * @access private
	 */
	private $handle;
	
	/**
	 * Max size for log file (in byte)
	 *
	 * @access private
	 */
	private $maxfilesize;		//1048576 bytes = 1MB

	/**
	 * Action to do when the maximum size is exceeded
	 * 
	 * Values allowed: (delete, rename)
	 *
	 * @access private
	 */
	private $exceeded_size;
	
	/**
	 * Datetime formatting (http://php.net/manual/en/function.date.php)
	 *
	 * @access private
	 */
	private $datetime_format;
	
	/**
	 * Default timezone
	 * 
	 * @access private
	 */	
	private $default_timezone = "Europe/London";
	
	/**
	 * Log types
	 * 
	 * @access private
	 */
	private $log_types = array(
							'I' => '[INFO]   :',
							'W' => '[WARNING]:',
							'E' => '[ERROR]  :',
							'D' => '[DEBUG]  :',
							'P' => '[PROFILE]:'
						 );

	/**
	 * Unix timestamp for profiling
	 * 
	 * @access private
	 */
	private $start_profile = NULL;
	
	
	
	
	
	/**
	 * Constructor
	 * 
	 * @param string $directory
	 * @param string $filename
	 * @param string $datetime
	 * @param string $maxfilesize    (in MB)
	 * @param string $exceeded_size
	 */
	function __construct( $directory = "log/", $filename = "app.log", $datetime = "c", $maxfilesize = "1", $exceeded_size = "rename") {
		$this->directory = $directory;
		$this->filename = $filename;
		$this->datetime_format = $datetime;
		$this->set_maxfilesize($maxfilesize);
		$this->set_exceeded_size($exceeded_size);
		$this->logpath = $directory.$filename;
		
		$this->set_default_timezone();
		$this->open();
	}

	/**
	 * Destructor
	 * 
	 */	
	function __destruct() {
		$this->close();
		unset($this->start_profile);
	}

	/**
	 * Open the log file
	 * 
	 * If the filesize exceed the max file size the function provides to
	 * rename or remove the old file.
	 * 
	 * @return handle to log file
	 */
	function open() {
		if( file_exists($this->logpath) ) {
			if($this->filesize() >= $this->maxfilesize) {
				if($this->exceeded_size=='delete') {
					$this->handle = fopen($this->logpath, "w+") or die('Error while trying to open file specified');
				} else {
					$newfilename = $this->logpath.time();
					if( rename($this->logpath,$newfilename) )
						$this->handle = fopen($this->logpath, "a+") or die('Error while trying to open file specified');
					else die('Error while trying to rename old file');
				}
			}
			else $this->handle = fopen($this->logpath, "a+") or die('Error while trying to open file specified');
		} else $this->handle = fopen($this->logpath, "w+") or die('Error while trying to open file specified');
	}

	/**
	 * Close the log file
	 * 
	 */
	function close() {
		fclose($this->handle);
	}

	/**
	 * Return the file size (in byte)
	 * 
	 * @return filesize
	 */
	function filesize() {
		return filesize($this->logpath);
	}
	
	/**
	 * Return the date with the datetime_format
	 * 
	 * @return string $datetime
	 */
	function datetime() {
		return date($this->datetime_format);
	}
	
	
	/**
	 * Set max file size for log file
	 *  
	 * @param int $size (in MB)
	 * 
	 * @return int $maxfilesize (in bytes)
	 */
	function set_maxfilesize($size) {
		if($size) 
			$this->maxfilesize = $size * 1024 * 1024;
	}

	/**
	 * Set action to do when the max file size exceeds
	 * 
	 * @param string $action
	 */
	function set_exceeded_size($action) {
		switch($action) {
			case 'delete': 
			case 'remove': $this->exceededsize = 'delete';
						   break;
			
			case 'rename': 
			default:	   $this->exceededsize = 'rename';
						   break;
		}
	}
	
	/**
	 * Set default timezone
	 * 
	 * @param string $timezone
	 */
	function set_default_timezone($timezone = NULL) {
		if($timezone) $this->default_timezone = $timezone;
		date_default_timezone_set($this->default_timezone);
	}
	
	
	
	/**
	 * Get the log filename
	 * 
	 * @return string $filename
	 */
	function get_filename() {
		return $this->filename;
	}	

	/**
	 * Get the log directory
	 * 
	 * @return string $directory
	 */
	function get_directory() {
		return $this->directory;
	}
	
	
	
	/**
	 * Log method
	 * 
	 * @param string $string
	 * @param string $log_type
	 */
	function log( $string, $log_type = "I" ) {
		$datetime = $this->datetime();
		$fwrite = $datetime . " " . $this->log_types[$log_type] . " ". $string . "\r\n";
		fwrite($this->handle, $fwrite) or die('Error while trying to write on file specified.');
	}
	
	/**
	 * Alias for debugging log
	 * 
	 * @param string $string
	 */
	function _($string) {
		$this->log($string, 'D');
	}

	/**
	 * Debug method
	 * 
	 * @param string $string
	 */
	function debug($string) {
		$this->log($string, 'D');
	}

	/**
	 * Info method
	 * 
	 * @param string $string
	 */
	function info($string) {
		$this->log($string, 'I');
	}

	/**
	 * Warning method
	 * 
	 * @param string $string
	 */
	function warn($string) {
		$this->log($string, 'W');
	}

	/**
	 * Error method
	 * 
	 * @param string $string
	 */
	function error($string) {
		$this->log($string, 'E');
	}

	/**
	 * Profile method
	 * 
	 * The method must be called two times. The first call saves the timestamp on 
	 * $start_profile; the second call returns the execution time of script.
	 * 
	 * @param string $string
	 */
	function profile($string) {
		if(!$this->start_profile) {
			$this->start_profile = microtime(true);
			$this->log($string,'P');
		} else {
			$execution_time = microtime(true) - $this->start_profile;
			$this->log($string.$execution_time,'P');
			unset($this->start_profile);
			unset($this->$execution_time);
		}
	}
	
}

?>