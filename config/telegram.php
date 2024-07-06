<?php

use Mollsoft\Telegram\DTO\BotCommand;

return [
    'init' => [
        'default' => [
            'commands' => [
                BotCommand::create('start', 'Главное меню'),
                BotCommand::create('refresh', 'Обновить'),
                BotCommand::create('back', 'Назад'),
            ]
        ]
    ],
    'page' => [
        'timeout' => 60, // таймаут в секундах на обработку запроса
        'wait' => 5, // время ожидания завершения предыдущего запроса
        'delay' => 2, // задержка после обработки запроса
        'max_redirects' => 3, // максимальное количество редиректов
    ]
];
