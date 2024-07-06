<message>
    <line>⚠️ Техническая ошибка ⚠️</line>
    <line>При обработке Вашего запроса возникла техническая ошибка.</line>
    <line>Пожалуйста, повторите запрос позже!</line>
</message>

@if( isset( $exception ) && $exception instanceof Exception )
<message>
    <line><code>{{ $exception->getMessage() }}</code></line>
</message>
@endif
