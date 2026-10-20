<?php

namespace App\Infrastructure\Logging;

use App\Application\Ports\LoggerInterface;

/**
 * Appends one line per event to logs/<channel>_<date>.log. Never breaks the request.
 */
final class FileLogger implements LoggerInterface
{
  private string $directory;

  public function __construct(private string $channel = 'app', ?string $directory = null)
  {
    $this->directory = $directory ?? dirname(__DIR__, 3) . '/logs';
  }

  public function info(string $message, array $context = []): void
  {
    $this->write('INFO', $message, $context);
  }

  public function warning(string $message, array $context = []): void
  {
    $this->write('WARNING', $message, $context);
  }

  public function error(string $message, array $context = []): void
  {
    $this->write('ERROR', $message, $context);
  }

  /**
   * @param array<string,mixed> $context
   */
  private function write(string $level, string $message, array $context): void
  {
    $context += ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli'];
    $line = sprintf(
      "[%s] [%s] %s | %s\n",
      date('Y-m-d H:i:s'),
      $level,
      $message,
      json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );

    $file = $this->directory . '/' . $this->channel . '_' . date('Y-m-d') . '.log';
    if (@file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
      error_log('[' . $this->channel . '] ' . trim($line));
    }
  }
}
