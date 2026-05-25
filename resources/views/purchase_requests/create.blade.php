<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Purchase Request') }}
        </h2>
    </x-slot>

    <div class="pb-12 pt-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('purchase-requests.store') }}" enctype="multipart/form-data">
                @csrf

                <!-- Main Info -->
                <div class="card shadow-sm rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium mb-4">Request Details</h3>

                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3">
                                <label for="request_date" class="form-label">Request Date *</label>
                                <input type="date" class="form-control @error('request_date') is-invalid @enderror"
                                    id="request_date" name="request_date"
                                    value="{{ old('request_date', date('Y-m-d')) }}" required>
                                @error('request_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pr_type" class="form-label">PR Type (Operational / Non-Operational)
                                    *</label>
                                <select class="form-control @error('pr_type') is-invalid @enderror" id="pr_type"
                                    name="pr_type" required>
                                    <option value="operational" {{ old('pr_type') == 'operational' ? 'selected' : '' }}>
                                        Operational</option>
                                    <option value="non_operational" {{ old('pr_type') == 'non_operational' ? 'selected' : '' }}>Non - Operational</option>
                                </select>
                                @error('pr_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div id="budget-info-container" style="display:none !important;"></div>
                    </div>
                </div>

                <!-- Items -->
                <div class="card shadow-sm rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <div class="d-flex justify-content-between mb-4">
                            <h3 class="text-lg font-medium">Items</h3>
                            <button type="button" class="btn btn-success btn-sm" id="add-item">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>

                        <div id="items-container">
                            <!-- Items will be added here -->
                        </div>

                        @if($errors->has('items'))
                            <div class="alert alert-danger mt-3">
                                {{ $errors->first('items') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="form-actions-sticky mt-4">
                    <button type="submit" name="action" value="submit" class="btn btn-primary">Submit Request</button>
                    <button type="submit" name="action" value="draft" class="btn btn-secondary">Save as Draft</button>
                    <a href="{{ route('purchase-requests.index') }}" class="btn btn-link text-gray-600">Cancel</a>
                </div>

            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let itemIndex = 0;
            const container = document.getElementById('items-container');
            const addButton = document.getElementById('add-item');

            function createItemRow(index) {
                const html = `
                    <div class="card border mb-3 item-row" style="background-color: rgba(255,255,255,0.02)" id="item-row-${index}">
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
                                        @foreach($purposes as $p)
                                            <option value="{{ $p->name }}">{{ $p->name }}</option>
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
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tgl Dibutuhkan / Keterangan</label>
                                    <input type="text" name="items[${index}][due_date]" class="form-control" placeholder="Contoh: ASAP, 20 Jan, atau Segera">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label font-weight-bold text-info"><i class="fas fa-coins mr-1"></i>Harga Satuan *</label>
                                    <input type="number" step="0.01" name="items[${index}][estimated_price]" class="form-control price-input" placeholder="0" min="0" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label font-weight-bold text-success"><i class="fas fa-calculator mr-1"></i>Total Harga</label>
                                    <input type="text" class="form-control total-input bg-dark text-white font-weight-bold" readonly value="Rp 0">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label font-weight-bold text-warning"><i class="fas fa-wallet mr-1"></i>Sisa Pagu</label>
                                    <div class="item-remaining-budget text-warning font-weight-bold pb-2" style="font-size: 0.95rem;">-</div>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label font-weight-bold text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Status</label>
                                    <div class="item-budget-status text-danger font-weight-bold pb-2" style="font-size: 0.95rem;">-</div>
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

            // Add rows from old input if validation failed, otherwise add one initial row
            const oldItems = @json(old('items'));
            if (oldItems && Object.keys(oldItems).length > 0) {
                Object.keys(oldItems).forEach(index => {
                    container.insertAdjacentHTML('beforeend', createItemRow(parseInt(index)));
                    const row = document.getElementById(`item-row-${index}`);

                    // Fill in old values
                    row.querySelector(`[name="items[${index}][item_name]"]`).value = oldItems[index].item_name || '';
                    row.querySelector(`[name="items[${index}][purpose]"]`).value = oldItems[index].purpose || '';
                    row.querySelector(`[name="items[${index}][quantity]"]`).value = oldItems[index].quantity || '';
                    row.querySelector(`[name="items[${index}][uom]"]`).value = oldItems[index].uom || '';
                    row.querySelector(`[name="items[${index}][due_date]"]`).value = oldItems[index].due_date || '';
                    row.querySelector(`[name="items[${index}][estimated_price]"]`).value = oldItems[index].estimated_price || '';
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
                });
            } else {
                container.insertAdjacentHTML('beforeend', createItemRow(itemIndex));
                itemIndex++;
            }

            window.toggleManualItemName = function (value, index) {
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

            window.toggleManualUom = function (value, index) {
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
                        onChange: function (value) {
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
                        onChange: function (value) {
                            window.toggleManualItemName(value, index);
                        }
                    });
                }

                const purposeSelect = rowElement.querySelector('.purpose-select');
                if (purposeSelect && !purposeSelect.classList.contains('tomselected')) {
                    new TomSelect(purposeSelect, {
                        create: false,
                        sortField: { field: "text", direction: "asc" },
                        placeholder: "Select Purpose",
                        onChange: function (value) {
                            checkBudget();
                        }
                    });
                }
            }

            // Initialize all existing selects
            document.querySelectorAll('.item-row').forEach((row, idx) => {
                initTomSelects(row, idx);
            });

            function formatIDR(val) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
            }

            function calculateItemTotals() {
                let grandTotal = 0;
                document.querySelectorAll('.item-row').forEach(row => {
                    const qtyInput = row.querySelector('.quantity-input');
                    const priceInput = row.querySelector('.price-input');
                    const totalInput = row.querySelector('.total-input');

                    if (qtyInput && priceInput && totalInput) {
                        const qty = parseFloat(qtyInput.value) || 0;
                        const price = parseFloat(priceInput.value) || 0;
                        const total = qty * price;
                        totalInput.value = formatIDR(total);
                        grandTotal += total;
                    }
                });
                return grandTotal;
            }

            // Budget Checking Logic
            const requestDateInput = document.getElementById('request_date');
            const budgetInfoContainer = document.getElementById('budget-info-container');

            function checkBudget() {
                const dateVal = requestDateInput.value;
                const grandTotal = calculateItemTotals();

                // Gather all items that have a purpose
                const payloadItems = [];
                document.querySelectorAll('.item-row').forEach(row => {
                    const purposeSelect = row.querySelector('.purpose-select');
                    const qtyInput = row.querySelector('.quantity-input');
                    const priceInput = row.querySelector('.price-input');

                    const purpose = purposeSelect ? purposeSelect.value : '';
                    const qty = parseFloat(qtyInput?.value) || 0;
                    const price = parseFloat(priceInput?.value) || 0;

                    if (purpose) {
                        payloadItems.push({
                            purpose: purpose,
                            amount: qty * price
                        });
                    }
                });

                if (dateVal && payloadItems.length > 0) {
                    if (budgetInfoContainer) {
                        budgetInfoContainer.style.display = 'block';
                        budgetInfoContainer.className = "alert alert-secondary py-2 px-3 mb-0 w-100 font-weight-bold";
                        budgetInfoContainer.innerHTML = '<span class="text-gray-500"><i class="fas fa-spinner fa-spin"></i> Mengecek anggaran...</span>';
                    }

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                        || document.querySelector('input[name="_token"]')?.value;
                    if (!csrfToken) return;

                    fetch('/api/internal/check-budget', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            request_date: dateVal,
                            items: payloadItems
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success' && data.results) {
                                if (data.is_allowed) {
                                    budgetInfoContainer.className = "alert alert-success py-2 px-3 mb-0 w-100 font-weight-bold";
                                    budgetInfoContainer.innerHTML = `<span class="text-success"><i class="fas fa-check-circle"></i> Anggaran tersedia. Total: <strong>${formatIDR(grandTotal)}</strong></span>`;
                                } else {
                                    budgetInfoContainer.className = "alert alert-danger py-2 px-3 mb-0 w-100 font-weight-bold";
                                    budgetInfoContainer.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle"></i> ${data.message || 'Anggaran tidak mencukupi.'}</span>`;
                                }

                                const runningTotals = {};

                                document.querySelectorAll('.item-row').forEach(row => {
                                    const purposeSelect = row.querySelector('.purpose-select');
                                    const remainingDiv = row.querySelector('.item-remaining-budget');
                                    const statusDiv = row.querySelector('.item-budget-status');

                                    const purpose = purposeSelect ? purposeSelect.value : '';
                                    const qty = parseFloat(row.querySelector('.quantity-input')?.value) || 0;
                                    const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
                                    const total = qty * price;

                                    if (!purpose) {
                                        if (remainingDiv) remainingDiv.innerHTML = '-';
                                        if (statusDiv) statusDiv.innerHTML = '<span class="text-secondary">-</span>';
                                        return;
                                    }

                                    const result = data.results[purpose];
                                    if (!result) {
                                        if (remainingDiv) remainingDiv.innerHTML = '-';
                                        if (statusDiv) statusDiv.innerHTML = '<span class="text-secondary">-</span>';
                                        return;
                                    }

                                    const remainingVal = result.remaining_budget !== null ? parseFloat(result.remaining_budget) : null;

                                    if (remainingDiv) {
                                        remainingDiv.innerHTML = remainingVal !== null ? formatIDR(remainingVal) : '-';
                                    }

                                    if (statusDiv) {
                                        runningTotals[purpose] = (runningTotals[purpose] || 0) + total;

                                        if (remainingVal === null) {
                                            statusDiv.innerHTML = '<span class="text-secondary">-</span>';
                                        } else if (runningTotals[purpose] <= remainingVal) {
                                            statusDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle mr-1"></i> Aman</span>';
                                        } else {
                                            statusDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle mr-1"></i> Over Budget</span>';
                                        }
                                    }
                                });

                            } else {
                                budgetInfoContainer.className = "alert alert-danger py-2 px-3 mb-0 w-100 font-weight-bold";
                                budgetInfoContainer.innerHTML = `<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Error: ${data.message || 'Gagal mengecek anggaran.'}</span>`;
                            }
                        })
                        .catch(error => {
                            budgetInfoContainer.className = "alert alert-danger py-2 px-3 mb-0 w-100 font-weight-bold";
                            budgetInfoContainer.innerHTML = `<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Terjadi kesalahan koneksi saat mengecek anggaran.</span>`;
                        });
                } else {
                    budgetInfoContainer.style.display = 'none';
                    document.querySelectorAll('.item-row').forEach(row => {
                        const remainingDiv = row.querySelector('.item-remaining-budget');
                        const statusDiv = row.querySelector('.item-budget-status');
                        if (remainingDiv) remainingDiv.innerHTML = '-';
                        if (statusDiv) statusDiv.innerHTML = '-';
                    });
                }
            }

            if (requestDateInput) {
                requestDateInput.addEventListener('change', checkBudget);
            }

            // Recalculate totals and check budget on input
            container.addEventListener('input', function (e) {
                if (e.target.classList.contains('quantity-input') || e.target.classList.contains('price-input')) {
                    calculateItemTotals();
                    checkBudget();
                }
            });

            // Initial check if values already exist
            calculateItemTotals();
            if (requestDateInput && requestDateInput.value) {
                checkBudget();
            }

            addButton.addEventListener('click', function () {
                container.insertAdjacentHTML('beforeend', createItemRow(itemIndex));

                const newRow = container.lastElementChild;
                initTomSelects(newRow, itemIndex);
                calculateItemTotals();
                checkBudget();
                itemIndex++;
            });

            container.addEventListener('click', function (e) {
                if (e.target.closest('.remove-item')) {
                    const row = e.target.closest('.item-row');
                    const allRows = container.querySelectorAll('.item-row');
                    if (allRows.length > 1) {
                        row.remove();
                        calculateItemTotals();
                        checkBudget();
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