<?php
/**
 * Sumo Logic via syslog handler for Monolog
 *
 * Handles all features of Sumo Logic via syslog handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.14.0
 */

namespace Decalog\Handler;

use DateTimeInterface;
use Monolog\Logger;
use Monolog\Handler\SocketHandler;
use Monolog\Handler\AbstractSyslogHandler;



/**
 * Define the Monolog Sumo Logic via syslog handler.
 *
 * Handles all features of Sumo Logic via syslog handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.14.0
 */
class SumoSysHandler extends AbstractSyslogHandler
{
    const RFC3164 = 0;
    const RFC5424 = 1;

    private $dateFormats = array(
        self::RFC3164 => 'M d H:i:s',
        self::RFC5424 => \DateTime::RFC3339,
    );

    protected $socket;
	protected $token;
    protected $ident;
    protected $rfc;

    /**
     * @param string     $host
     * @param int        $port
     * @param string|int $facility Either one of the names of the keys in $this->facilities, or a LOG_* facility constant
     * @param string|int $level    The minimum logging level at which this handler will be triggered
     * @param bool       $bubble   Whether the messages that are handled can bubble up the stack or not
     * @param string     $ident    Program name or tag for each log message.
     * @param int        $rfc      RFC to format the message for.
     */
    public function __construct(string $host, int $port, int $timeout, string $token, $facility = LOG_USER, $level = Logger::DEBUG, bool $bubble = true, string $ident = 'DecaLog', int $rfc = self::RFC5424)
    {
        parent::__construct( $facility, $level, $bubble );

        $this->token = $token;
	    $this->ident = $ident;
        $this->rfc   = $rfc;

        $this->socket = new SocketHandler('tls://' . $host . ':' . $port);
	    $this->socket->setPersistent( true );
	    $this->socket->setConnectionTimeout( $timeout );
    }

    protected function write(array $record): void
    {
        $lines = $this->splitMessageIntoLines($record['formatted']);

        $header = $this->makeCommonSyslogHeader($this->logLevels[$record['level']], $record['datetime']);

        foreach ($lines as $line) {
        	$line=str_replace('[]', '[test3="ccc"]', $line);
            $this->socket->write([ 'formatted' => $header . $line . ' [test3="ccc"] ']);
	        //error_log($header . $line . ' [test3="ccc"] ');
        }

    }

    public function close(): void
    {
        $this->socket->close();
    }

    private function splitMessageIntoLines($message): array
    {
        if (is_array($message)) {
            $message = implode("\n", $message);
        }

        return preg_split('/$\R?^/m', (string) $message, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Make common syslog header (see rfc5424 or rfc3164)
     */
    protected function makeCommonSyslogHeader(int $severity, DateTimeInterface $datetime): string
    {
        $priority = $severity + $this->facility;

        if (!$pid = getmypid()) {
            $pid = '-';
        }

        if (!$hostname = gethostname()) {
            $hostname = '-';
        }

        if ($this->rfc === self::RFC3164) {
            $datetime->setTimezone(new \DateTimeZone('UTC'));
        }
        $date = $datetime->format($this->dateFormats[$this->rfc]);

       /* if ($this->rfc === self::RFC3164) {
            return "<$priority>" .
                $date . " " .
                $hostname . " " .
                $this->ident . "[" . $pid . "]: ";
        } else {
            return "<$priority>1 " .
                $date . " " .
                $hostname . " " .
                $this->ident . " " .
                $pid . " - - ";
        }*/

		return "<$priority>1 $date $hostname $this->ident $pid ID47 [$this->token test1=\"aaa\" test2=\"bbb\"] ";

    }
}
