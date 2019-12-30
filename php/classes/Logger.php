<?php

/**
 * $log = new Logger('sample.log', 'path/to');
 * write to sample.log
 * $log->add("message", errorLevel, dump);
 * output log to the screen
 * $log->print();
 */

class Logger
{
	const
		FATALS = [E_ERROR, E_PARSE, E_COMPILE_ERROR];

	protected
		# realpath to the log file
		$file,
		# array with a current log
		$log = [];

	/**
	 * @name - name of the log file
	 * optional @dir - realpath to the directory
	 * optional @rewriteLog - bool. If == true (default) then log file should rewriting
	 */
	public function __construct($name, $dir='.', $rewriteLog=true)
	{
		set_error_handler([&$this, 'userErrorHandler']);
		# Обрабатываем фаталы
		register_shutdown_function([&$this, 'handleFatals']);

		$this->file = $dir . "/$name";
		$this->rewriteLog = (bool) $rewriteLog;
	}

	/**
	 * @message - string to the log
	 * optional @level - error constant || code
	 * optional mixed @dump - will be output in the log by the function var_dump
	 */
	public function add(string $message, $level=null, $dump=[])
	{
		$bt = debug_backtrace();
		$caller = array_shift($bt);
		$fileName = basename($caller['file']);

		$log = $this->_formatLog($fileName, $caller['line'], $message, $level);

		if(!is_array($dump)) $dump = [$dump];
		if(count($dump))
		{
			foreach ($dump as $d) {
				ob_start();
				echo PHP_EOL;
				var_dump($d);
				$log .= ob_get_clean();
			}
		}

		$this->log[]= $log . PHP_EOL . PHP_EOL;
	}

	protected function _addToLog($fileName, $line, $message, $level=null)
	:string
	{
		return $this->log[]= $this->_formatLog($fileName, $line, $message, $level) . PHP_EOL . PHP_EOL;
	}


	protected function _formatLog($fileName, $line, $message, $level=null)
	:string
	{
		switch ($level) {
			case E_WARNING:
			case E_USER_WARNING:
				$errorLevel = ' WARNING';
				break;
			case E_ERROR:
			case E_USER_ERROR:
				$errorLevel = ' ERROR';
				break;
			default:
				$errorLevel = (bool) $level ? " code:$level " : '';
				break;
		}
		return "[{$fileName}: {$line} - " . date('D M d H:i:s Y',time()) . "$errorLevel] $message";
	}

	public function print()
	{
		print_r("<h3>Log</h3><pre>");
		foreach ($this->log as &$string) {
			print_r($string);
		}
		echo "</pre>";
	}


	public function userErrorHandler($errno, $errstr, $errfile, $errline) :bool
	{
		if (!(error_reporting() & $errno)) {
			// Этот код ошибки не включен в error_reporting,
			// так что пусть обрабатываются стандартным обработчиком ошибок PHP
			// return false;
		}

		$fileName = basename($errfile);

		switch ($errno) {
		case E_ERROR:
		case E_USER_ERROR:
			$this->_addToLog($fileName, $errline, $errstr, $errno);
			$this->__destruct();
			die("Завершение работы.<br />\n");
			break;

		/* case E_USER_WARNING:
			$this->_addToLog($fileName, $errline, $errstr, $errno);
			// $this->_addToLog($fileName, $errline, $errstr, E_USER_WARNING);
		break;

		case E_USER_NOTICE:
			$this->_addToLog($fileName, $errline, $errstr, $errno);
			// $this->_addToLog($fileName, $errline, $errstr, E_USER_NOTICE);
			break; */

		default:
			// echo "\nНеизвестная ошибка: [$errno] $fileName: $errline<br />\n";
			$this->_addToLog($fileName, $errline, $errstr, $errno);
			break;
		}

		# На серьёзных ошибках запускаем системный обработчик
		if ($errno && in_array($errno, self::FATALS))
			return false;
		else
			return true;
	} // userErrorHandler


	/**
	 * Логируем фаталы
	 */
	public function handleFatals()
	{
		$error = error_get_last();

		if (!$error || !in_array($error['type'], self::FATALS))
			return;

		# если кончилась память
		if (strpos($error['message'], 'Allowed memory size') === 0)
		{
			# выделяем немножко что бы доработать корректно
			ini_set('memory_limit', (intval(ini_get('memory_limit'))+64)."M");
		}

		$this->add("PHP Fatal: ".$error['message']." in ".$error['file'].":".$error['line']);

		$this->__destruct();
		$this->print();
		// die();

	} // handleFatals


	public function __destruct()
	{
		file_put_contents($this->file, $this->log, !$this->rewriteLog ? FILE_APPEND : null);
	}
} // Logger