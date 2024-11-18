<table style="width: 100%; padding: 20px;">
    <tr>
        <td>
            <table
                style="max-width: 800px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                <tr>
                    <td style="text-align: center; padding-bottom: 30px;">
                        <h1 style="font-size: 32px; color: #00A761; font-family: 'Open Sans', Arial, sans-serif;">Процес
                            конвертування успішно завершено!</h1>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p style="font-size: 18px; color: #374151; font-family: 'Open Sans', Arial, sans-serif;">Шановний
                            користувачу,</p>
                        <p style="font-size: 18px; color: #374151; font-family: 'Open Sans', Arial, sans-serif;">Раді
                            повідомити, що процес конвертування вашої реляційної бази даних у нереляційну було успішно
                            завершено.</p>
                        <p style="font-size: 18px; color: #374151; font-family: 'Open Sans', Arial, sans-serif;">Деталі:
                        </p>
                        <ul style="font-size: 18px; color: #374151; font-family: 'Open Sans', Arial, sans-serif;">
                            <li><strong style="color: #005073;">База даних SQL:</strong>
                                {{ $convert->sqlDatabase->database }}</li>
                            <li><strong style="color: #005073;">База даних MongoDB:</strong>
                                {{ $convert->mongoDatabase->database }}</li>
                            @if ($convert->description)
                                <li><strong style="color: #005073;">Опис:</strong> {{ $convert->description }}</li>
                            @endif
                        </ul>
                        <p style="font-size: 18px; color: #374151; font-family: 'Open Sans', Arial, sans-serif;">Вітаємо
                            з успішним завершенням процесу конвертування.</p>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center; padding-top: 30px;">
                        <p style="font-size: 16px; color: #888; font-family: 'Open Sans', Arial, sans-serif;">З
                            найкращими побажаннями,</p>
                        <p style="font-size: 16px; color: #888; font-family: 'Open Sans', Arial, sans-serif;">
                            {{ config('app.name') }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
