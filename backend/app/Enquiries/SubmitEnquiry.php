<?php

namespace App\Enquiries;

use App\Catalogue\Product;
use App\Mail\EnquiryReceived;
use App\Models\Enquiry;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SubmitEnquiry
{
    /**
     * Saves first, so a mail failure can't lose the lead. The customer still gets
     * a success response in that case, and the failure is logged and recorded on
     * the enquiry so sales can follow it up.
     *
     * @param  array{name: string, email: string, phone?: ?string, message?: ?string}  $contact
     */
    public function handle(Product $product, array $contact): Enquiry
    {
        $enquiry = Enquiry::create([
            ...$contact,
            'product_id' => $product->id,
            'product_title' => $product->title,
            'email_status' => EmailStatus::Pending,
        ]);

        try {
            Mail::to(config('enquiries.sales_inbox'))->send(new EnquiryReceived($enquiry));
            $enquiry->update(['email_status' => EmailStatus::Sent]);
        } catch (Throwable $e) {
            // Broad on purpose: each mail provider throws its own exception types.
            report($e);
            $enquiry->update(['email_status' => EmailStatus::Failed]);
        }

        return $enquiry;
    }
}
