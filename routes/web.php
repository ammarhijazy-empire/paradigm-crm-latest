<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CollectionsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FileStageController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OutboxController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\RfqController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::post('/locale', [UserController::class, 'locale'])->name('locale');

Route::middleware(['auth', 'crm'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Quote request (RFQ)
    Route::get('/rfq/new', [RfqController::class, 'create'])->name('rfq.create');
    Route::post('/rfq', [RfqController::class, 'store'])->name('rfq.store');
    Route::get('/files/{file}/rfq', [RfqController::class, 'edit'])->name('rfq.edit');
    Route::put('/files/{file}/rfq', [RfqController::class, 'update'])->name('rfq.update');
    Route::post('/rfq/check', [RfqController::class, 'check'])->name('rfq.check');

    // Shipment files
    Route::get('/files', [FileController::class, 'index'])->name('files.index');
    Route::get('/files/{file}', [FileController::class, 'show'])->name('files.show');
    Route::get('/operations', [FileController::class, 'operations'])->name('files.operations');
    Route::post('/files/{file}/notes', [FileController::class, 'note'])->name('files.note');

    // Stage changes: only through FileStageService
    Route::post('/files/{file}/checks', [FileStageController::class, 'tick'])->name('files.tick');
    Route::post('/files/{file}/advance', [FileStageController::class, 'advance'])->name('files.advance');
    Route::post('/files/{file}/back', [FileStageController::class, 'back'])->name('files.back');
    Route::post('/files/{file}/lost', [FileStageController::class, 'lost'])->name('files.lost');
    Route::post('/files/{file}/reopen', [FileStageController::class, 'reopen'])->name('files.reopen');

    // Pricing
    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::post('/files/{file}/rfq-vendors', [PricingController::class, 'sendRfq'])->name('pricing.send-rfq');
    Route::post('/files/{file}/quotes', [PricingController::class, 'storeQuote'])->name('pricing.quotes.store');
    Route::put('/quotes/{quote}', [PricingController::class, 'confirmQuote'])->name('pricing.quotes.confirm');
    Route::delete('/quotes/{quote}', [PricingController::class, 'rejectQuote'])->name('pricing.quotes.reject');
    Route::post('/files/{file}/quotes/{quote}/choose', [PricingController::class, 'choose'])->name('pricing.quotes.choose');
    Route::put('/files/{file}/costs', [PricingController::class, 'saveCosts'])->name('pricing.costs');
    Route::put('/files/{file}/quote-meta', [PricingController::class, 'saveMeta'])->name('pricing.meta');
    Route::post('/files/{file}/review', [PricingController::class, 'submitReview'])->name('pricing.submit-review');
    Route::post('/files/{file}/review/decide', [PricingController::class, 'review'])->name('pricing.review');
    Route::post('/reprice/{reprice}/answer', [PricingController::class, 'answerReprice'])->name('pricing.reprice.answer');
    Route::post('/inbound/{inbound}/match', [PricingController::class, 'matchInbound'])->name('pricing.inbound.match');
    Route::post('/files/{file}/pay-terms', [PricingController::class, 'payTerms'])->name('pricing.pay-terms');

    // Quotation (sales)
    Route::put('/files/{file}/quote-lines', [QuotationController::class, 'saveLines'])->name('quote.lines');
    Route::post('/files/{file}/margin-approve', [QuotationController::class, 'approveMargin'])->name('quote.approve-margin');
    Route::get('/files/{file}/quotation.pdf', [QuotationController::class, 'pdf'])->name('quote.pdf');
    Route::post('/files/{file}/quotation-sent', [QuotationController::class, 'markSent'])->name('quote.sent');
    Route::post('/files/{file}/rounds', [QuotationController::class, 'addRound'])->name('quote.rounds.store');
    Route::put('/rounds/{round}', [QuotationController::class, 'updateRound'])->name('quote.rounds.update');
    Route::post('/files/{file}/reprice', [QuotationController::class, 'reprice'])->name('quote.reprice');

    // Operations / customs
    Route::put('/files/{file}/booking', [ShippingController::class, 'booking'])->name('shipping.booking');
    Route::post('/files/{file}/client-update', [ShippingController::class, 'clientUpdate'])->name('shipping.update');
    Route::put('/files/{file}/actual-costs', [ShippingController::class, 'actualCosts'])->name('shipping.actual-costs');
    Route::post('/files/{file}/events', [ShippingController::class, 'event'])->name('shipping.event');

    // Documents + AI document check
    Route::post('/files/{file}/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}', [DocumentController::class, 'download'])->name('documents.download');
    Route::post('/files/{file}/doc-check', [DocumentController::class, 'runCheck'])->middleware('throttle:ai')->name('documents.check');
    Route::post('/doc-checks/{check}/dismiss', [DocumentController::class, 'dismiss'])->name('documents.dismiss');

    // Invoices and money
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/files/{file}/invoices', [InvoiceController::class, 'storeFromFile'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'payment'])->name('invoices.payment');
    Route::put('/invoices/{invoice}/e-invoice', [InvoiceController::class, 'eInvoice'])->name('invoices.einvoice');
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::get('/collections', [CollectionsController::class, 'index'])->name('collections.index');
    Route::post('/cost-lines/{line}/vendor-paid', [CollectionsController::class, 'vendorPaid'])->name('collections.vendor-paid');

    // Clients, leads, vendors
    Route::get('/clients/duplicates', [ClientController::class, 'duplicates'])->name('clients.duplicates');
    Route::resource('clients', ClientController::class)->except(['destroy']);
    Route::post('/clients/{client}/activities', [ClientController::class, 'activity'])->name('clients.activity');
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::post('/leads/{lead}/contacted', [LeadController::class, 'contacted'])->name('leads.contacted');
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
    Route::post('/leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
    Route::resource('vendors', VendorController::class)->except(['show', 'destroy']);

    // Performance
    Route::get('/performance', [PerformanceController::class, 'index'])->name('performance.index');
    Route::post('/performance/self', [PerformanceController::class, 'self'])->name('performance.self');
    Route::post('/performance/{user}/manager', [PerformanceController::class, 'manager'])->name('performance.manager');

    // AI
    Route::post('/ai/whatsapp-rfq', [AiController::class, 'whatsapp'])->middleware('throttle:ai')->name('ai.whatsapp');
    Route::get('/ai/runs/{run}', [AiController::class, 'status'])->name('ai.status');
    Route::get('/ai/usage', [AiController::class, 'usage'])->name('ai.usage');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/push/subscribe', [NotificationController::class, 'subscribe'])->name('push.subscribe');

    // Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/rates', [SettingsController::class, 'rates'])->name('settings.rates');
    Route::post('/settings/rates/refresh', [SettingsController::class, 'refreshRates'])->name('settings.rates.refresh');
    Route::post('/settings/lead-keys', [SettingsController::class, 'leadKey'])->name('settings.lead-key');
    Route::get('/outbox', [OutboxController::class, 'index'])->name('outbox.index');
    Route::post('/outbox/{row}/retry', [OutboxController::class, 'retry'])->name('outbox.retry');
});
