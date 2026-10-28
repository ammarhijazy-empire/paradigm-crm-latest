<?php

namespace App\Http\Controllers;

use App\Domain\InvoiceService;
use App\Models\Invoice;
use App\Models\ShipmentFile;
use App\Support\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoices) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);
        $q = Invoice::with('client', 'file')->latest('issue_date');
        if ($s = $request->input('status')) {
            $q->where('status', $s);
        }
        return view('invoices.index', ['invoices' => $q->paginate(30)->withQueryString()]);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        return view('invoices.show', ['invoice' => $invoice->load('lines', 'payments.recorder', 'client', 'file')]);
    }

    public function pdf(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $locale = $request->input('lang') === 'en' ? 'en' : 'ar';
        return Pdf::render('pdf.invoice', ['invoice' => $invoice->load('lines', 'client', 'file')], $locale, $invoice->number.'.pdf');
    }

    public function storeFromFile(Request $request, ShipmentFile $file)
    {
        $this->authorize('bill', $file);
        $d = $request->validate(['vat' => ['array'], 'vat.*' => ['boolean'], 'due_date' => ['nullable', 'date']]);
        $created = $this->invoices->createFromFile($file, $request->user(), $d['vat'] ?? [], $d['due_date'] ?? null);
        return redirect()->to(route('files.show', $file).'?tab=money')->with('ok', __('invoice.created', ['n' => count($created)]));
    }

    public function payment(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $d = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'paid_at' => ['required', 'date', 'before_or_equal:today'], 'method' => ['nullable', 'string', 'max:24'], 'reference' => ['nullable', 'string', 'max:120']]);
        $this->invoices->recordPayment($invoice, $request->user(), (float) $d['amount'], $d['paid_at'], $d['method'] ?? null, $d['reference'] ?? null);
        return $this->ok('invoice.payment_saved');
    }

    public function eInvoice(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $d = $request->validate(['e_invoice_no' => ['nullable', 'string', 'max:64']]);
        $this->invoices->setEInvoiceNo($invoice, $request->user(), $d['e_invoice_no'] ?? null);
        return $this->ok('common.saved');
    }

    public function cancel(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $this->invoices->cancel($invoice, $request->user());
        return $this->ok('invoice.cancelled');
    }
}
