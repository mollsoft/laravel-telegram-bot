<?php

namespace Mollsoft\Telegram\Foundation;

abstract class BaseService
{
    protected ?\Closure $logger = null;

    public function setLogger(?\Closure $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    protected function log(string|array $message, string $type = 'info'): void
    {
        if ($this->logger) {
            if (is_array($message)) {
                $message = implode("\n", $message);
            }

            call_user_func($this->logger, $message, $type);
        }
    }

    protected function error(string|array $message): void
    {
        $this->log($message, 'error');
    }

    protected function success(string|array $message): void
    {
        $this->log($message, 'success');
    }

    protected function info(string|array $message): void
    {
        $this->log($message);
    }
}
