@props([
    'withHelpIcon' => true, // Add a new prop to control the display of the help icon
])

<button
    {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 border rounded-md font-semibold text-xs bg-info text-white hover:bg-accent uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
    @if ($withHelpIcon)
        <x-icons.help />
    @endif
</button>
