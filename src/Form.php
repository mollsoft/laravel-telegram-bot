<?php

namespace Mollsoft\Telegram;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mollsoft\Telegram\Exceptions\RedirectException;
use Mollsoft\Telegram\Foundation\TelegramRequest;

class Form
{
    protected bool $validated = false;
    protected ?string $current = null;
    protected ?string $error = null;
    protected array $attributes = [];
    protected mixed $inputValue = null;

    public function __construct(public readonly TelegramRequest $request)
    {
        $this->current = $this->request->query('_current');
        $this->init();
    }

    public function init(): void
    {
    }

    public function rules(): array
    {
        return [];
    }

    protected function changeCurrent(string $current, array $extra = []): void
    {
        $this->current = $current;

        throw new RedirectException(
            to_route(
                Route::currentRouteName(),
                [
                    ...request()->route()->parameters(),
                    ...request()->query(),
                    '_current' => $this->current,
                    ...$this->attributes,
                    ...$extra
                ]
            )
        );
    }

    public function parseInput(string $attribute, TelegramRequest $request): ?string
    {
        return $request->text() ?? $request->post('value');
    }

    public function validate(array $rules = []): bool
    {
        $this->validated = false;

        // Собираем правила из класса и параметров в один массив
        $rules = [
            ...$this->rules(),
            ...$rules
        ];
        $rulesKeys = array_keys($rules);
        $currentIndex = array_search($this->current, $rulesKeys);

        // Заполняем массив attributes значениями полей из Query
        $this->attributes = [];
        foreach ($this->request->query() as $key => $value) {
            if (isset($rules[$key])) {
                $this->attributes[$key] = $value;
            }
        }

        // Если текущий аттрибут не определен, устанавливаем первый
        if (!isset($rules[$this->current])) {
            $this->current = array_key_first($rules);
        }

        // Обнуляем текст ошибки
        $this->error = null;

        // Если входящее сообщение не получено, останавливаем
        $this->inputValue = $this->parseInput($this->current, $this->request);

        // Устанавливаем в текущий аттрибут значение
        if ($this->inputValue) {
            $this->attributes[$this->current] = $this->inputValue === 'NULL' ? '' : $this->inputValue;
        }

        // Валидация формы
        try {
            $this->attributes = Validator::validate($this->attributes, $rules);
        } catch (ValidationException $e) {
            if (!$this->inputValue) {
                return false;
            }

            $errorAttribute = array_key_first($e->errors());
            $errorMessage = $e->errors()[$errorAttribute][0];

            // Если ошибка в текущем аттрибуте - печатаем ошибку
            if ($errorAttribute === $this->current) {
                $this->error = $errorMessage;
                return false;
            }

            // Если ошибка в аттрибуте до текущего - то переключаемся на него
            foreach (array_slice($rulesKeys, 0, $currentIndex) as $key) {
                if ($errorAttribute === $key) {
                    $this->changeCurrent($errorAttribute);
                }
            }

            $this->changeCurrent($rulesKeys[$currentIndex + 1]);
        }

        if (isset($rulesKeys[$currentIndex + 1])) {
            $this->changeCurrent($rulesKeys[$currentIndex + 1]);
        }

        $this->validated = true;

        return true;
    }

    public function current(): ?string
    {
        return $this->current;
    }

    public function error(): ?string
    {
        return $this->error;
    }

    public function validated(): bool
    {
        return $this->validated;
    }

    public function get(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->attributes;
        }

        return $this->attributes[$key] ?? $default;
    }

    public function only(array $keys): array
    {
        return Arr::only($this->attributes, $keys);
    }
}
