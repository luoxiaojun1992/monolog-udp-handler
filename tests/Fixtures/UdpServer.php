<?php

$serv = new swoole_server('127.0.0.1', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);

$serv->set(array(
    'daemonize' => 1,
    'pid_file' => __DIR__.'/server.pid',
));

//监听数据接收事件
$serv->on('Packet', function ($serv, $data, $clientInfo) {
    file_put_contents(__DIR__ . '/logs.txt', $data, FILE_APPEND);
});

//启动服务器
$serv->start();
