<div x-show="open" @click.away="open = false" class="block">
    @foreach($available_locales as $locale_name => $available_locale)
        @if($available_locale === app()->getLocale())
            <span class="block px-4 py-2 text-customgray font-semibold">{{ __($locale_name) }}</span>
        @else
            <x-dropdown-link :href="route('lang', ['locale' => $available_locale])" >
                {{ __($locale_name) }}
            </x-dropdown-link>
        @endif
    @endforeach
</div>
