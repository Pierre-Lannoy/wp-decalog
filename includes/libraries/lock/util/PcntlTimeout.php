<?php

declare(strict_types=1);

namespace malkusch\lock\util;

use InvalidArgumentException;
use malkusch\lock\exception\DeadlineException;
use malkusch\lock\exception\LockAcquireException;
use RuntimeException;

/**
 * Timeout based on a scheduled alarm.
 *
 * This class requires the pcntl module.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @internal
 */
final class PcntlTimeout
{
    /**
     * @var int Timeout in seconds
     */
    private $timeout;

    /**
     * Builds the timeout.
     *
     * @param int $timeout Timeout in seconds.
     * @throws \RuntimeException When the PCNTL module is not enabled.
     * @throws \InvalidArgumentException When the timeout is zero or negative.
     */
    public function __construct(int $timeout)
    {
        if (!self::isSupported()) {
            throw new RuntimeException('PCNTL module not enabled');
        }

        if ($timeout <= 0) {
            throw new InvalidArgumentException(
                'Timeout must be positive and non zero'
            );
        }

        $this->timeout = $timeout;
    }

    /**
     * Runs the code and would eventually time out.
     *
     * This method has the side effect, that any signal handler for SIGALRM will
     * be reset to the default handler (SIG_DFL). It also expects that there is
     * no previously scheduled alarm. If your application uses alarms
     * ({@link pcntl_alarm()}) or a signal handler for SIGALRM, don't use this
     * method. It will interfere with your application and lead to unexpected
     * behaviour.
     *
     * @param  callable $code Executed code block
     * @throws \malkusch\lock\exception\DeadlineException Running the code hit
     * the deadline.
     * @throws \malkusch\lock\exception\LockAcquireException Installing the
     * timeout failed.
     * @return mixed Return value of the executed block
     */
    public function timeBoxed(callable $code)
    {
        $existingHandler = pcntl_signal_get_handler(SIGALRM);

        $signal = pcntl_signal(SIGALRM, function (): void {
            throw new DeadlineException(sprintf(
                'Timebox hit deadline of %d seconds',
                $this->timeout
            ));
        });
        if (!$signal) {
            throw new LockAcquireException('Could not install signal');
        }

        $oldAlarm = pcntl_alarm($this->timeout);
        if ($oldAlarm != 0) {
            throw new LockAcquireException('Existing alarm was not expected');
        }

        try {
            return $code();
        } finally {
            pcntl_alarm(0);
            pcntl_signal_dispatch();
            pcntl_signal(SIGALRM, $existingHandler);
        }
    }

    /**
     * Returns if this class is supported by the PHP runtime.
     *
     * This class requires the pcntl module. This method checks if
     * it is available.
     *
     * @return bool TRUE if this class is supported by the PHP runtime.
     */
    public static function isSupported(): bool
    {
        return
            PHP_SAPI === 'cli' &&
            extension_loaded('pcntl') &&
            function_exists('pcntl_alarm') &&
            function_exists('pcntl_signal') &&
            function_exists('pcntl_signal_dispatch');
    }
}
