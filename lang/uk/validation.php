<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Поле :attribute має бути прийнято.',
    'accepted_if' => 'Поле :attribute має бути прийнято, коли :other є :value.',
    'active_url' => 'Поле :attribute має бути дійсним URL.',
    'after' => 'Поле :attribute має бути датою після :date.',
    'after_or_equal' => 'Поле :attribute має бути датою після або рівною :date.',
    'alpha' => 'Поле :attribute може містити лише літери.',
    'alpha_dash' => 'Поле :attribute може містити лише літери, цифри, тире та підкреслення.',
    'alpha_num' => 'Поле :attribute може містити лише літери та цифри.',
    'array' => 'Поле :attribute має бути масивом.',
    'ascii' => 'Поле :attribute має містити лише однобайтові буквено-цифрові символи та символи.',
    'before' => 'Поле :attribute має бути датою до :date.',
    'before_or_equal' => 'Поле :attribute має бути датою до або рівною :date.',
    'between' => [
        'array' => 'Поле :attribute має містити від :min до :max елементів.',
        'file' => 'Поле :attribute має бути від :min до :max кілобайт.',
        'numeric' => 'Поле :attribute має бути між :min і :max.',
        'string' => 'Поле :attribute має містити від :min до :max символів.',
    ],
    'boolean' => 'Поле :attribute має бути true або false.',
    'can' => 'Поле :attribute містить несанкціоноване значення.',
    'confirmed' => 'Підтвердження поля :attribute не збігається.',
    'contains' => 'Поле :attribute містить відсутнє обов’язкове значення.',
    'current_password' => 'Пароль невірний.',
    'date' => 'Поле :attribute має бути дійсною датою.',
    'date_equals' => 'Поле :attribute має бути датою, рівною :date.',
    'date_format' => 'Поле :attribute має відповідати формату :format.',
    'decimal' => 'Поле :attribute має містити :decimal десяткових знаків.',
    'declined' => 'Поле :attribute має бути відхилено.',
    'declined_if' => 'Поле :attribute має бути відхилено, коли :other є :value.',
    'different' => 'Поле :attribute і :other мають бути різними.',
    'digits' => 'Поле :attribute має містити :digits цифр.',
    'digits_between' => 'Поле :attribute має містити від :min до :max цифр.',
    'dimensions' => 'Поле :attribute має недійсні розміри зображення.',
    'distinct' => 'Поле :attribute містить повторюване значення.',
    'doesnt_end_with' => 'Поле :attribute не повинно закінчуватися одним із наступних: :values.',
    'doesnt_start_with' => 'Поле :attribute не повинно починатися з одного з наступних: :values.',
    'email' => 'Поле :attribute має бути дійсною електронною адресою.',
    'ends_with' => 'Поле :attribute має закінчуватися одним із наступних: :values.',
    'enum' => 'Вибране значення для :attribute недійсне.',
    'exists' => 'Вибране значення для :attribute недійсне.',
    'extensions' => 'Поле :attribute має мати одне з наступних розширень: :values.',
    'file' => 'Поле :attribute має бути файлом.',
    'filled' => 'Поле :attribute обов’язково для заповнення.',
    'gt' => [
        'array' => 'Поле :attribute має містити більше ніж :value елементів.',
        'file' => 'Поле :attribute має бути більше ніж :value кілобайт.',
        'numeric' => 'Поле :attribute має бути більше ніж :value.',
        'string' => 'Поле :attribute має бути більше ніж :value символів.',
    ],
    'gte' => [
        'array' => 'Поле :attribute має містити не менше :value елементів.',
        'file' => 'Поле :attribute має бути більше або дорівнювати :value кілобайт.',
        'numeric' => 'Поле :attribute має бути більше або дорівнювати :value.',
        'string' => 'Поле :attribute має бути більше або дорівнювати :value символів.',
    ],
    'hex_color' => 'Поле :attribute має бути дійсним шістнадцятковим кольором.',
    'image' => 'Поле :attribute має бути зображенням.',
    'in' => 'Вибране значення для :attribute недійсне.',
    'in_array' => 'Поле :attribute має існувати в :other.',
    'integer' => 'Поле :attribute має бути цілим числом.',
    'ip' => 'Поле :attribute має бути дійсною IP адресою.',
    'ipv4' => 'Поле :attribute має бути дійсною IPv4 адресою.',
    'ipv6' => 'Поле :attribute має бути дійсною IPv6 адресою.',
    'json' => 'Поле :attribute має бути дійсним JSON рядком.',
    'list' => 'Поле :attribute має бути списком.',
    'lowercase' => 'Поле :attribute має бути в нижньому регістрі.',
    'lt' => [
        'array' => 'Поле :attribute має містити менше ніж :value елементів.',
        'file' => 'Поле :attribute має бути менше ніж :value кілобайт.',
        'numeric' => 'Поле :attribute має бути менше ніж :value.',
        'string' => 'Поле :attribute має бути менше ніж :value символів.',
    ],
    'lte' => [
        'array' => 'Поле :attribute не має містити більше ніж :value елементів.',
        'file' => 'Поле :attribute має бути менше або дорівнювати :value кілобайт.',
        'numeric' => 'Поле :attribute має бути менше або дорівнювати :value.',
        'string' => 'Поле :attribute має бути менше або дорівнювати :value символів.',
    ],
    'mac_address' => 'Поле :attribute має бути дійсною MAC адресою.',
    'max' => [
        'array' => 'Поле :attribute не має містити більше ніж :max елементів.',
        'file' => 'Поле :attribute не має бути більше ніж :max кілобайт.',
        'numeric' => 'Поле :attribute не має бути більше ніж :max.',
        'string' => 'Поле :attribute не має бути більше ніж :max символів.',
    ],
    'max_digits' => 'Поле :attribute не має містити більше ніж :max цифр.',
    'mimes' => 'Поле :attribute має бути файлом одного з типів: :values.',
    'mimetypes' => 'Поле :attribute має бути файлом одного з типів: :values.',
    'min' => [
        'array' => 'Поле :attribute має містити принаймні :min елементів.',
        'file' => 'Поле :attribute має бути не менше :min кілобайт.',
        'numeric' => 'Поле :attribute має бути не менше :min.',
        'string' => 'Поле :attribute має бути не менше :min символів.',
    ],
    'min_digits' => 'Поле :attribute має містити принаймні :min цифр.',
    'missing' => 'Поле :attribute має бути відсутнім.',
    'missing_if' => 'Поле :attribute має бути відсутнім, коли :other є :value.',
    'missing_unless' => 'Поле :attribute має бути відсутнім, якщо :other не є :value.',
    'missing_with' => 'Поле :attribute має бути відсутнім, коли присутній :values.',
    'missing_with_all' => 'Поле :attribute має бути відсутнім, коли присутні :values.',
    'multiple_of' => 'Поле :attribute має бути кратним :value.',
    'not_in' => 'Вибране значення для :attribute недійсне.',
    'not_regex' => 'Поле :attribute має неправильний формат.',
    'numeric' => 'Поле :attribute має бути числом.',
    'password' => [
        'letters' => 'Поле :attribute має містити принаймні одну літеру.',
        'mixed' => 'Поле :attribute має містити принаймні одну велику та одну малу літеру.',
        'numbers' => 'Поле :attribute має містити принаймні одну цифру.',
        'symbols' => 'Поле :attribute має містити принаймні один символ.',
        'uncompromised' => 'Задане значення :attribute було виявлено у витоку даних. Будь ласка, оберіть інше значення :attribute.',
    ],
    'present' => 'Поле :attribute має бути присутнім.',
    'present_if' => 'Поле :attribute має бути присутнім, коли :other є :value.',
    'present_unless' => 'Поле :attribute має бути присутнім, якщо :other не є :value.',
    'present_with' => 'Поле :attribute має бути присутнім, коли присутні :values.',
    'present_with_all' => 'Поле :attribute має бути присутнім, коли присутні всі значення :values.',
    'prohibited' => 'Поле :attribute заборонене.',
    'prohibited_if' => 'Поле :attribute заборонене, коли :other є :value.',
    'prohibited_unless' => 'Поле :attribute заборонене, якщо :other не є в :values.',
    'prohibits' => 'Поле :attribute забороняє присутність :other.',
    'regex' => 'Поле :attribute має неправильний формат.',
    'required' => "Поле :attribute є обов'язковим.",
    'required_array_keys' => 'Поле :attribute має містити записи для: :values.',
    'required_if' => "Поле :attribute є обов'язковим, коли :other є :value.",
    'required_if_accepted' => "Поле :attribute є обов'язковим, коли :other прийнято.",
    'required_if_declined' => "Поле :attribute є обов'язковим, коли :other відхилено.",
    'required_unless' => "Поле :attribute є обов'язковим, якщо :other не є в :values.",
    'required_with' => "Поле :attribute є обов'язковим, коли присутнє :values.",
    'required_with_all' => "Поле :attribute є обов'язковим, коли присутні всі значення :values.",
    'required_without' => "Поле :attribute є обов'язковим, коли :values відсутнє.",
    'required_without_all' => "Поле :attribute є обов'язковим, коли жодне з :values не присутнє.",
    'same' => 'Поле :attribute має збігатися з :other.',
    'size' => [
        'array' => 'Поле :attribute має містити :size елементів.',
        'file' => 'Поле :attribute має бути розміром :size кілобайт.',
        'numeric' => 'Поле :attribute має бути розміром :size.',
        'string' => 'Поле :attribute має бути довжиною :size символів.',
    ],
    'starts_with' => 'Поле :attribute має починатися з одного з наступних значень: :values.',
    'string' => 'Поле :attribute має бути рядком.',
    'timezone' => 'Поле :attribute має бути дійсним часовим поясом.',
    'unique' => 'Поле :attribute вже зайняте.',
    'uploaded' => 'Завантаження поля :attribute не вдалося.',
    'uppercase' => 'Поле :attribute має бути у верхньому регістрі.',
    'url' => 'Поле :attribute має бути дійсною URL-адресою.',
    'ulid' => 'Поле :attribute має бути дійсним ULID.',
    'uuid' => 'Поле :attribute має бути дійсним UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
