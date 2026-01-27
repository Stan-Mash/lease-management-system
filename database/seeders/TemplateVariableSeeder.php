<?php

namespace Database\Seeders;

use App\Models\TemplateVariableDefinition;
use Illuminate\Database\Seeder;

class TemplateVariableSeeder extends Seeder
{
    public function run(): void
    {
        $variables = [
            // Lease Variables
            [
                'variable_name' => 'lease->reference_number',
                'display_name' => 'Lease Reference',
                'category' => 'lease',
                'description' => 'Unique lease reference number (e.g., LSE-XXX)',
                'data_type' => 'text',
                'is_required' => true,
                'eloquent_path' => 'lease.reference_number',
                'sample_value' => 'LSE-ABC123',
            ],
            [
                'variable_name' => 'lease->monthly_rent',
                'display_name' => 'Monthly Rent',
                'category' => 'lease',
                'description' => 'Monthly rent amount in KES',
                'data_type' => 'money',
                'format_options' => ['decimal_places' => 2],
                'is_required' => true,
                'eloquent_path' => 'lease.monthly_rent',
                'sample_value' => '25000.00',
            ],
            [
                'variable_name' => 'lease->deposit_amount',
                'display_name' => 'Deposit Amount',
                'category' => 'lease',
                'description' => 'Security deposit amount in KES',
                'data_type' => 'money',
                'format_options' => ['decimal_places' => 2],
                'is_required' => true,
                'eloquent_path' => 'lease.deposit_amount',
                'sample_value' => '25000.00',
            ],
            [
                'variable_name' => 'lease->start_date',
                'display_name' => 'Start Date',
                'category' => 'lease',
                'description' => 'Lease commencement date',
                'data_type' => 'date',
                'format_options' => ['date_format' => 'd/m/Y'],
                'is_required' => true,
                'eloquent_path' => 'lease.start_date',
                'sample_value' => '01/01/2024',
            ],
            [
                'variable_name' => 'lease->end_date',
                'display_name' => 'End Date',
                'category' => 'lease',
                'description' => 'Lease expiry date',
                'data_type' => 'date',
                'format_options' => ['date_format' => 'd/m/Y'],
                'is_required' => false,
                'eloquent_path' => 'lease.end_date',
                'sample_value' => '31/12/2024',
            ],
            [
                'variable_name' => 'lease->lease_type',
                'display_name' => 'Lease Type',
                'category' => 'lease',
                'description' => 'Type of lease (residential/commercial)',
                'data_type' => 'text',
                'is_required' => true,
                'eloquent_path' => 'lease.lease_type',
                'sample_value' => 'Residential Major',
            ],

            // Tenant Variables
            [
                'variable_name' => 'tenant->full_name',
                'display_name' => 'Tenant Full Name',
                'category' => 'tenant',
                'description' => 'Tenant\'s full legal name',
                'data_type' => 'text',
                'is_required' => true,
                'eloquent_path' => 'tenant.full_name',
                'sample_value' => 'John Doe Mwangi',
            ],
            [
                'variable_name' => 'tenant->id_number',
                'display_name' => 'Tenant ID Number',
                'category' => 'tenant',
                'description' => 'Tenant\'s national ID number',
                'data_type' => 'text',
                'is_required' => true,
                'eloquent_path' => 'tenant.id_number',
                'sample_value' => '12345678',
            ],
            [
                'variable_name' => 'tenant->phone',
                'display_name' => 'Tenant Phone',
                'category' => 'tenant',
                'description' => 'Tenant\'s phone number',
                'data_type' => 'text',
                'is_required' => true,
                'eloquent_path' => 'tenant.phone',
                'sample_value' => '+254712345678',
            ],
            [
                'variable_name' => 'tenant->email',
                'display_name' => 'Tenant Email',
                'category' => 'tenant',
                'description' => 'Tenant\'s email address',
                'data_type' => 'text',
                'is_required' => false,
                'eloquent_path' => 'tenant.email',
                'sample_value' => 'john.doe@example.com',
            ],

            // Landlord Variables
            [
                'variable_name' => 'landlord->name',
                'display_name' => 'Landlord Name',
                'category' => 'landlord',
                'description' => 'Landlord\'s full name or company name',
                'data_type' => 'text',
                'is_required' => true,
                'eloquent_path' => 'landlord.name',
                'sample_value' => 'Chabrin Properties Ltd',
            ],
            [
                'variable_name' => 'landlord->phone',
                'display_name' => 'Landlord Phone',
                'category' => 'landlord',
                'description' => 'Landlord\'s contact phone',
                'data_type' => 'text',
                'is_required' => false,
                'eloquent_path' => 'landlord.phone',
                'sample_value' => '+254712000000',
            ],
            [
                'variable_name' => 'landlord->email',
                'display_name' => 'Landlord Email',
                'category' => 'landlord',
                'description' => 'Landlord\'s email address',
                'data_type' => 'text',
                'is_required' => false,
                'eloquent_path' => 'landlord.email',
                'sample_value' => 'admin@chabrin.co.ke',
            ],

            // Property Variables
            [
                'variable_name' => 'property->name',
                'display_name' => 'Property Name',
                'category' => 'property',
                'description' => 'Name of the property/building',
                'data_type' => 'text',
                'is_required' => true,
                'eloquent_path' => 'property.name',
                'sample_value' => 'Sunshine Apartments',
            ],
            [
                'variable_name' => 'property->plot_number',
                'display_name' => 'Plot Number',
                'category' => 'property',
                'description' => 'Official plot/parcel number',
                'data_type' => 'text',
                'is_required' => false,
                'eloquent_path' => 'property.plot_number',
                'sample_value' => 'LR/12345/67',
            ],
            [
                'variable_name' => 'property->address',
                'display_name' => 'Property Address',
                'category' => 'property',
                'description' => 'Full property address',
                'data_type' => 'text',
                'is_required' => false,
                'eloquent_path' => 'property.address',
                'sample_value' => 'Ngong Road, Nairobi',
            ],

            // Unit Variables
            [
                'variable_name' => 'unit->unit_number',
                'display_name' => 'Unit Number',
                'category' => 'unit',
                'description' => 'Unit/apartment number',
                'data_type' => 'text',
                'is_required' => true,
                'eloquent_path' => 'unit.unit_number',
                'sample_value' => 'A12',
            ],
            [
                'variable_name' => 'unit->floor',
                'display_name' => 'Floor Number',
                'category' => 'unit',
                'description' => 'Floor where unit is located',
                'data_type' => 'number',
                'is_required' => false,
                'eloquent_path' => 'unit.floor',
                'sample_value' => '3',
            ],

            // System Variables
            [
                'variable_name' => 'today',
                'display_name' => 'Today\'s Date',
                'category' => 'system',
                'description' => 'Current date when document is generated',
                'data_type' => 'date',
                'format_options' => ['date_format' => 'd/m/Y'],
                'is_required' => false,
                'eloquent_path' => null,
                'helper_method' => 'now()->format("d/m/Y")',
                'sample_value' => '19/01/2026',
            ],
            [
                'variable_name' => 'qrCode',
                'display_name' => 'QR Code',
                'category' => 'system',
                'description' => 'QR code for lease verification',
                'data_type' => 'image',
                'is_required' => false,
                'eloquent_path' => null,
                'helper_method' => 'QRCodeService::generate($lease)',
                'sample_value' => 'data:image/png;base64,...',
            ],
        ];

        foreach ($variables as $variable) {
            TemplateVariableDefinition::updateOrCreate(
                ['variable_name' => $variable['variable_name']],
                $variable
            );
        }

        $this->command->info('Created ' . count($variables) . ' template variable definitions');
    }
}
