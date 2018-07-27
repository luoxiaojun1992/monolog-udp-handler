<?php

class HandlerTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        parent::setUp();

        shell_exec('`which php` ' . __DIR__ . '/Fixtures/UdpServer.php &');

        usleep(10000);
    }

    public function testLogSuccess()
    {
        $handler = new \Lxj\Monolog\Udp\Handler(
            '127.0.0.1',
            9501,
            \Monolog\Logger::DEBUG,
            true,
            5
        );
        $formatter = new \Monolog\Formatter\LineFormatter("%message%\n");
        $handler->setFormatter($formatter);
        $monolog = new \Monolog\Logger('test');
        $monolog->pushHandler($handler);

        for ($i = 0; $i < 10; ++$i) {
            $monolog->info('test');
        }

        usleep(500000);

        $expected = <<<EOF
test
test
test
test
test
test
test
test
test
test

EOF;
        $this->assertEquals($expected, file_get_contents(__DIR__ . '/Fixtures/logs.txt'));
    }

    public function tearDown()
    {
        parent::tearDown();

        unlink(__DIR__ . '/Fixtures/logs.txt');

        shell_exec('kill ' . file_get_contents(__DIR__ . '/Fixtures/server.pid'));
        shell_exec('kill ' . file_get_contents(__DIR__ . '/Fixtures/server.pid'));
    }
}
