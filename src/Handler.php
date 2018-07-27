<?php declare(strict_types=1);
/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Monolog\Handler;
use Monolog\Logger;
use Monolog\Handler\SyslogUdp\UdpSocket;
/**
 * A Handler for logging to a remote syslogd server.
 *
 * @author Jesper Skovgaard Nielsen <nulpunkt@gmail.com>
 */
class Handler extends AbstractProcessingHandler
{
    protected $socket;
    private $recordBuffer = [];
    private $recordBufferMaxSize = 10;

    /**
     * @param string $host
     * @param int    $port
     * @param int    $level                 The minimum logging level at which this handler will be triggered
     * @param bool   $bubble                Whether the messages that are handled can bubble up the stack or not
     * @param int    $recordBufferMaxSize   Max size of record buffer
     */
    public function __construct(
        $host,
        $port = 514,
        $level = Logger::DEBUG,
        bool $bubble = true,
        $recordBufferMaxSize = 10
    )
    {
        parent::__construct($level, $bubble);
        $this->socket = new UdpSocket($host, $port ?: 514);
        $this->recordBufferMaxSize = $recordBufferMaxSize;
    }

    protected function write(array $record, $flushAll = false): void
    {
        if (count($record) > 0) {
            $this->recordBuffer[] = $record;
        }
        if (!$flushAll && count($this->recordBuffer) < $this->recordBufferMaxSize) {
            return;
        }

        $logContent = '';
        foreach ($this->recordBuffer as $record) {
            $logContent .= $record['formatted'];
        }
        if ($logContent) {
            $this->socket->write($logContent);
        }

        $this->recordBuffer = [];
    }

    public function close(): void
    {
        if (count($this->recordBuffer) > 0) {
            $this->write([], true);
        }

        $this->socket->close();
    }

    /**
     * Inject your own socket, mainly used for testing
     */
    public function setSocket(UdpSocket $socket)
    {
        $this->socket = $socket;
    }
}
