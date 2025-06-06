<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Http\Request;

class ChangeInvoiceStatusController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, Invoice $invoice)
    {
        $this->authorize('send invoice', $invoice);

        if ($request->status == Invoice::STATUS_SENT) {
            $invoice->status = Invoice::STATUS_SENT;
            $invoice->sent = true;
            $invoice->save();
        } elseif ($request->status == Invoice::STATUS_COMPLETED) {
            $invoice->status = Invoice::STATUS_COMPLETED;
            $invoice->paid_status = Invoice::STATUS_PAID;
            $invoice->due_amount = 0;
            $invoice->save();

            $manageStock = Setting::get('manage_stock', false); // Set to false

            if ($manageStock) {
                foreach ($invoice->items as $invoiceItem) {
                    $item = $invoiceItem->item;

                    if ($item) {
                        $item->opening_stock -= $invoiceItem->quantity;
                        $item->save();
                    }
                }
            }

            return response()->json([
                'success' => true,
            ]);
        }
    }
}
