# Laravel Telegram Bot

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mollsoft/laravel-telegram-bot.svg?style=flat-square)](https://packagist.org/packages/mollsoft/laravel-telegram-bot)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mollsoft/laravel-telegram-bot/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mollsoft/laravel-telegram-bot/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mollsoft/laravel-telegram-bot/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mollsoft/laravel-telegram-bot/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mollsoft/laravel-telegram-bot.svg?style=flat-square)](https://packagist.org/packages/mollsoft/laravel-telegram-bot)

EN: This package for Laravel 11+ allows you to easily create interactive Telegram bots, using Laravel routing, and using Blade templates to conduct a dialogue with the user.

RU: Этот пакет для Laravel 11+ позволяет с легкостью создавать интерактивные Telegram боты, при чем использовать маршрутизацию Laravel, а для ведения диалога с пользователем - использовать Blade шаблоны.

## Installation / Установка

You can install the package via composer:

Используйте менеджер пакетов Composer для установки пакета:

```bash
composer require mollsoft/laravel-telegram-bot
```

You can publish and run the migrations with:

Вы можете опубликовать и запустить миграции:

```bash
php artisan vendor:publish --tag="laravel-telegram-bot-migrations"
php artisan migrate
```

You can publish the config file with:

Вы можете опубликовать конфигурационные файлы командой:

```bash
php artisan vendor:publish --tag="laravel-telegram-bot-config"
```

This is the contents of the published config file:

Это содержимое конфигурационного файла:

```php
use \Telegram\DTO\BotCommand;

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
```

Optionally, you can publish the views using:

Опционально, Вы можете опубликовать шаблоны командой:

```bash
php artisan vendor:publish --tag=":package_slug-views"
```

## Usage / Использование

```php
$variable = new VendorName\Skeleton();
echo $variable->echoPhrase('Hello, VendorName!');
```

## Testing / Тестирование

```bash
composer test
```

## Changelog / Логи изменений

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

Пожалуйста смотрите [CHANGELOG](CHANGELOG.md) для получения подробной информации об изменениях.

## Credits / Авторы

- [MollSoft](https://github.com/mollsoft)

## License / Лицензия

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

Лицензия MIT (MIT). Дополнительную информацию см. в [Файле лицензии](LICENSE.md).
