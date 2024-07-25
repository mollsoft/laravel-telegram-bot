<?php

use Mollsoft\Telegram\DTO\BotCommand;

return [
    'init' => [
        'default' => [
            // 'name' => 'Название бота',
            // 'description' => 'Описание бота',
            // 'short_description' => 'Короткое описание бота',
            'commands' => [
                BotCommand::create('start', 'Главное меню'),
                BotCommand::create('refresh', 'Обновить'),
                BotCommand::create('back', 'Назад'),
            ],
        ]
    ],
    'reactions' => [
        'start' => ['/start'],
        'home' => ['🏠', 'Ⓜ️', '/home'],
        'back' => ['⬅️', '🔙', '/back'],
        'refresh' => ['🔄', '/refresh'],
    ],
    'page' => [
        'timeout' => 60, // таймаут в секундах на обработку запроса
        'wait' => 5, // время ожидания завершения предыдущего запроса
        'delay' => 2, // задержка после обработки запроса
        'max_redirects' => 3, // максимальное количество редиректов
    ],
    'webhook' => [
        'background' => false,
        // запускать обработчик webhook в фоновом режиме (позволяет не создавать очередь в Telegram, требует proc_open).
    ],
    'cache' => [
        'ttl' => 86400,
        'encode_ttl' => 3 * 24 * 60 * 60,
    ],
    'models' => [
        'bot' => \Mollsoft\Telegram\Models\TelegramBot::class,
        'chat' => \Mollsoft\Telegram\Models\TelegramChat::class,
        'user' => \Mollsoft\Telegram\Models\TelegramUser::class,
    ]
];
