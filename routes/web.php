<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/export-ongoing', [DashboardController::class, 'exportOngoingItems'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.export-ongoing');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Departments
    Route::resource('departments', DepartmentController::class);

    // Users
    Route::resource('users', UserController::class);
    Route::get('api/companies/{companyId}/departments', [UserController::class, 'getDepartmentsByCompany'])->name('api.companies.departments');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/check', [PurchaseRequestController::class, 'checkNotifications'])->name('notifications.check');
    Route::get('notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
    Route::delete('notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Purchase Requests
    Route::get('purchase-requests/rejected', [PurchaseRequestController::class, 'rejected'])->name('purchase-requests.rejected');
    Route::get('purchase-requests/drafts', [PurchaseRequestController::class, 'drafts'])->name('purchase-requests.drafts');
    Route::post('purchase-requests/{purchaseRequest}/submit-draft', [PurchaseRequestController::class, 'submitDraft'])->name('purchase-requests.submit-draft');
    Route::get('purchase-requests/approvals', [PurchaseRequestController::class, 'approvalQueue'])
        ->middleware('role:operational_manager|manager_fat|general_manager|superadmin|procurement|procurement_holding')
        ->name('purchase-requests.approvals');
    Route::post('purchase-requests/bulk-sync-expenses', [PurchaseRequestController::class, 'bulkSyncExpensesToFinance'])->name('purchase-requests.bulk-sync-expenses');
    Route::resource('purchase-requests', PurchaseRequestController::class);
    Route::post('/api/internal/check-budget', [PurchaseRequestController::class, 'checkBudget'])->name('api.internal.check-budget');
    Route::post('purchase-requests/{purchaseRequest}/save-estimates', [PurchaseRequestController::class, 'saveEstimates'])->name('purchase-requests.save-estimates');
    Route::post('purchase-requests/{purchaseRequest}/resend-notification', [PurchaseRequestController::class, 'resendNotification'])->name('purchase-requests.resend-notification');






    Route::post('purchase-requests/{purchaseRequest}/approve-all', [PurchaseRequestController::class, 'approveAll'])->name('purchase-requests.approve-all');
    Route::post('purchase-requests/{purchaseRequest}/reject-all', [PurchaseRequestController::class, 'rejectAll'])->name('purchase-requests.reject-all');
    Route::post('purchase-requests/{purchaseRequest}/send-note-all', [PurchaseRequestController::class, 'sendNoteAll'])->name('purchase-requests.send-note-all');

    Route::post('purchase-requests/{item}/approve', [PurchaseRequestController::class, 'approveItem'])->name('purchase-requests.approve-item');
    Route::post('purchase-requests/{item}/reject', [PurchaseRequestController::class, 'rejectItem'])->name('purchase-requests.reject-item');
    Route::post('purchase-requests/{item}/send-note', [PurchaseRequestController::class, 'sendValidationNote'])->name('purchase-requests.send-note');
    Route::post('purchase-requests/{item}/revise', [PurchaseRequestController::class, 'reviseItem'])->name('purchase-requests.revise-item');
    Route::delete('purchase-requests/items/{item}', [PurchaseRequestController::class, 'deleteRejectedItem'])->name('purchase-requests.delete-rejected-item');
    Route::put('purchase-requests/items/{item}/quantity', [PurchaseRequestController::class, 'updateItemQuantity'])->name('purchase-requests.update-item-quantity');
    Route::post('purchase-requests/items/{item}/update-purpose', [PurchaseRequestController::class, 'updateItemPurpose'])->name('purchase-requests.update-item-purpose');
    Route::get('purchase-requests/{purchaseRequest}/preview', [PurchaseRequestController::class, 'preview'])->name('purchase-requests.preview');
    Route::get('purchase-requests/{purchaseRequest}/export', [PurchaseRequestController::class, 'export'])->name('purchase-requests.export');
    Route::post('purchase-requests/{item}/update-status', [PurchaseRequestController::class, 'updateItemStatus'])->name('purchase-requests.update-item-status');
    Route::post('purchase-requests/{item}/toggle-flags', [PurchaseRequestController::class, 'toggleItemFlags'])->name('purchase-requests.toggle-item-flags');
    Route::post('purchase-requests/{item}/sync-to-odoo', [PurchaseRequestController::class, 'syncItemToOdoo'])->name('purchase-requests.sync-to-odoo');
    Route::post('purchase-requests/{purchaseRequest}/sync-expense', [PurchaseRequestController::class, 'syncExpenseToFinance'])->name('purchase-requests.sync-expense');
    Route::get('odoo/vendors', [PurchaseRequestController::class, 'getOdooVendors'])->name('api.odoo.vendors');
    Route::put('purchase-requests/{item}/delivery-plans', [PurchaseRequestController::class, 'updateDeliveryPlans'])->name('purchase-requests.update-delivery-plans');
    Route::post('purchase-requests/{item}/deliveries', [PurchaseRequestController::class, 'storeDelivery'])->name('purchase-requests.store-delivery');
    Route::put('purchase-requests/deliveries/{delivery}', [PurchaseRequestController::class, 'updateDelivery'])->name('purchase-requests.update-delivery');
    Route::delete('purchase-requests/deliveries/{delivery}', [PurchaseRequestController::class, 'destroyDelivery'])->name('purchase-requests.destroy-delivery');
    Route::get('purchase-requests/deliveries/rejected', [PurchaseRequestController::class, 'rejectedDeliveries'])->name('purchase-requests.deliveries.rejected');
    Route::post('purchase-requests/deliveries/{delivery}/receive-retur', [PurchaseRequestController::class, 'storeReturReceipt'])->name('purchase-requests.deliveries.store-retur-receipt');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Staging Pengeluaran Pagu (read from FAT DB)
    Route::get('/staging-pagu', [\App\Http\Controllers\StagingPaguController::class, 'index'])
        ->middleware('role:superadmin|company_admin|procurement')
        ->name('staging-pagu.index');

    // Company specific budget and vendor views
    Route::get('/companies/{company}/budget', [\App\Http\Controllers\SettingController::class, 'companyFinanceBudget'])->name('companies.budget');
    Route::get('/companies/{company}/budget-status', [\App\Http\Controllers\SettingController::class, 'getCompanyFinanceBudgetStatus'])->name('companies.budget-status');
    Route::post('/companies/{company}/budget-generate', [\App\Http\Controllers\SettingController::class, 'generateCompanyFinanceBudget'])->name('companies.budget-generate');
    Route::get('/companies/{company}/budget-data', [\App\Http\Controllers\SettingController::class, 'getCompanyFinanceBudgetData'])->name('companies.budget-data');
    Route::get('/companies/{company}/budget-detail', [\App\Http\Controllers\SettingController::class, 'getCompanyFinanceBudgetDetail'])->name('companies.budget-detail');
    Route::post('/companies/{company}/budget-sync-departments', [\App\Http\Controllers\SettingController::class, 'syncCompanyDepartments'])->name('companies.budget-sync-departments');

    // Odoo Vendors
    Route::middleware('role:superadmin|procurement|procurement_holding|company_admin')->group(function () {
        Route::get('/companies/{company}/vendors', [\App\Http\Controllers\SettingController::class, 'companyOdooVendors'])->name('companies.vendors');
        Route::post('/companies/{company}/vendors', [\App\Http\Controllers\SettingController::class, 'storeCompanyOdooVendor'])->name('companies.vendors.store');
    });

    // Shared connection test routes for superadmin and company_admin
    Route::middleware('role:superadmin|company_admin')->group(function () {
        Route::post('/companies/test-odoo', [\App\Http\Controllers\CompanyController::class, 'testOdooConnection'])->name('companies.test-odoo');
        Route::post('/companies/test-finance', [\App\Http\Controllers\CompanyController::class, 'testFinanceConnection'])->name('companies.test-finance');
    });

    // Company Admin Settings
    Route::middleware('role:company_admin')->group(function () {
        Route::get('/my-company/settings', [\App\Http\Controllers\SettingController::class, 'myCompanySettings'])->name('settings.my-company');
        Route::put('/my-company/settings', [\App\Http\Controllers\SettingController::class, 'updateMyCompanySettings'])->name('settings.my-company.update');
    });

    // Superadmin Settings
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/settings/general', [\App\Http\Controllers\SettingController::class, 'general'])->name('settings.general');
        Route::post('/settings/general', [\App\Http\Controllers\SettingController::class, 'updateGeneral'])->name('settings.update-general');
        Route::post('/settings/finance-credentials', [\App\Http\Controllers\SettingController::class, 'updateFinanceCredentials'])->name('settings.update-finance-credentials');
        Route::post('/settings/test-finance-api', [\App\Http\Controllers\SettingController::class, 'testFinanceApi'])->name('settings.test-finance-api');
        Route::post('/settings/odoo-credentials', [\App\Http\Controllers\SettingController::class, 'updateOdooCredentials'])->name('settings.update-odoo-credentials');
        Route::post('/settings/test-odoo-api', [\App\Http\Controllers\SettingController::class, 'testOdooApi'])->name('settings.test-odoo-api');
        Route::resource('uoms', \App\Http\Controllers\UomController::class);
        Route::resource('purposes', \App\Http\Controllers\PurposeController::class);
        Route::post('master-items/import', [\App\Http\Controllers\MasterItemController::class, 'import'])->name('master-items.import');
        Route::get('master-items/template', [\App\Http\Controllers\MasterItemController::class, 'downloadTemplate'])->name('master-items.template');
        Route::resource('master-items', \App\Http\Controllers\MasterItemController::class);
        Route::resource('companies', \App\Http\Controllers\CompanyController::class);
    });

    Route::post('switch-company', [\App\Http\Controllers\CompanyController::class, 'switchCompany'])->name('switch-company');
});

require __DIR__.'/auth.php';
