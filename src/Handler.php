<?php declare(strict_types=1);

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Lxj\Monolog\Udp;
use Monolog\Handler\AbstractProcessingHandler;
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
    private $recordBufferFormatter;

    /**
     * @param string $host
     * @param int    $port
     * @param int    $level                 The minimum logging level at which this handler will be triggered
     * @param bool   $bubble                Whether the messages that are handled can bubble up the stack or not
     * @param int    $recordBufferMaxSize   Max size of record buffer
     * @param \Closure|null $recordBufferFormatter Record buffer formatter
     */
    public function __construct(
        $host,
        $port = 514,
        $level = Logger::DEBUG,
        $bubble = true,
        $recordBufferMaxSize = 10,
        $recordBufferFormatter = null
    )
    {
        parent::__construct($level, $bubble);
        $this->socket = new UdpSocket($host, $port ?: 514);
        $this->recordBufferMaxSize = $recordBufferMaxSize;
        $this->recordBufferFormatter = $recordBufferFormatter;
    }

    protected function write(array $record, $flushAll = false)
    {
        if (count($record) > 0) {
            $this->recordBuffer[] = $record;
        }
        if (!$flushAll && count($this->recordBuffer) < $this->recordBufferMaxSize) {
            return;
        }

        $logContent = '';
        if (!$this->recordBufferFormatter) {
            foreach ($this->recordBuffer as $record) {
                $logContent .= $record['formatted'];
            }
        } else {
            $recordBuffer = [];
            foreach ($this->recordBuffer as $record) {
                $recordBuffer[] = $record['formatted'];
            }
            $logContent = call_user_func_array($this->recordBufferFormatter, ['recordBuffer' => $recordBuffer]);
        }
        if ($logContent) {
            $this->socket->write($logContent);
        }

        $this->recordBuffer = [];
    }

    public function close()
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
