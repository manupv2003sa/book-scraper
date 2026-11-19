<?php 
namespace App\Logging;

use App\Logging\LoggerInterface;
use RuntimeException;

class Logger implements LoggerInterface
{
    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;

        $directory = dirname($logFile);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException(
                    "Unable to create log directory: $directory"
                );
            }
        }
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context=[]) :void{
        $this->log('ERROR',$message,$context);
    }


    private function log(
        string $level,
        string $message,
        array $context,
    ):void {
        $timestamp= date('Y-m-d H:i:s');

        $contextData = !empty($context)
            ? json_encode($context, JSON_INVALID_UTF8_SUBSTITUTE)
            : '';

        $logMessage="[$timestamp] [$level] $message $contextData" . PHP_EOL;

        $result = @file_put_contents(
            $this->logFile,
            $logMessage,
            FILE_APPEND | LOCK_EX
        );

        if ($result === false) {
            error_log("Failed to write to log file: {$this->logFile}");
        }
    }

}