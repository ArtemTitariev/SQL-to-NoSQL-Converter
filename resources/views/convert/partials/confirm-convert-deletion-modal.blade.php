<section>
    <x-modal name="confirm-convert-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('converts.destroy', $convert->id) }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-info">
                {{ __('Are you sure you want to delete this convert?') }}
            </h2>

            <p class="mt-1 text-sm text-customgray">
                {{ __('All data about this conversion will be deleted, including: connection parameters to your relational database and MongoDB, data about their structure and relationships, and the progress of the conversion.') }}
            </p>
            <p class="mt-1 text-sm text-customgray font-semibold">
                {{ __('Please note that any data that may have been transferred from a relational database to a non-relational database will not be deleted.') }}
            </p>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Delete') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
