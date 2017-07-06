<?php
/*
Basic logging class
Allows configuration of logging level, path, and filename.
*/
declare(strict_types=1);

class Logger {
	// ref: https://en.wikipedia.org/wiki/Log4j#Log4j_log_levels
	private const LOG_LEVELS = [
		'off'   => 0,
		'fatal' => 1,
		'error' => 2,
		'warn'  => 3,
		'info'  => 4,
		'debug' => 5,
		'trace' => 6,
	];
	private $log_level = null;
	private $log_file_name = null;

	public function __construct(array $options) {
		// Ensure the following options are available, if not already set
		$option_defaults = [
			'log_level' => 'error',
			'log_file_name' => 'realmeyeapi',
		];
		foreach ($option_defaults as $option => $option_value) {
			if (!array_key_exists($option, $options)) {
				$options[$option] = $option_value;
			}
		}

		if (!$this->check_valid_log_level($options['log_level'])) {
			throw new Exception('Invalid log level passed: ' . $level);
		}

		$this->log_level = $options['log_level'];
		$this->log_file_name = $options['log_file_name'];
	}

	// Accepts log level string and unformatted log message
	// Returns a boolean indicating whether any new data was written to log
	// Path will default to log/realmeyeapi_2012-01-23.log
	public function write(string $log_level, string $message): bool {
		if (!$this->check_valid_log_level($log_level)) {
			return false;
		} else if (
			self::LOG_LEVELS[$log_level] <= self::LOG_LEVELS[$this->log_level]
		) {
			$log_dir = 'log';
			// if log level is less than configured level, log the message
			$file_path = $log_dir . '/' . $this->log_file_name . '_' .
			             date('o-m-d') . '.log';

			// Remove existing file if matches log dir; ensure log dir exists
			if (!is_dir($log_dir)) {
				if (file_exists($log_dir)) {
					unlink($log_dir);
				}
				mkdir($log_dir, 0755, true);
			}

			// Write log and return write response
			$write_response = file_put_contents(
				$file_path,
				$this->format_message($log_level, $message),
				FILE_APPEND
			);
			return (bool) $write_response;
		}
		return false;
	}

	// Shorthand aliases for write('level', ...)
	public function fatal(string $message) {
		$this->write(__FUNCTION__, $message);
	}
	public function error(string $message) {
		$this->write(__FUNCTION__, $message);
	}
	public function warn(string $message) {
		$this->write(__FUNCTION__, $message);
	}
	public function info(string $message) {
		$this->write(__FUNCTION__, $message);
	}
	public function debug(string $message) {
		$this->write(__FUNCTION__, $message);
	}
	public function trace(string $message) {
		$this->write(__FUNCTION__, $message);
	}

	// Accepts a log level and log message string to be formatted
	// Returns a formatted string in the format below:
	//   [ISO8601 time] [level] file.php(123): message
	private function format_message(string $level, string $message): string {
		$datetime = date('c');
		$debug_info = debug_backtrace();
		$caller = $debug_info[count($debug_info) - 1];
		$file = basename($caller['file']);
		$line = $caller['line'];
		$formatted_message = '[' . $datetime . '] ';
		$formatted_message .= '[' . $level . '] ';
		$formatted_message .= $file . '(' . $line . '): ';
		$formatted_message .= $message;
		$formatted_message .= "\n";
		return $formatted_message;
	}

	// Accepts a log level string to validate
	// Returns false if it is not an allowed level (eg. ERRROR)
	// Returns true if it is an allowed level (eg. ERROR)
	private function check_valid_log_level(string $level): bool {
		if (!array_key_exists($level, self::LOG_LEVELS)) {
			return false;
		}
		return true;
	}
}
