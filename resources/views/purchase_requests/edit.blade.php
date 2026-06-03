<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Purchase Request') }}
        </h2>
    </x-slot>

    <div class="pb-12 pt-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('purchase-requests.update', $purchaseRequest) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <!-- Main Info -->
                <div class="card shadow-sm rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        @if($purchaseRequest->hasRejectedItems())
                            <div class="alert alert-warning mb-4">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Revision Required:</strong> Some items in this request were rejected. Please review the reasons below and update the items accordingly.
                            </div>
                        @endif
                        <h3 class="text-lg font-medium mb-4">Request Details</h3>
                        
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3">
                                <label for="request_date" class="form-label">Request Date *</label>
                                <input type="date" class="form-control @error('request_date') is-invalid @enderror" id="request_date" name="request_date" value="{{ old('request_date', $purchaseRequest->request_date->format('Y-m-d')) }}" required>
                                @error('request_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pr_type" class="form-label">PR Type (Operational / Non-Operational) *</label>
                                <select class="form-control @error('pr_type') is-invalid @enderror" id="pr_type" name="pr_type" required @disabled($purchaseRequest->status !== 'draft')>
                                    <option value="operational" {{ old('pr_type', $purchaseRequest->pr_type) == 'operational' ? 'selected' : '' }}>Operational</option>
                                    <option value="non_operational" {{ old('pr_type', $purchaseRequest->pr_type) == 'non_operational' ? 'selected' : '' }}>Non - Operational</option>
                                </select>
                                @if($purchaseRequest->status !== 'draft')
                                    <input type="hidden" name="pr_type" value="{{ $purchaseRequest->pr_type }}">
                                @endif
                                @error('pr_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="card shadow-sm rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <div class="d-flex justify-content-between mb-4">
                            <h3 class="text-lg font-medium">Items</h3>
                            @if($purchaseRequest->status === 'draft')
                                <button type="button" class="btn btn-success btn-sm" id="add-item">
                                    <i class="fas fa-plus"></i> Add Item
                                </button>
                            @endif
                        </div>

                        <div id="items-container">
                            @php
                                $items = $purchaseRequest->items;
                                $lockedItems = $items->filter(function($item) use ($purchaseRequest) {
                                    $isRejected = str_starts_with($item->status, 'rejected');
                                    $isDraft = $purchaseRequest->status === 'draft';
                                    $isPending = in_array($item->status, ['pending', 'pending_estimate']);
                                    return !($isRejected || $isDraft || ($isPending && $purchaseRequest->isEditable()));
                                });
                                $editableItems = $items->filter(function($item) use ($purchaseRequest) {
                                    $isRejected = str_starts_with($item->status, 'rejected');
                                    $isDraft = $purchaseRequest->status === 'draft';
                                    $isPending = in_array($item->status, ['pending', 'pending_estimate']);
                                    return $isRejected || $isDraft || ($isPending && $purchaseRequest->isEditable());
                                });
                            @endphp

                            @if($lockedItems->isNotEmpty())
                                <div class="alert alert-info py-2 px-3 mb-4">
                                    <i class="fas fa-lock mr-1"></i> 
                                    <strong>Locked Items:</strong> Items below are already in the approval process and cannot be modified.
                                </div>
                                <div class="locked-items-section mb-5">
                                    @foreach($lockedItems as $item)
                                        @php $index = $items->search($item); @endphp
                                        <div class="card mb-3 border-light shadow-sm" style="background-color: rgba(255,255,255,0.02)">
                                            <div class="card-body py-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0 font-weight-bold">
                                                        <i class="fas fa-check-circle text-success mr-1"></i>
                                                        Item #{{ $index + 1 }}: {{ $item->item_name }}
                                                    </h6>
                                                    <span class="badge badge-info">
                                                        Status: {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                                    </span>
                                                </div>
                                                <div class="text-sm text-gray-400 mb-0">
                                                    Qty: <strong>{{ $item->quantity }} {{ $item->uom }}</strong>
                                                </div>
                                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                                <input type="hidden" name="items[{{ $index }}][purpose]" value="{{ $item->purpose }}">
                                                <input type="hidden" name="items[{{ $index }}][item_name]" value="{{ $item->item_name }}">
                                                <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}">
                                                <input type="hidden" name="items[{{ $index }}][uom]" value="{{ $item->uom }}">
                                                <input type="hidden" name="items[{{ $index }}][due_date]" value="{{ $item->due_date }}">
                                                <input type="hidden" name="items[{{ $index }}][estimated_price]" value="{{ $item->estimated_price }}">
                                                <input type="hidden" name="items[{{ $index }}][description]" value="{{ $item->description }}">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <hr class="my-4">
                            @endif

                            @if($editableItems->isNotEmpty())
                                <h4 class="text-md font-bold mb-3 text-warning">
                                    <i class="fas fa-edit mr-1"></i> Items to Revise / Edit
                                </h4>
                                @foreach($editableItems as $item)
                                    @php 
                                        $index = $items->search($item);
                                        $isRejected = str_starts_with($item->status, 'rejected');
                                    @endphp
                                    <div class="card mb-3 item-row {{ $isRejected ? 'border-warning shadow-sm' : 'border' }}" style="background-color: rgba(255,255,255,0.02)" id="item-row-{{ $index }}">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-3">
                                                <h5 class="card-title {{ $isRejected ? 'text-warning' : '' }} font-weight-bold">
                                                    Item #{{ $index + 1 }}
                                                    @if($isRejected)
                                                        <span class="badge badge-danger ml-2">Revision Required</span>
                                                    @endif
                                                </h5>
                                                @if($purchaseRequest->status === 'draft')
                                                    <button type="button" class="btn btn-danger btn-xs remove-item" data-index="{{ $index }}">
                                                        <i class="fas fa-times"></i> Remove
                                                    </button>
                                                @endif
                                            </div>
                                            
                                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                            
                                            <div class="row">
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">Item Name *</label>
                                                    @php
                                                        $oldItemName = old("items.{$index}.item_name", $item->item_name);
                                                        $isOther = $oldItemName === 'other' || !in_array($oldItemName, $masterItems->pluck('name')->toArray());
                                                        $manualItemName = old("items.{$index}.manual_item_name", ($isOther && $oldItemName !== 'other') ? $item->item_name : '');
                                                    @endphp
                                                    <select name="items[{{ $index }}][item_name]" id="item_name_select_{{ $index }}" class="form-control tomselect-item" required @disabled($purchaseRequest->status !== 'draft')>
                                                        <option value="">Select Item Name</option>
                                                        @foreach($masterItems as $masterItem)
                                                            <option value="{{ $masterItem->name }}" {{ $oldItemName == $masterItem->name ? 'selected' : '' }}>{{ $masterItem->name }}</option>
                                                        @endforeach
                                                        <option value="other" {{ $isOther ? 'selected' : '' }}>Others (Tulis Manual)</option>
                                                    </select>
                                                    <input type="text" name="items[{{ $index }}][manual_item_name]" id="manual_item_name_{{ $index }}" class="form-control mt-2" placeholder="Tulis nama manual..." style="display: {{ $isOther ? 'block' : 'none' }};" value="{{ $manualItemName }}" {{ $isOther ? 'required' : '' }} @disabled($purchaseRequest->status !== 'draft')>
                                                </div>
                                                 <div class="col-md-3 mb-3">
                                                     <label class="form-label font-weight-bold text-primary"><i class="fas fa-bullseye mr-1"></i>Purpose of Request *</label>
                                                     <select name="items[{{ $index }}][purpose]" class="form-control purpose-select" required @disabled($purchaseRequest->status !== 'draft')>
                                                         <option value="">Select Purpose</option>
                                                          @foreach($purposes->groupBy('department_name') as $deptName => $deptItems)
                                                              <optgroup label="{{ $deptName ?: 'Lainnya' }}">
                                                                  @foreach($deptItems as $p)
                                                                      <option value="{{ $p->name }}" {{ old("items.{$index}.purpose", $item->purpose) == $p->name ? 'selected' : '' }}>{{ $p->name }}</option>
                                                                  @endforeach
                                                              </optgroup>
                                                          @endforeach
                                                     </select>
                                                     
                                                 </div>
                                                <div class="col-md-2 mb-3">
                                                     <label class="form-label">Quantity *</label>
                                                     <input type="number" step="0.01" name="items[{{ $index }}][quantity]" class="form-control quantity-input" min="0.01" value="{{ old("items.{$index}.quantity", $item->quantity) }}" required>
                                                 </div>
                                                 <div class="col-md-2 mb-3">
                                                     <label class="form-label">UOM *</label>
                                                     @php
                                                         $oldUom = old("items.{$index}.uom", $item->uom);
                                                         $isOtherUom = $oldUom === 'other' || (!empty($oldUom) && !in_array($oldUom, $uoms->pluck('name')->toArray()));
                                                         $manualUom = old("items.{$index}.manual_uom", ($isOtherUom && $oldUom !== 'other') ? $item->uom : '');
                                                     @endphp
                                                     <select name="items[{{ $index }}][uom]" class="form-control tomselect-uom" required>
                                                         <option value="">Select UOM</option>
                                                         @foreach($uoms as $uomOption)
                                                             <option value="{{ $uomOption->name }}" {{ (!$isOtherUom && $oldUom == $uomOption->name) ? 'selected' : '' }}>{{ $uomOption->name }}</option>
                                                         @endforeach
                                                         <option value="other" {{ $isOtherUom ? 'selected' : '' }}>Others (Tulis Manual)</option>
                                                     </select>
                                                     <input type="text" name="items[{{ $index }}][manual_uom]" id="manual_uom_{{ $index }}" class="form-control mt-2" placeholder="Tulis UOM manual..." style="display: {{ $isOtherUom ? 'block' : 'none' }};" value="{{ $manualUom }}" {{ $isOtherUom ? 'required' : '' }}>
                                                 </div>
                                                <div class="col-md-2 mb-3">
                                                    <label class="form-label">Attachment / File</label>
                                                    @if($item->attachment)
                                                        <div class="mb-1">
                                                            <a href="{{ asset('storage/' . $item->attachment) }}" target="_blank" class="btn btn-xs btn-info">View Current</a>
                                                        </div>
                                                    @endif
                                                    <input type="file" name="items[{{ $index }}][attachment]" class="form-control" @disabled($purchaseRequest->status !== 'draft')>
                                            </div>
                                            <div class="row align-items-end">
                                                <div class="col-md-12 mb-3">
                                                     <label class="form-label">Tgl Dibutuhkan / Keterangan</label>
                                                    <input type="text" name="items[{{ $index }}][due_date]" class="form-control" value="{{ old("items.{$index}.due_date", $item->due_date) }}" placeholder="Contoh: ASAP, 20 Jan, atau Segera">
                                                </div>
                                                
                                                
                                                
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Description / Specs</label>
                                                    <textarea name="items[{{ $index }}][description]" class="form-control" rows="2" @disabled($purchaseRequest->status !== 'draft')>{{ old("items.{$index}.description", $item->description) }}</textarea>
                                                </div>

                                                @if($isRejected && $item->reject_reason)
                                                    <div class="col-md-12">
                                                        <div class="alert alert-danger py-2 px-3 mt-2">
                                                            <strong><i class="fas fa-times-circle"></i> Rejected:</strong> {{ $item->reject_reason }}
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        @if($errors->has('items'))
                            <div class="alert alert-danger mt-3">
                                {{ $errors->first('items') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="form-actions-sticky mt-4">
                    <button type="submit" name="action" value="submit" class="btn btn-primary">Update and Submit</button>
                    <button type="submit" name="action" value="draft" class="btn btn-secondary">Save as Draft</button>
                    <a href="{{ route('purchase-requests.index') }}" class="btn btn-link text-gray-600">Cancel</a>
                </div>

            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let itemIndex = {{ $purchaseRequest->items->count() }};
            const container = document.getElementById('items-container');
            const addButton = document.getElementById('add-item');

            function createItemRow(index) {
                const html = `
                    <div class="card mb-3 item-row" id="item-row-${index}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <h5 class="card-title">Item #${index + 1}</h5>
                                <button type="button" class="btn btn-danger btn-xs remove-item" data-index="${index}">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Item Name *</label>
                                    <select name="items[${index}][item_name]" id="item_name_select_${index}" class="form-control tomselect-item" required>
                                        <option value="">Select Item Name</option>
                                        @foreach($masterItems as $masterItem)
                                            <option value="{{ $masterItem->name }}">{{ $masterItem->name }}</option>
                                        @endforeach
                                        <option value="other">Others (Tulis Manual)</option>
                                    </select>
                                    <input type="text" name="items[${index}][manual_item_name]" id="manual_item_name_${index}" class="form-control mt-2" placeholder="Tulis nama manual..." style="display:none;">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label font-weight-bold text-primary"><i class="fas fa-bullseye mr-1"></i>Purpose of Request *</label>
                                    <select name="items[${index}][purpose]" class="form-control purpose-select" required>
                                        <option value="">Select Purpose</option>
                                        @foreach($purposes->groupBy('department_name') as $deptName => $deptItems)
                                            <optgroup label="{{ $deptName ?: 'Lainnya' }}">
                                                @foreach($deptItems as $p)
                                                    <option value="{{ $p->name }}">{{ $p->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Quantity *</label>
                                    <input type="number" step="0.01" name="items[${index}][quantity]" class="form-control quantity-input" min="0.01" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">UOM *</label>
                                    <select name="items[${index}][uom]" class="form-control tomselect-uom" required>
                                        <option value="">Select UOM</option>
                                        @foreach($uoms as $uom)
                                            <option value="{{ $uom->name }}">{{ $uom->name }}</option>
                                        @endforeach
                                        <option value="other">Others (Tulis Manual)</option>
                                    </select>
                                    <input type="text" name="items[${index}][manual_uom]" id="manual_uom_${index}" class="form-control mt-2" placeholder="Tulis UOM manual..." style="display:none;">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Attachment / File (Optional)</label>
                                    <input type="file" name="items[${index}][attachment]" class="form-control">
                                </div>
                            </div>
                            <div class="row align-items-end">
                                <div class="col-md-12 mb-3">
                                                     <label class="form-label">Tgl Dibutuhkan / Keterangan</label>
                                    <input type="text" name="items[${index}][due_date]" class="form-control" placeholder="Contoh: ASAP, 20 Jan, atau Segera">
                                </div>
                                
                                
                                
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Description / Specs</label>
                                    <textarea name="items[${index}][description]" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                return html;
            }

            // Restore any new items that were added but failed validation
            const oldItems = @json(old('items'));
            if (oldItems) {
                Object.keys(oldItems).forEach(index => {
                    // If index is greater than or equal to original count, it's a newly added row
                    if (parseInt(index) >= {{ $purchaseRequest->items->count() }}) {
                        container.insertAdjacentHTML('beforeend', createItemRow(parseInt(index)));
                        const row = document.getElementById(`item-row-${index}`);
                        
                        // Fill in old values
                        row.querySelector(`[name="items[${index}][item_name]"]`).value = oldItems[index].item_name || '';
                        row.querySelector(`[name="items[${index}][purpose]"]`).value = oldItems[index].purpose || '';
                        row.querySelector(`[name="items[${index}][quantity]"]`).value = oldItems[index].quantity || '';
                        row.querySelector(`[name="items[${index}][uom]"]`).value = oldItems[index].uom || '';
                        row.querySelector(`[name="items[${index}][due_date]"]`).value = oldItems[index].due_date || '';
                                                row.querySelector(`[name="items[${index}][description]"]`).value = oldItems[index].description || '';
                        
                        if (oldItems[index].item_name === 'other') {
                            const manualInput = row.querySelector(`#manual_item_name_${index}`);
                            if (manualInput) {
                                manualInput.style.display = 'block';
                                manualInput.required = true;
                                manualInput.value = oldItems[index].manual_item_name || '';
                            }
                        }

                        if (oldItems[index].uom === 'other') {
                            const manualInput = row.querySelector(`#manual_uom_${index}`);
                            if (manualInput) {
                                manualInput.style.display = 'block';
                                manualInput.required = true;
                                manualInput.value = oldItems[index].manual_uom || '';
                            }
                        }

                        if (parseInt(index) >= itemIndex) {
                            itemIndex = parseInt(index) + 1;
                        }
                    }
                });
            }

            addButton.addEventListener('click', function() {
                container.insertAdjacentHTML('beforeend', createItemRow(itemIndex));
                
                const newRow = container.lastElementChild;
                initTomSelects(newRow, itemIndex);
                itemIndex++;
            });

            window.toggleManualItemName = function(value, index) {
                const manualInput = document.getElementById(`manual_item_name_${index}`);
                if (!manualInput) return;
                if (value === 'other') {
                    manualInput.style.display = 'block';
                    manualInput.required = true;
                } else {
                    manualInput.style.display = 'none';
                    manualInput.required = false;
                    manualInput.value = '';
                }
            };

            window.toggleManualUom = function(value, index) {
                const manualInput = document.getElementById(`manual_uom_${index}`);
                if (!manualInput) return;
                if (value === 'other') {
                    manualInput.style.display = 'block';
                    manualInput.required = true;
                } else {
                    manualInput.style.display = 'none';
                    manualInput.required = false;
                    manualInput.value = '';
                }
            };

            function initTomSelects(rowElement, index) {
                const uomSelect = rowElement.querySelector('.tomselect-uom');
                if (uomSelect && !uomSelect.classList.contains('tomselected')) {
                    new TomSelect(uomSelect, {
                        create: false,
                        sortField: { field: "text", direction: "asc" },
                        placeholder: "Select UOM",
                        onChange: function(value) {
                            window.toggleManualUom(value, index);
                        }
                    });
                }

                const itemSelect = rowElement.querySelector('.tomselect-item');
                if (itemSelect && !itemSelect.classList.contains('tomselected')) {
                    new TomSelect(itemSelect, {
                        create: false,
                        sortField: { field: "text", direction: "asc" },
                        placeholder: "Select Item Name",
                        onChange: function(value) {
                            window.toggleManualItemName(value, index);
                        }
                    });
                }

                const purposeSelect = rowElement.querySelector('.purpose-select');
                if (purposeSelect && !purposeSelect.classList.contains('tomselected')) {
                    new TomSelect(purposeSelect, {
                        create: false,
                        sortField: { field: "text", direction: "asc" },
                        placeholder: "Select Purpose"
                    });
                }
            }

            // Initialize all existing selects
            document.querySelectorAll('.item-row').forEach((row) => {
                const idx = row.id.split('-').pop();
                initTomSelects(row, idx);
            });


            
                                    container.addEventListener('click', function(e) {
                if (e.target.closest('.remove-item')) {
                    const row = e.target.closest('.item-row');
                    const allRows = container.querySelectorAll('.item-row');
                    if (allRows.length > 1) {
                        row.remove();
                        } else {
                        alert('At least one item is required.');
                    }
                }
            });
        });
    </script>
    <style>
        /* To prevent TomSelect dropdown cutoff inside cards */
        .card-body {
            overflow: visible !important;
        }
        .bg-white {
            overflow: visible !important; 
        }

        @media (max-width: 768px) {
            .form-actions-sticky {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                padding: 1rem 1rem calc(1rem + env(safe-area-inset-bottom));
                box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
                z-index: 1030;
                display: flex;
                gap: 0.5rem;
                justify-content: center;
                flex-wrap: wrap;
            }
            .form-actions-sticky .btn {
                flex: 1 1 auto;
                margin: 0 !important;
            }
            .pb-12 {
                padding-bottom: calc(5rem + env(safe-area-inset-bottom)) !important;
            }
        }
        @media (min-width: 769px) {
            .form-actions-sticky {
                display: flex;
                align-items: center;
                gap: 1rem;
            }
        }
    </style>
</x-app-layout>
