<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('General Settings') }}
        </h2>
    </x-slot>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm rounded-lg">
                <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                    <h3 class="card-title text-lg font-medium"><i class="fas fa-cogs mr-2 text-primary"></i> System Configuration</h3>
                </div>
                <form action="{{ route('settings.update-general') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body px-4 pb-4">
                        <div class="form-group">
                            <label for="app_name">System Name</label>
                            <input type="text" name="app_name" class="form-control @error('app_name') is-invalid @enderror" id="app_name" value="{{ old('app_name', $settings['app_name']) }}" required>
                            @error('app_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="app_logo">System Logo</label>
                            @if($settings['app_logo'])
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['app_logo']) }}" alt="Logo" style="height: 50px;">
                                </div>
                            @endif
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="app_logo" class="custom-file-input" id="app_logo">
                                    <label class="custom-file-label" for="app_logo">Choose file</label>
                                </div>
                            </div>
                            <small class="text-muted">Max size: 2MB. Format: JPG, PNG, SVG.</small>
                        </div>

                        <div class="form-group">
                            <label for="export_logo">Export Logo (for PDF/Preview)</label>
                            @if(isset($settings['export_logo']) && $settings['export_logo'])
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['export_logo']) }}" alt="Export Logo" style="height: 50px;">
                                </div>
                            @endif
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="export_logo" class="custom-file-input" id="export_logo">
                                    <label class="custom-file-label" for="export_logo">Choose file</label>
                                </div>
                            </div>
                            <small class="text-muted">Max size: 2MB. Format: JPG, PNG, SVG. Used specifically for PR Export.</small>
                        </div>

                        <div class="form-group">
                            <label for="app_favicon">Favicon</label>
                            @if($settings['app_favicon'])
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['app_favicon']) }}" alt="Favicon" style="height: 32px;">
                                </div>
                            @endif
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="app_favicon" class="custom-file-input" id="app_favicon">
                                    <label class="custom-file-label" for="app_favicon">Choose file</label>
                                </div>
                            </div>
                            <small class="text-muted">Max size: 1MB. Format: ICO, PNG.</small>
                        </div>

                        <hr>
                        <h5 class="mb-3">Digital Signatures (for Export)</h5>
                        
                        <div class="form-group">
                            <label for="signature_om">Operational Manager Signature</label>
                            @if($settings['signature_om'])
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['signature_om']) }}" alt="OM Signature" style="height: 60px; border: 1px solid #ddd; padding: 5px;">
                                </div>
                            @endif
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="signature_om" class="custom-file-input" id="signature_om">
                                    <label class="custom-file-label" for="signature_om">Choose Signature Image</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="signature_gm">General Manager Signature</label>
                            @if($settings['signature_gm'])
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['signature_gm']) }}" alt="GM Signature" style="height: 60px; border: 1px solid #ddd; padding: 5px;">
                                </div>
                            @endif
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="signature_gm" class="custom-file-input" id="signature_gm">
                                    <label class="custom-file-label" for="signature_gm">Choose Signature Image</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="signature_proc">Procurement Signature</label>
                            @if($settings['signature_proc'])
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['signature_proc']) }}" alt="Procurement Signature" style="height: 60px; border: 1px solid #ddd; padding: 5px;">
                                </div>
                            @endif
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" name="signature_proc" class="custom-file-input" id="signature_proc">
                                    <label class="custom-file-label" for="signature_proc">Choose Signature Image</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>


        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-header border-bottom-0 pb-0 pt-4 px-4">
                    <h3 class="card-title text-lg font-medium"><i class="fas fa-info-circle mr-2 text-info"></i> Information</h3>
                </div>
                <div class="card-body p-4">
                    <p>These settings will affect the entire application including:</p>
                    <ul>
                        <li>Sidebar Brand Name</li>
                        <li>Browser Tab Title</li>
                        <li>Login Page visuals</li>
                    </ul>
                </div>
            </div>



        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function () {
            if (typeof bsCustomFileInput !== 'undefined') {
                bsCustomFileInput.init();
            }



        });

    </script>
    @endpush
</x-app-layout>
