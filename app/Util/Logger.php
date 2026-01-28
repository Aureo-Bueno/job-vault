<?php

namespace App\Util;

/**
 * Simple File Logger
 *
 * Logs application events to files.
 * Assumes logs directory exists (created in Dockerfile).
 *
 * @package App\Util
 * @version 2.0
 */
class Logger
{
  /**
   * Logger name/module
   *
   * @var string
   */
  private $name;

  /**
   * Log file path
   *
   * @var string
   */
  private $logFile;

  /**
   * Log levels
   */
  const LEVEL_DEBUG = 'DEBUG';
  const LEVEL_INFO = 'INFO';
  const LEVEL_WARNING = 'WARNING';
  const LEVEL_ERROR = 'ERROR';

  /**
   * Constructor
   *
   * Sets log file path.
   * Assumes logs directory already exists.
   *
   * @param string $name Module/logger name
   */
  public function __construct($name = 'app')
  {
    $this->name = $name;

    // Log file path - directory must exist (created by Dockerfile)
    $logsDir = __DIR__ . '/../../logs';
    $this->logFile = $logsDir . '/' . $name . '_' . date('Y-m-d') . '.log';
  }

  /**
   * Write log message to file
   *
   * Appends message to log file.
   * Uses error suppression to prevent warnings if write fails.
   *
   * @param string $level Log level
   * @param string $message Log message
   * @param array $context Additional context data
   * @return void
   */
  private function write($level, $message, $context = [])
  {
    try {
      $timestamp = date('Y-m-d H:i:s');
      $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
      $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";

      // Write to file with error suppression
      @file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    } catch (\Exception $e) {
      // Silently fail - don't break application
      error_log('Logger error: ' . $e->getMessage());
    }
  }

  /**
   * Log debug message
   *
   * @param string $message Debug message
   * @param array $context Context data
   * @return void
   */
  public function debug($message, $context = [])
  {
    $this->write(self::LEVEL_DEBUG, $message, $context);
  }

  /**
   * Log info message
   *
   * @param string $message Info message
   * @param array $context Context data
   * @return void
   */
  public function info($message, $context = [])
  {
    $this->write(self::LEVEL_INFO, $message, $context);
  }

  /**
   * Log warning message
   *
   * @param string $message Warning message
   * @param array $context Context data
   * @return void
   */
  public function warning($message, $context = [])
  {
    $this->write(self::LEVEL_WARNING, $message, $context);
  }

  /**
   * Log error message
   *
   * @param string $message Error message
   * @param array $context Context data
   * @return void
   */
  public function error($message, $context = [])
  {
    $this->write(self::LEVEL_ERROR, $message, $context);
  }
}
