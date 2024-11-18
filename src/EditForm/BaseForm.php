<?php

namespace Mollsoft\Telegram\EditForm;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mollsoft\Telegram\Exceptions\RedirectException;
use Mollsoft\Telegram\TelegramRequest;

abstract class BaseForm
{
    protected readonly TelegramRequest $request;
    /** @var Collection<FormField> $fields */
    protected Collection $fields;
    protected ?FormField $current = null;
    protected ?FormField $previous = null;
    protected ?FormField $next = null;
    protected bool $inputReceived = false;
    protected bool $isCreate = false;

    public function __construct(TelegramRequest $request)
    {
        $this->request = $request;

        $this->init();
    }

    protected function init(): void
    {
        $this->fields = collect();

        $titles = $this->titles();
        $optional = $this->optional();
        $defaults = $this->defaults();

        foreach( array_keys($this->rules()) as $name ) {
            $this->fields->push(
                new FormField(
                    name: $name,
                    title: $titles[$name] ?? $name,
                    optional: in_array($name, $optional),
                    default: $defaults[$name] ?? null,
                    value: $this->request->query($name),
                    error: null
                )
            );
        }

        $currentName = $this->request->query('_current');
        if( $currentName ) {
            $this->currentByName($currentName);
        }
    }

    public function changeCurrent(string $currentName, array $extra = [], ?array $with = null): void
    {
        $this->currentByName($currentName);

        $redirect = redirect(
            $this->request->fullUrlWithQuery([
                '_current' => $this->current?->name,
                ...$this->fields
                    ->whereNotNull('value')
                    ->pluck('value', 'name')
                    ->all(),
                ...$extra,
            ])
        );

        foreach( $with ?? [] as $key => $value ) {
            $redirect->with($key, $value);
        }

        throw new RedirectException($redirect);
    }

    protected function currentByName(string $currentName): ?FormField
    {
        $index = $this->fields->search(fn(FormField $item) => $item->name === $currentName);
        $this->current = $index !== false ? $this->fields->get($index) : null;
        $this->previous = $index !== false ? $this->fields->get($index - 1) : null;
        $this->next = $index !== false ? $this->fields->get($index + 1) : null;

        return $this->current;
    }

    protected function inputParse(string $attribute, TelegramRequest $request): ?string
    {
        return $request->text() ?? $request->post('value');
    }

    public abstract function rules(): array;

    public function optional(): array
    {
        return [];
    }

    public function titles(): array
    {
        return [];
    }

    public function defaults(): array
    {
        return [];
    }

    public function isCreate(): static
    {
        $this->isCreate = true;

        return $this;
    }

    public function setDefault(array $defaults): static
    {
        foreach( $defaults as $name => $value ) {
            if( $field = $this->fields->firstWhere('name', $name) ) {
                $field->default = $value;
            }
        }

        return $this;
    }

    /** @return Collection<FormField> */
    public function fields(): Collection
    {
        return $this->fields;
    }

    public function current(): ?FormField
    {
        return $this->current;
    }

    public function previous(): ?FormField
    {
        return $this->previous;
    }

    public function next(): ?FormField
    {
        return $this->next;
    }

    public function validate(): bool
    {
        if( $current = $this->request->post('current') ) {
            $this->changeCurrent($current);
        }
        $isSubmit = !!$this->request->post('submit');

        if( $this->fields->count() === 0 ) {
            return $isSubmit;
        }

        if( !$this->current ) {
            $this->currentByName(
                $this->fields->first()->name
            );
        }

        $formData = $this->fields
            ->mapWithKeys(fn(FormField $field) => [
                $field->name => $field->value ?? $field->default,
            ])
            ->all();

        $inputData = $this->inputParse($this->current->name, $this->request);
        $this->inputReceived = $inputData || $inputData === '0';
        if ($this->inputReceived) {
            $value = $inputData === 'NULL' || $inputData === '/empty' ? '' : $inputData;
            $this->current->value = $value;
            $formData[$this->current->name] = $value;
        }

        try {
            Validator::validate($formData, $this->rules());

            if( $this->inputReceived ) {
                if( $this->isCreate ) {
                    if( $this->next ) {
                        $this->changeCurrent($this->next->name);
                    }
                    else {
                        $isSubmit = true;
                    }
                }
                else {
                    $this->changeCurrent($this->current->name, [], [
                        'success_form' => 'Значение принято!'
                    ]);
                }
            }
        } catch (ValidationException $e) {
            $errors = $e->errors();

            foreach( $errors as $name => $items ) {
                $field = $this->fields->firstWhere('name', $name);
                if( $field ) {
                    $field->error = $items[0];
                }
            }

            if( $this->inputReceived ) {
                $this->current->error = $errors[$this->current->name][0] ?? null;

                if( !$this->current->error ) {
                    if( $this->isCreate ) {
                        if( $this->next ) {
                            $this->changeCurrent($this->next->name);
                        }
                        else {
                            $attribute = array_key_first($errors);
                            $this->changeCurrent($attribute);
                        }
                    }
                    else {
                        $this->changeCurrent($this->current->name, [], [
                            'success_form' => 'Значение принято!'
                        ]);
                    }
                }
            }

            if( $isSubmit ) {
                $attribute = array_key_first($errors);
                $error = $errors[$attribute][0];

                $this->changeCurrent($attribute, [], [
                    'error_form' => $error,
                ]);
            }

            return false;
        }

        return $isSubmit;
    }

    public function get(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->fields
                ->whereNotNull('value')
                ->mapWithKeys(fn(FormField $field) => [$field->name => $field->value !== '' ? $field->value : null])
                ->all();
        }

        return $this->fields->firstWhere('name', $key)?->value ?? $default;
    }

    public function all(): array
    {
        return $this->fields
            ->mapWithKeys(fn(FormField $field) => [$field->name => $field->value ?? $field->default])
            ->all();
    }

    public function only(array $keys): array
    {
        return $this->fields
            ->mapWithKeys(fn(FormField $field) => [$field->name => $field->value ?? $field->default])
            ->only($keys)
            ->all();
    }

    public function inputReceived(): bool
    {
        return $this->inputReceived;
    }
}
