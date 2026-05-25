<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight">
            {{ __('Add Master Item') }}
        </h2>
    </x-slot>

    <div>
        <div class="card shadow-sm rounded-lg">
            <div class="card-body p-4">
                <form action="{{ route('master-items.store') }}" method="POST">
                    @csrf

                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Item Name *</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Item</button>
                        <a href="{{ route('master-items.index') }}" class="btn btn-secondary ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
