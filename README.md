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

```bash
php artisan telegram:install
```

You can publish and run the migrations with:

Вы можете опубликовать и запустить миграции:

```bash
php artisan vendor:publish --tag="telegram-migrations"
php artisan migrate
```

You can publish the config file with:

Вы можете опубликовать конфигурационные файлы командой:

```bash
php artisan vendor:publish --tag="telegram-config"
```

Optionally, you can publish the views using:

Опционально, Вы можете опубликовать шаблоны командой:

```bash
php artisan vendor:publish --tag="telegram-views"
```

Optionally, if you use Sail for local development, you need add PHP params `PHP_CLI_SERVER_WORKERS="10"` in file `supervisord.conf`:
```bash
[program:php]
command=%(ENV_SUPERVISOR_PHP_COMMAND)s
user=%(ENV_SUPERVISOR_PHP_USER)s
environment=LARAVEL_SAIL="1",PHP_CLI_SERVER_WORKERS="10"
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

You can use Laravel Auth, edit file `config/auth.php` and edit section `guards`:
```php
'guards' => [
        'web' => [...],
        'telegram' => [
            'driver' => 'telegram',
            'provider' => 'users',
        ]
    ],
```

After this you can use middleware `auth:telegram` in your routes.

If you want work with automatic truncate dialogs, you must run command `php artisan telegram:truncate` every minute using Schedule.

Вы можете настроить "живые страницы", для этого в файле `bootstrap/app.php` в раздел `withMiddleware` добавьте aliase:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'telegram.live' => \Mollsoft\Telegram\Middleware\LiveMiddleware::class,
    ]);
})
```

После чего в нужном маршруте подключите middleware:
```php
Route::telegram('/', [\App\Telegram\Controllers\MyController::class, 'index'])
    ->middleware(['telegram.live:30']);
```

Аргумент - частота в секундах, как часто обновлять страницу.

А в файл `routes/console.php` добавить:
```php
Schedule::command('telegram:live')
    ->runInBackground()
    ->everyMinute();
```

## Usage / Использование

Create new Telegram Bot:

```php
php artisan telegram:new-bot
```


Set Webhook for bot:

```php
php artisan telegram:set-webhook
```


Unset Webhook for bot:

```php
php artisan telegram:unset-webhook
```


Manual pooling (on localhost) for bot:

```php
php artisan telegram:pooling [BOT_ID]
```


### Inline Keyboard

If you want create button for change current URI query params, use this template:

```html
<inline-keyboard>
    <row>
        <column query-param="value">Change query param</column>
    </row>
</inline-keyboard>
```

If you want send POST data you must use this template:

```html
<inline-keyboard>
    <row>
        <column data-field="value">Send field value</column>
    </row>
</inline-keyboard>
```

If you POST data is long, you can encrypt using this template:

```html
<inline-keyboard>
    <row>
        <column data-field="long value" encode="true">Encoded send data</column>
    </row>
</inline-keyboard>
```

If you want make redirect to another page from button, use this template:

```html
<inline-keyboard>
    <row>
        <column data-redirect="/">Redirect to /</column>
    </row>
</inline-keyboard>
```

### Edit Form / Форма для редактирования данных

```php
class MyForm extends \Mollsoft\Telegram\EditForm\BaseForm 
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:5', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:15'],
        ];
    }
    
    public function titles(): array
    {
        return [
            'name' => 'Ваше имя',
            'phone' => 'Ваш номер телефона'
        ];
    }
}
```

```php
class MyController 
{
    public function edit(MyForm $form): mixed
    {
        $form->setDefault([
            'name' => 'Default name',
            'phone' => '1234567890',
        ]);
        
        if( $form->validate() ) {
            // $form->get();
        }
        
        return view('...', compact('form'));
    }
    
    public function create(MyForm $form): mixed
    {
        if( $form->isCreate()->validate() ) {
            // $form->get();
        }
        
        return view('...', compact('form'));
    }
}
```

```html
<message>
    <x-telegram-edit-form :form="$form">
        <x-slot:name>
            <line>Please, enter your First Name:</line>
        </x-slot:name>
    </x-telegram-edit-form>
</message>
```

## Testing / Тестирование

```bash
composer test
```

## Ideas / Идеи

1. В Inline Button сделать параметр `query-history=false` что бы по нему текущий URL не сохранялся в referer и при back не выполнялся сброс формы - а был возврат назад.
2. Возможность загрузки пользователями фото/видео/документы и парсинг capture в message.
3. В Reply Button сделать кнопку отправки номера телефона + получение результатов в TelegramRequest.
4. Чтение результата пересланного контакта в TelegramRequest.

## Changelog / Логи изменений

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

Пожалуйста смотрите [CHANGELOG](CHANGELOG.md) для получения подробной информации об изменениях.

## Credits / Авторы

- [MollSoft](https://github.com/mollsoft)

## License / Лицензия

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

Лицензия MIT (MIT). Дополнительную информацию см. в [Файле лицензии](LICENSE.md).
