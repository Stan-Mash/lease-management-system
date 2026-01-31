<?php

namespace Database\Seeders;

use App\Models\LeaseTemplate;
use Illuminate\Database\Seeder;

class ExactLeaseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Residential Major Template
        LeaseTemplate::updateOrCreate(
            ['slug' => 'residential-major'],
            [
                'name' => 'Residential Major Lease',
                'template_type' => 'residential_major',
                'description' => 'Standard lease template for major residential units (bedsitters, 1BR, 2BR, etc.)',
                'source_type' => 'system_default',
                'blade_content' => $this->getResidentialMajorTemplate(),
                'css_styles' => $this->getDefaultCssStyles(),
                'is_active' => true,
                'is_default' => true,
                'version_number' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );
        $this->command->info('Created/Updated template: Residential Major Lease');

        // Update all residential_major type templates
        LeaseTemplate::where('template_type', 'residential_major')
            ->update(['blade_content' => $this->getResidentialMajorTemplate()]);

        // Residential Micro Template
        LeaseTemplate::updateOrCreate(
            ['slug' => 'residential-micro'],
            [
                'name' => 'Residential Micro Lease',
                'template_type' => 'residential_micro',
                'description' => 'Standard lease template for micro residential units (single rooms)',
                'source_type' => 'system_default',
                'blade_content' => $this->getResidentialMicroTemplate(),
                'css_styles' => $this->getDefaultCssStyles(),
                'is_active' => true,
                'is_default' => true,
                'version_number' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );
        $this->command->info('Created/Updated template: Residential Micro Lease');

        // Update all residential_micro type templates
        LeaseTemplate::where('template_type', 'residential_micro')
            ->update(['blade_content' => $this->getResidentialMicroTemplate()]);

        // Commercial Template
        LeaseTemplate::updateOrCreate(
            ['slug' => 'commercial'],
            [
                'name' => 'Commercial Lease',
                'template_type' => 'commercial',
                'description' => 'Standard lease template for commercial properties',
                'source_type' => 'system_default',
                'blade_content' => $this->getCommercialTemplate(),
                'css_styles' => $this->getDefaultCssStyles(),
                'is_active' => true,
                'is_default' => true,
                'version_number' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );
        $this->command->info('Created/Updated template: Commercial Lease');

        // Update all commercial type templates
        LeaseTemplate::where('template_type', 'commercial')
            ->update(['blade_content' => $this->getCommercialTemplate()]);
    }

    private function getResidentialMajorTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tenancy Lease Agreement</title>
    <style>
        @page {
            margin: 120px 50px 80px 50px;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* Fixed Header on every page */
        header {
            position: fixed;
            top: -100px;
            left: 0;
            right: 0;
            height: 90px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo-cell {
            width: 200px;
        }
        .logo-img {
            width: 180px;
            height: auto;
        }
        .company-info {
            text-align: right;
            font-size: 10pt;
            line-height: 1.4;
            color: #1a365d;
        }
        .company-info .gold {
            color: #DAA520;
        }
        .header-line {
            border-bottom: 3px solid #DAA520;
            margin-top: 5px;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 25%;
            left: 10%;
            width: 80%;
            height: auto;
            opacity: 0.08;
            z-index: -1;
        }
        .watermark img {
            width: 100%;
        }

        /* Content styles */
        .content {
            position: relative;
        }
        .title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 30px 0 20px 0;
        }
        .subtitle {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin: 20px 0 15px 0;
        }
        .section-header {
            font-weight: bold;
            margin: 20px 0 10px 0;
        }
        .party-block {
            margin: 10px 0 10px 20px;
        }
        .dotted-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 200px;
        }
        .dotted-line-short {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 80px;
        }
        .dotted-line-medium {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 150px;
        }
        .dotted-line-long {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 300px;
        }

        /* Numbered clauses */
        .clause-main {
            margin: 15px 0;
            text-align: justify;
        }
        .clause-sub {
            margin: 10px 0 10px 30px;
            text-align: justify;
        }
        .clause-number {
            font-weight: normal;
        }

        /* Schedule */
        .schedule-title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            margin: 25px 0 15px 0;
        }
        .schedule-item {
            margin: 10px 0 10px 20px;
        }

        /* Signatures */
        .signature-section {
            margin-top: 30px;
        }
        .sig-row {
            margin: 12px 0;
        }
        .sig-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 250px;
            margin-left: 20px;
        }

        /* Page break control */
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <!-- Fixed Header -->
    <header>
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img src="{{ public_path('images/chabrin-logo.png') }}" class="logo-img" alt="Chabrin Agencies Ltd">
                </td>
                <td class="company-info">
                    <strong>NACICO PLAZA, LANDHIES ROAD</strong><br>
                    5<sup>TH</sup> FLOOR – ROOM 517<br>
                    P.O. Box 16659 – 00620<br>
                    NAIROBI<br>
                    <span class="gold">CELL : +254-720-854-389</span><br>
                    <span class="gold">MAIL: info@chabrinagencies.co.ke</span>
                </td>
            </tr>
        </table>
        <div class="header-line"></div>
    </header>

    <!-- Watermark -->
    <div class="watermark">
        <img src="{{ public_path('images/Chabrin-Logo-background.png') }}" alt="">
    </div>

    <!-- Page 1: Title and Parties -->
    <div class="content">
        <div class="title">TENANCY LEASE AGREEMENT</div>

        <div class="subtitle">BETWEEN</div>

        <p style="margin-left: 20px;">
            <strong>1.</strong> <span class="dotted-line-long">{{ $landlord->name ?? '' }}</span> c/o<br>
            <span style="margin-left: 25px;"><strong>MANAGING AGENT: CHABRIN AGENCIES LTD</strong></span><br>
            <span style="margin-left: 25px;">P O BOX 16659-00620</span><br>
            <span style="margin-left: 25px;">NAIROBI</span>
        </p>

        <div class="subtitle">AND</div>

        <p style="margin-left: 20px;">
            <strong>2. TENANT:</strong> <span class="dotted-line-long">{{ $tenant->full_name ?? ($tenant->first_name ?? '') . ' ' . ($tenant->last_name ?? '') }}</span>
        </p>

        <p style="margin-left: 25px;">
            <strong>ID NO:</strong> <span class="dotted-line-medium">{{ $tenant->id_number ?? '' }}</span> (Attach copy)
            <strong>Tel:</strong> <span class="dotted-line-medium">{{ $tenant->phone ?? '' }}</span>
        </p>

        <p style="margin-left: 25px;">
            <strong>ADDRESS:</strong> <span class="dotted-line-long">{{ $tenant->address ?? '' }}</span>
        </p>

        <p style="margin-left: 25px;">
            <strong>NEXT OF KIN:</strong> <span class="dotted-line">{{ $tenant->next_of_kin ?? '' }}</span>
            <strong>Tel:</strong> <span class="dotted-line-short">{{ $tenant->next_of_kin_phone ?? '' }}</span>
        </p>

        <p style="margin-top: 20px;"><strong>IN RESPECT OF RESIDENTIAL PREMISES DESIGNED AS:</strong></p>

        <p style="margin-left: 20px;">
            <strong>PLOT NO:</strong> <span class="dotted-line-short">{{ $property->plot_number ?? $property->property_code ?? '' }}</span>
            <strong>Flat no:</strong> <span class="dotted-line-short">{{ $unit->unit_number ?? '' }}</span>
        </p>

        <p style="margin-top: 15px; text-align: justify;">
            This tenancy agreement is made on the <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('d') : '' }}</span> /
            <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('m') : '' }}</span> /
            <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('Y') : '' }}</span>
            between <span class="dotted-line">{{ $landlord->name ?? '' }}</span> c/o <strong>CHABRIN AGENCIES LTD</strong> of Post Office number
            16659-00620 Nairobi In the Republic of Kenya (herein called "the managing agent" which
            expression shall where the context so admits include its successors and assigns) of the
            one part and <span class="dotted-line">{{ $tenant->full_name ?? '' }}</span> of ID No <span class="dotted-line-short">{{ $tenant->id_number ?? '' }}</span> Post Office
            number <span class="dotted-line-short">{{ $tenant->postal_address ?? '' }}</span> (Hereafter called "the tenant" which expression shall where
            the context so admits include his/her personal representatives and assigns) of the other
            part.
        </p>

        <!-- Page 2: Clauses -->
        <p class="section-header">NOW THIS TENANCY AGREEMENT WITNESSES AS FOLLOWS:</p>

        <div class="clause-main">
            <strong>1.</strong> That landlord hereby grants and the tenant hereby accepts a lease of the premises
            (hereinafter called the "premises") described in the schedule hereto for the term of
            and at the rent specified in the said schedule, payable as provided in the said
            schedule subject to the covenants agreements conditions, stipulations and provisions
            contained hereinafter.
        </div>

        <div class="clause-main">
            <strong>2. The tenants covenants with the landlord as follows:-</strong>
        </div>

        <div class="clause-sub">
            a. To pay the rent as stated in the schedule without any deductions whatsoever
            to the landlord or the landlord's duly appointed agents.
        </div>

        <div class="clause-sub">
            b. On or before execution of this agreement to pay the landlord or his agents
            Kenya Shillings <span class="dotted-line">{{ number_format($lease->deposit_amount ?? 0, 2) }}</span> Refundable security bond to be held
            by the said landlord or his agent until this agreement is terminated. The said
            deposit shall be refunded to the tenant without interest on termination of this
            agreement after the due performance of all the terms and conditions of this
            agreement by the tenant to the satisfaction of the landlord. Should the tenant
            default in such performance, the said deposit will be utilized by the landlord in
            performance in the said terms and conditions on behalf of the tenant.
        </div>

        <div class="clause-sub">
            c. The tenant has examined and knows the condition of premises and has
            received the same in good order and repairs except as herein otherwise
            specified at the execution of this lease and upon the termination of this lease
            in any way, tenant will immediately yield up premises to Lessor or his Agent in
            as good condition as when the same as entered upon by tenant and in
            particular the tenant shall be required to repaint the interior walls and fittings
            with first quality paint to restore them as they were at the commencement of
            the tenancy. The repainting and repair shall be carried by a contractor
            approved and appointed by the Lessor or his agent.
        </div>

        <div class="clause-sub">
            d. To pay all electricity and water conservancy charges in respect of the said
            premises throughout the terms hereby created or to the date of its sooner
            termination as hereinafter provided.
        </div>

        <div class="clause-sub">
            e. To keep the interior of the said premises including all doors, windows, locks,
            fasteners, keys, water taps and all internal sanitary apparatus and electric light
            fittings in good and tenantable repair and proper working order and condition
            (fair wear and tear expected).
        </div>

        <div class="clause-sub">
            f. Not to make alterations in or additions to the said premise without the
            landlord's prior consent in writing.
        </div>

        <div class="clause-sub">
            g. Not without the landlord's prior consent in writing to alter or interfere with the
            plumbing or electrical installations other than to keep in repair and to replace
            as and when necessary all switches fuses and elements forming part of the
            electrical installations.
        </div>

        <div class="clause-sub">
            h. To replace and be responsible for the cost of any keys which are damaged or
            lost and their appropriate interior and exterior doors and locks.
        </div>

        <div class="clause-sub">
            i. To permit the landlord or the landlord's agent to enter and view the condition
            of the said premises and upon notice given by the landlord forthwith to repair
            in accordance with such notice and in the event of the tenant not carrying
            out such repairs within fourteen days of the said notice the cost shall be a debt
            due from the landlord and shall be forthwith recoverable by action as rent.
        </div>

        <div class="clause-sub">
            j. To use the premises as a residential premises for the tenant only.
        </div>

        <div class="clause-sub">
            k. Not to permit any sale by auction to be held upon the said premises.
        </div>

        <div class="clause-sub">
            l. Not to suffer any part of the said premises to be used as to cause annoyance
            or inconvenience to the occupiers of the adjacent or neighboring flat or
            premises.
        </div>

        <div class="clause-sub">
            m. Not to suffer any part of the said premises to be used for any illegal purpose.
        </div>

        <div class="clause-sub">
            n. Not to assign underlet or part with possession of any part of the said premises
            without the prior consent in writing or the landlord, first had and obtained.
        </div>

        <div class="clause-sub">
            o. During the last one (1) months of the term hereby created to permit the
            landlord to affix upon the said premises a notice for re-letting and to permit
            persons with authority from the landlord or the landlord's agent or agents at
            reasonable times to view the said premises by prior appointment.
        </div>

        <div class="clause-sub">
            p. To yield up the said premises with all fixtures (other than the tenant's fixtures)
            and additions at the expiration or sooner determination of the tenancy in good
            and tenantable repair and condition and good as the tenant found them at
            the commencement of the lease.
        </div>

        <div class="clause-sub">
            q. In case of breach of this tenancy agreement the tenant or the landlord is
            entitled to one month's notice in writing or paying one month rent in lieu
            thereof to terminate the term hereby created.
        </div>

        <div class="clause-sub">
            r. To pay service charge e.g. security and garbage collection. The responsibility
            to appoint agents of these services rest on tenants unless where the landlord is
            requested to assist.
        </div>

        <div class="clause-sub">
            s. All payments are strictly made to our accounts as provided. Personal cheques
            are not acceptable. Any cheque returned to us unpaid will attract an
            immediate penalty of Kshs 3,500.
        </div>

        <div class="clause-main">
            <strong>3. The landlord covenant with the tenant as follows:</strong>
        </div>

        <div class="clause-sub">
            a. To permit the tenant to peacefully hold and enjoy the said premises during the
            said term without any interruption by the landlord or any person or agents
            rightfully claiming under or in trust of the landlord, so long as the tenant pays
            the rent hereby reserved and performs and observes the several covenants
            and the conditions herein contained.
        </div>

        <div class="clause-sub">
            b. To keep the walls, roof and structure of the premises in good and tenantable
            state of repair and maintenance.
        </div>

        <div class="clause-sub">
            c. To keep adequately lighted, cleaned and in good state the repair and
            condition the entrance halls and all common area of the said premises.
        </div>

        <div class="clause-main">
            <strong>4.</strong> The landlord shall have a right of re-entry and possession if any rent shall not have
            been paid as agreed or on breach or non-observance by the tenant of any covenant
            herein contained or on bankruptcy or composition with creditors or suffering distress
            or execution. In that event this agreement shall stand terminated automatically,
            without prejudice to landlord's rights under this agreement.
        </div>

        <div class="clause-main">
            <strong>5.</strong> In case of the premises being required for statutory duties or re-construction the
            landlord shall give the tenant notice not more than six months from the date of
            service.
        </div>

        <div class="clause-main">
            <strong>6.</strong> Any party hereto wishing to terminate the tenancy created hereby shall serve upon
            the other party written notice of his/her intention to do so and such notice shall be for
            a period of not less than one(1) calendar month.
        </div>

        <div class="clause-main">
            <strong>7.</strong> Service under this lease shall be sufficiently affected if sent to any party and registered
            post or left at the party's last known address in Kenya. The date of the posted service
            is the date when the notice is posted as indicated by postal stamp on the envelope
            or the Lessee notice when received by the Lessor.
        </div>

        <!-- The Schedule -->
        <div class="schedule-title">THE SCHEDULE</div>

        <div class="schedule-item">
            a) The date of commencement of the lease is <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('d') : '' }}</span>/<span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('m') : '' }}</span>/<span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('Y') : '' }}</span>
        </div>

        <div class="schedule-item">
            b) The term of tenancy is periodic tenancy.
        </div>

        <div class="schedule-item">
            c) The monthly rent is Kshs <span class="dotted-line">{{ number_format($lease->monthly_rent ?? 0, 2) }}</span>
        </div>

        <div class="schedule-item">
            d) The rent shall be reviewed after each calendar year to the market rates or to such a
            reasonable figure and the tenant shall henceforth pay the reviewed rent.
        </div>

        <div class="schedule-item">
            e) The rent is payable monthly in advance by 1<sup>st</sup> day and the deadline will be 5<sup>th</sup> day of
            each calendar month.
        </div>

        <div class="schedule-item">
            f) The premise is designed as Plot No. <span class="dotted-line">{{ $property->plot_number ?? $property->property_code ?? '' }} - {{ $unit->unit_number ?? '' }}</span>
        </div>

        <!-- Signatures -->
        <p style="margin-top: 30px; text-align: justify;">
            <strong>IN WITNESS WHEREOF</strong> this agreement was duly executed by the parties hereto the day
            and year first above written.
        </p>

        <div class="signature-section">
            <div class="sig-row">
                <strong>Signed</strong> by the Managing Agents <span style="margin-left: 50px;">)</span> <span class="sig-line"></span><br>
                <span style="margin-left: 30px;">(For the Landlord)</span>
            </div>

            <div class="sig-row">
                The said <span style="margin-left: 120px;">)</span>
            </div>

            <div class="sig-row" style="margin-top: 20px;">
                In the presence of <span style="margin-left: 80px;">)</span> <span class="sig-line"></span>
            </div>

            <div class="sig-row" style="margin-top: 25px;">
                Signed by the tenant <span style="margin-left: 70px;">)</span> <span class="sig-line"></span>
            </div>

            <div class="sig-row">
                The said ID NO. {{ $tenant->id_number ?? '' }} <span style="margin-left: 20px;">)</span>
            </div>

            <div class="sig-row" style="margin-top: 20px;">
                In the presence of: <span style="margin-left: 75px;">)</span> <span class="sig-line"></span>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getResidentialMicroTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tenancy Lease Agreement - Micro Dwelling</title>
    <style>
        @page {
            margin: 120px 50px 80px 50px;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
        }
        header {
            position: fixed;
            top: -100px;
            left: 0;
            right: 0;
            height: 90px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo-cell {
            width: 200px;
            vertical-align: top;
        }
        .logo-img {
            width: 180px;
        }
        .company-info {
            text-align: right;
            font-size: 10pt;
            line-height: 1.4;
            color: #1a365d;
            vertical-align: top;
        }
        .gold {
            color: #DAA520;
        }
        .header-line {
            border-bottom: 3px solid #DAA520;
            margin-top: 5px;
        }
        .watermark {
            position: fixed;
            top: 25%;
            left: 10%;
            width: 80%;
            opacity: 0.08;
            z-index: -1;
        }
        .title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 30px 0 20px 0;
        }
        .subtitle {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
        }
        .dotted-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 150px;
        }
        .dotted-line-short {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 80px;
        }
        .clause {
            margin: 10px 0 10px 25px;
            text-align: justify;
        }
        .sig-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 200px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <header>
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img src="{{ public_path('images/chabrin-logo.png') }}" class="logo-img">
                </td>
                <td class="company-info">
                    <strong>NACICO PLAZA, LANDHIES ROAD</strong><br>
                    5<sup>TH</sup> FLOOR – ROOM 517<br>
                    P.O. Box 16659 – 00620<br>
                    NAIROBI<br>
                    <span class="gold">CELL : +254-720-854-389</span><br>
                    <span class="gold">MAIL: info@chabrinagencies.co.ke</span>
                </td>
            </tr>
        </table>
        <div class="header-line"></div>
    </header>

    <div class="watermark">
        <img src="{{ public_path('images/Chabrin-Logo-background.png') }}" style="width:100%">
    </div>

    <div class="title">TENANCY LEASE AGREEMENT<br>(MICRO DWELLING)</div>

    <div class="subtitle">BETWEEN</div>

    <p style="margin-left:20px;">
        <strong>1.</strong> c/o <strong>CHABRIN AGENCIES LTD</strong> (Managing Agent)<br>
        <span style="margin-left:25px;">P O BOX 16659-00620, NAIROBI</span>
    </p>

    <div class="subtitle">AND</div>

    <p style="margin-left:20px;">
        <strong>2. TENANT:</strong> <span class="dotted-line">{{ $tenant->full_name ?? '' }}</span><br>
        <strong>ID NO:</strong> <span class="dotted-line-short">{{ $tenant->id_number ?? '' }}</span>
        <strong>Tel:</strong> <span class="dotted-line-short">{{ $tenant->phone ?? '' }}</span>
    </p>

    <p>
        <strong>PLOT NO:</strong> <span class="dotted-line-short">{{ $property->property_code ?? '' }}</span>
        <strong>Room no:</strong> <span class="dotted-line-short">{{ $unit->unit_number ?? '' }}</span>
    </p>

    <p style="text-align:justify;">
        This agreement made on <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('d/m/Y') : '' }}</span>
        between the Landlord c/o <strong>CHABRIN AGENCIES LTD</strong> and
        <span class="dotted-line">{{ $tenant->full_name ?? '' }}</span> (the Tenant).
    </p>

    <p><strong>TERMS:</strong></p>

    <div class="clause">1. Monthly rent: Kshs <span class="dotted-line-short">{{ number_format($lease->monthly_rent ?? 0, 2) }}</span> payable by the 5th of each month.</div>
    <div class="clause">2. Security deposit: Kshs <span class="dotted-line-short">{{ number_format($lease->deposit_amount ?? 0, 2) }}</span> (refundable).</div>
    <div class="clause">3. Tenant shall pay electricity/water bills.</div>
    <div class="clause">4. No subletting without written consent.</div>
    <div class="clause">5. One month notice required for termination.</div>
    <div class="clause">6. Premises for residential use only.</div>

    <div style="margin-top:40px;">
        <p><strong>Managing Agent:</strong> <span class="sig-line"></span></p>
        <p><strong>Tenant:</strong> <span class="sig-line"></span></p>
        <p><strong>Date:</strong> <span class="sig-line"></span></p>
    </div>
</body>
</html>
HTML;
    }

    private function getCommercialTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Commercial Lease Agreement</title>
    <style>
        @page {
            margin: 120px 50px 80px 50px;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
        }
        header {
            position: fixed;
            top: -100px;
            left: 0;
            right: 0;
            height: 90px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo-cell {
            width: 200px;
            vertical-align: top;
        }
        .logo-img {
            width: 180px;
        }
        .company-info {
            text-align: right;
            font-size: 10pt;
            line-height: 1.4;
            color: #1a365d;
            vertical-align: top;
        }
        .gold {
            color: #DAA520;
        }
        .header-line {
            border-bottom: 3px solid #DAA520;
            margin-top: 5px;
        }
        .watermark {
            position: fixed;
            top: 25%;
            left: 10%;
            width: 80%;
            opacity: 0.08;
            z-index: -1;
        }
        .title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 30px 0 20px 0;
        }
        .subtitle {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
        }
        .dotted-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 150px;
        }
        .dotted-line-short {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 80px;
        }
        .clause {
            margin: 10px 0 10px 25px;
            text-align: justify;
        }
        .sig-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 200px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <header>
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img src="{{ public_path('images/chabrin-logo.png') }}" class="logo-img">
                </td>
                <td class="company-info">
                    <strong>NACICO PLAZA, LANDHIES ROAD</strong><br>
                    5<sup>TH</sup> FLOOR – ROOM 517<br>
                    P.O. Box 16659 – 00620<br>
                    NAIROBI<br>
                    <span class="gold">CELL : +254-720-854-389</span><br>
                    <span class="gold">MAIL: info@chabrinagencies.co.ke</span>
                </td>
            </tr>
        </table>
        <div class="header-line"></div>
    </header>

    <div class="watermark">
        <img src="{{ public_path('images/Chabrin-Logo-background.png') }}" style="width:100%">
    </div>

    <div class="title">COMMERCIAL LEASE AGREEMENT</div>

    <div class="subtitle">BETWEEN</div>

    <p style="margin-left:20px;">
        <strong>1. LANDLORD:</strong> <span class="dotted-line">{{ $landlord->name ?? '' }}</span><br>
        <span style="margin-left:25px;">c/o <strong>CHABRIN AGENCIES LTD</strong> (Managing Agent)</span><br>
        <span style="margin-left:25px;">P O BOX 16659-00620, NAIROBI</span>
    </p>

    <div class="subtitle">AND</div>

    <p style="margin-left:20px;">
        <strong>2. TENANT/BUSINESS:</strong> <span class="dotted-line">{{ $tenant->full_name ?? '' }}</span><br>
        <strong>ID/Passport No:</strong> <span class="dotted-line-short">{{ $tenant->id_number ?? '' }}</span>
        <strong>Tel:</strong> <span class="dotted-line-short">{{ $tenant->phone ?? '' }}</span>
    </p>

    <p>
        <strong>PREMISES:</strong> Plot <span class="dotted-line-short">{{ $property->property_code ?? '' }}</span>,
        Unit <span class="dotted-line-short">{{ $unit->unit_number ?? '' }}</span>
    </p>

    <p><strong>LEASE TERMS:</strong></p>

    <div class="clause">1. Commencement: <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('d/m/Y') : '' }}</span></div>
    <div class="clause">2. Monthly rent: Kshs <span class="dotted-line-short">{{ number_format($lease->monthly_rent ?? 0, 2) }}</span></div>
    <div class="clause">3. Security deposit: Kshs <span class="dotted-line-short">{{ number_format($lease->deposit_amount ?? 0, 2) }}</span></div>
    <div class="clause">4. Rent payable by the 5th of each month.</div>
    <div class="clause">5. Tenant responsible for utilities (electricity, water, etc.).</div>
    <div class="clause">6. Premises for commercial/business use only.</div>
    <div class="clause">7. No structural alterations without written consent.</div>
    <div class="clause">8. Three months notice required for termination.</div>
    <div class="clause">9. Annual rent review applies.</div>

    <p style="margin-top:30px;"><strong>IN WITNESS WHEREOF</strong> the parties have signed:</p>

    <div style="margin-top:20px;">
        <p><strong>For the Landlord (Managing Agent):</strong> <span class="sig-line"></span></p>
        <p><strong>Tenant:</strong> <span class="sig-line"></span></p>
        <p><strong>Witness:</strong> <span class="sig-line"></span></p>
        <p><strong>Date:</strong> <span class="sig-line"></span></p>
    </div>
</body>
</html>
HTML;
    }

    private function getDefaultCssStyles(): array
    {
        return [
            'font_family' => 'Arial, Helvetica, sans-serif',
            'font_size' => '11pt',
            'line_height' => '1.5',
            'page_size' => 'A4',
            'orientation' => 'portrait',
        ];
    }
}
