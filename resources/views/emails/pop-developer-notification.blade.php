<x-mail::message>
# New Payment Received - {{ $pop->pop_number }}

Dear {{ $magicLink->developer_name ?? 'Developer' }},

We have received a new proof of payment for **{{ $pop->project_name }}**.

## Payment Details

- **POP Number:** {{ $pop->pop_number }}
- **Unit Number:** {{ $pop->unit_number }}
- **Amount:** AED {{ number_format($pop->amount, 2) }}
- **Date Received:** {{ $pop->created_at->format('F d, Y') }}

## Next Steps

Please access the developer portal to review the proof of payment and upload the official receipt.

<x-mail::button :url="$portalUrl" color="success">
Access Developer Portal
</x-mail::button>

**Important Notes:**
- This link is valid for 90 days from the date of this email
- You can upload receipts for all pending POPs in this project
- The link provides secure access without requiring a password

If you have any questions, please contact our finance team.

Best regards,<br>
{{ config('app.name') }} - Finance Department
</x-mail::message>
