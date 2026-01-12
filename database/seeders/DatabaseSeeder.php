<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\LandlordsImport;
use App\Imports\PropertiesImport;
use App\Imports\UnitsImport;
use App\Imports\TenantsImport;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Import Landlords (Must be first)
        $this->command->info('Importing Landlords...');
        Excel::import(new LandlordsImport, storage_path('app/imports/landlords.xlsx'));

        // 2. Import Properties (Needs Landlords)
        $this->command->info('Importing Properties...');
        Excel::import(new PropertiesImport, storage_path('app/imports/properties.xlsx'));

        // 3. Import Units (Needs Properties)
        $this->command->info('Importing Units...');
        Excel::import(new UnitsImport, storage_path('app/imports/units.xlsx'));

        // 4. Import Tenants (Independent)
        $this->command->info('Importing Tenants...');
        // Note: Check if your tenant file is .xls or .xlsx and update the line below accordingly
        Excel::import(new TenantsImport, storage_path('app/imports/tenant.xlsx'), null, \Maatwebsite\Excel\Excel::XLS);

        $this->command->info('ALL DATA IMPORTED SUCCESSFULLY!');
    }
}
