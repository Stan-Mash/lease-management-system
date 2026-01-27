Dear {{ $landlord->name }},

This is to confirm the scheduled rent escalation for your property.

Property: {{ $property }} {{ $unit }}
Tenant: {{ $tenant?->name }}
Lease Reference: {{ $lease->reference_number }}

Rent Escalation Details:
- Effective Date: {{ $effectiveDate }}
- Previous Rent: KES {{ number_format($escalation->previous_rent, 2) }}
- New Rent: KES {{ number_format($escalation->new_rent, 2) }}
- Increase: {{ $escalation->formatted_increase }}

The tenant has been notified.

Best regards,
Chabrin Agencies
