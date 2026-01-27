Dear {{ $tenant->name }},

This is to notify you of an upcoming rent adjustment for your lease.

Property: {{ $property }} {{ $unit }}
Lease Reference: {{ $lease->reference_number }}

Rent Adjustment Details:
- Effective Date: {{ $effectiveDate }}
- Current Rent: KES {{ number_format($escalation->previous_rent, 2) }}
- New Rent: KES {{ number_format($escalation->new_rent, 2) }}
- Increase: {{ $escalation->formatted_increase }}

If you have any questions, please contact Chabrin Agencies.

Best regards,
Chabrin Agencies
