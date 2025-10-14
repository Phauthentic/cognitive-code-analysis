<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Result;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generic result object for handler operations.
 * Provides consistent success/failure handling across all command handlers.
 */
class OperationResult
{
    private function __construct(
        private readonly bool $success,
        private readonly mixed $data = null,
        private readonly string $errorMessage = ''
    ) {
    }

    /**
     * Create a successful result with optional data.
     */
    public static function success(mixed $data = null): self
    {
        return new self(true, $data);
    }

    /**
     * Create a failed result with error message.
     */
    public static function failure(string $errorMessage): self
    {
        return new self(false, null, $errorMessage);
    }

    /**
     * Check if the operation was successful.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the operation failed.
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Get the data from the operation (only available on success).
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Get the error message (only available on failure).
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Convert the result to a command status code.
     * Outputs error message if failed, returns appropriate status code.
     */
    public function toCommandStatus(OutputInterface $output): int
    {
        if ($this->isFailure()) {
            $output->writeln('<error>' . $this->errorMessage . '</error>');
            return 1; // Command::FAILURE
        }

        return 0; // Command::SUCCESS
    }
}
