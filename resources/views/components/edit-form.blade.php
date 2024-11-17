@php
    /** @var \Mollsoft\Telegram\EditForm\BaseForm $form */
@endphp

<message>
    {{ $header ?? '' }}
    @foreach($form->fields() as $i => $field)
        <line>{{ $form->current()->name === $field->name ? '→ ' : '' }}{{ $field->error ? '🔴' : ($field->value !== null ? '✅' : ($field->default !== null ? '🟢' : '⚪')) }} <i>{{ $field->title }}:</i> <code>{{ $form->get($field->name, $field->default) === '' || $form->get($field->name, $field->default) === null ? '-' : $form->get($field->name, $field->default) }}</code></line>
    @endforeach
    <line></line>
    <inline-keyboard>
        <row>
            <column data-current="{{ $form->previous()?->name }}">👈 Назад</column>
            <column data-current="{{ $form->next()?->name }}">Вперед 👉</column>
        </row>
        <row>
            <column data-submit="true">🏁 Отправить форму</column>
        </row>
    </inline-keyboard>
</message>

@if( session('error_form') )
    <message>
        <line>❌ Ошибка: <code>{!! session('error_form') !!}</code></line>
    </message>
@endif

@if( session('success_form') )
    <message>
        <line>✅ <code>{!! session('success_form') !!}</code></line>
    </message>
@elseif( $form->current() )
    <message>
        @if( ${$form->current()->name} ?? null )
            {{ ${$form->current()->name} }}
        @else
            <line>✍️ Напишите <b>{{ $form->current()->title }}</b>{{ $form->current()->optional ? ' или /empty' : '' }}:</line>
        @endif
    </message>
@endif
