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
        ->middleware('role:operational_manager|manager_fat|general_manager|superadmin')
        ->name('purchase-requests.approvals');
    Route::resource('purchase-requests', PurchaseRequestController::class);
    Route::post('/api/internal/check-budget', [PurchaseRequestController::class, 'checkBudget'])->name('api.internal.check-budget');
    Route::post('purchase-requests/{purchaseRequest}/save-estimates', [PurchaseRequestController::class, 'saveEstimates'])->name('purchase-requests.save-estimates');
    Route::post('purchase-requests/{purchaseRequest}/resend-notification', [PurchaseRequestController::class, 'resendNotification'])->name('purchase-requests.resend-notification');






    Route::post('purchase-requests/{item}/approve', [PurchaseRequestController::class, 'approveItem'])->name('purchase-requests.approve-item');
    Route::post('purchase-requests/{item}/reject', [PurchaseRequestController::class, 'rejectItem'])->name('purchase-requests.reject-item');
    Route::post('purchase-requests/{item}/send-note', [PurchaseRequestController::class, 'sendValidationNote'])->name('purchase-requests.send-note');
    Route::post('purchase-requests/{item}/revise', [PurchaseRequestController::class, 'reviseItem'])->name('purchase-requests.revise-item');
    Route::put('purchase-requests/items/{item}/quantity', [PurchaseRequestController::class, 'updateItemQuantity'])->name('purchase-requests.update-item-quantity');
    Route::get('purchase-requests/{purchaseRequest}/preview', [PurchaseRequestController::class, 'preview'])->name('purchase-requests.preview');
    Route::get('purchase-requests/{purchaseRequest}/export', [PurchaseRequestController::class, 'export'])->name('purchase-requests.export');
    Route::post('purchase-requests/{item}/update-status', [PurchaseRequestController::class, 'updateItemStatus'])->name('purchase-requests.update-item-status');
    Route::post('purchase-requests/{item}/sync-to-odoo', [PurchaseRequestController::class, 'syncItemToOdoo'])->name('purchase-requests.sync-to-odoo');
    Route::get('odoo/vendors', [PurchaseRequestController::class, 'getOdooVendors'])->name('api.odoo.vendors');
    Route::put('purchase-requests/{item}/delivery-plans', [PurchaseRequestController::class, 'updateDeliveryPlans'])->name('purchase-requests.update-delivery-plans');
    Route::post('purchase-requests/{item}/deliveries', [PurchaseRequestController::class, 'storeDelivery'])->name('purchase-requests.store-delivery');
    Route::put('purchase-requests/deliveries/{delivery}', [PurchaseRequestController::class, 'updateDelivery'])->name('purchase-requests.update-delivery');
    Route::delete('purchase-requests/deliveries/{delivery}', [PurchaseRequestController::class, 'destroyDelivery'])->name('purchase-requests.destroy-delivery');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Staging Pengeluaran Pagu (read from FAT DB)
    Route::get('/staging-pagu', [\App\Http\Controllers\StagingPaguController::class, 'index'])
        ->middleware('role:superadmin|manager_fat|general_manager|operational_manager|procurement')
        ->name('staging-pagu.index');

    // Finance Budget Management
    Route::get('/settings/finance-budget', [\App\Http\Controllers\SettingController::class, 'financeBudget'])->name('settings.finance-budget');
    Route::get('/settings/finance-budget-status', [\App\Http\Controllers\SettingController::class, 'getFinanceBudgetStatus'])->name('settings.finance-budget-status');
    Route::post('/settings/finance-budget-generate', [\App\Http\Controllers\SettingController::class, 'generateFinanceBudget'])->name('settings.finance-budget-generate');
    Route::get('/settings/finance-budget-data', [\App\Http\Controllers\SettingController::class, 'getFinanceBudgetData'])->name('settings.finance-budget-data');
    Route::get('/settings/finance-budget-detail', [\App\Http\Controllers\SettingController::class, 'getFinanceBudgetDetail'])->name('settings.finance-budget-detail');
    Route::post('/settings/finance-budget-sync-departments', [\App\Http\Controllers\SettingController::class, 'syncDepartments'])->name('settings.finance-budget-sync-departments');

    // Odoo Vendors
    Route::middleware('role:superadmin|procurement')->group(function () {
        Route::get('/settings/odoo-vendors', [\App\Http\Controllers\SettingController::class, 'odooVendors'])->name('settings.odoo-vendors');
        Route::post('/settings/odoo-vendors', [\App\Http\Controllers\SettingController::class, 'storeOdooVendor'])->name('settings.odoo-vendors.store');
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
    });

});

require __DIR__.'/auth.php';
