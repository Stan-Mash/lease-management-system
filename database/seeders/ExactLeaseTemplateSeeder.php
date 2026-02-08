<?php

namespace Database\Seeders;

use App\Models\LeaseTemplate;
use App\Models\LeaseTemplateVersion;
use Illuminate\Database\Seeder;

class ExactLeaseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Use withoutEvents to prevent the model observer from auto-creating
        // duplicate version snapshots during seeding
        LeaseTemplate::withoutEvents(function () {
            $this->seedTemplate(
                slug: 'residential-major',
                name: 'Residential Major Lease',
                type: 'residential_major',
                description: 'Standard lease template for major residential units (bedsitters, 1BR, 2BR, etc.)',
                content: $this->getResidentialMajorTemplate(),
            );

            $this->seedTemplate(
                slug: 'residential-micro',
                name: 'Residential Micro Lease',
                type: 'residential_micro',
                description: 'Standard lease template for micro residential units (single rooms)',
                content: $this->getResidentialMicroTemplate(),
            );

            $this->seedTemplate(
                slug: 'commercial',
                name: 'Commercial Lease',
                type: 'commercial',
                description: 'Standard lease template for commercial properties',
                content: $this->getCommercialTemplate(),
            );
        });
    }

    /**
     * Seed or update a single template and its latest version.
     */
    private function seedTemplate(string $slug, string $name, string $type, string $description, string $content): void
    {
        $template = LeaseTemplate::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'template_type' => $type,
                'description' => $description,
                'source_type' => 'system_default',
                'blade_content' => $content,
                'css_styles' => $this->getDefaultCssStyles(),
                'is_active' => true,
                'is_default' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        // Update or create the latest version record (avoids unique constraint violations)
        LeaseTemplateVersion::updateOrCreate(
            [
                'lease_template_id' => $template->id,
                'version_number' => $template->version_number,
            ],
            [
                'blade_content' => $content,
                'css_styles' => $this->getDefaultCssStyles(),
                'layout_config' => $template->layout_config,
                'branding_config' => $template->branding_config,
                'available_variables' => $template->available_variables,
                'created_by' => 1,
                'change_summary' => 'Template seeded/updated to match physical PDF',
            ]
        );

        // Also update all templates of the same type to keep them in sync
        LeaseTemplate::where('template_type', $type)
            ->where('id', '!=', $template->id)
            ->update(['blade_content' => $content]);

        $this->command->info("Created/Updated template: {$name} (v{$template->version_number})");
    }

    /**
     * =========================================================================
     * RESIDENTIAL MAJOR — "TENANCY LEASE AGREEMENT" (5-page PDF)
     * Matches: CHABRIN AGENCIES TENANCY LEASE AGREEMENT - MAJOR DWELLING.pdf
     * =========================================================================
     */
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
                    5<sup>TH</sup> FLOOR &ndash; ROOM 517<br>
                    P.O. Box 16659 &ndash; 00620<br>
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
            <strong>Tel:</strong> <span class="dotted-line-medium">{{ $tenant->phone ?? $tenant->phone_number ?? '' }}</span>
        </p>

        <p style="margin-left: 25px;">
            <strong>ADDRESS:</strong> <span class="dotted-line-long">{{ $tenant->address ?? $tenant->postal_address ?? '' }}</span>
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
            16659-00620 Nairobi In the Republic of Kenya (herein called &ldquo;the managing agent&rdquo; which
            expression shall where the context so admits include its successors and assigns) of the
            one part and <span class="dotted-line">{{ $tenant->full_name ?? '' }}</span> of ID No <span class="dotted-line-short">{{ $tenant->id_number ?? '' }}</span> Post Office
            number <span class="dotted-line-short">{{ $tenant->postal_address ?? '' }}</span> (Hereafter called &ldquo;the tenant&rdquo; which expression shall where
            the context so admits include his/her personal representatives and assigns) of the other
            part.
        </p>

        <!-- Clauses -->
        <p class="section-header">NOW THIS TENANCY AGREEMENT WITNESSES AS FOLLOWS:</p>

        <div class="clause-main">
            <strong>1.</strong> That landlord hereby grants and the tenant hereby accepts a lease of the premises
            (hereinafter called the &ldquo;premises&rdquo;) described in the schedule hereto for the term of
            and at the rent specified in the said schedule, payable as provided in the said
            schedule subject to the covenants agreements conditions, stipulations and provisions
            contained hereinafter.
        </div>

        <div class="clause-main">
            <strong>2. The tenants covenants with the landlord as follows:-</strong>
        </div>

        <div class="clause-sub">
            a. To pay the rent as stated in the schedule without any deductions whatsoever
            to the landlord or the landlord&rsquo;s duly appointed agents.
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
            landlord&rsquo;s prior consent in writing.
        </div>

        <div class="clause-sub">
            g. Not without the landlord&rsquo;s prior consent in writing to alter or interfere with the
            plumbing or electrical installations other than to keep in repair and to replace
            as and when necessary all switches fuses and elements forming part of the
            electrical installations.
        </div>

        <div class="clause-sub">
            h. To replace and be responsible for the cost of any keys which are damaged or
            lost and their appropriate interior and exterior doors and locks.
        </div>

        <div class="clause-sub">
            i. To permit the landlord or the landlord&rsquo;s agent to enter and view the condition
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
            persons with authority from the landlord or the landlord&rsquo;s agent or agents at
            reasonable times to view the said premises by prior appointment.
        </div>

        <div class="clause-sub">
            p. To yield up the said premises with all fixtures (other than the tenant&rsquo;s fixtures)
            and additions at the expiration or sooner determination of the tenancy in good
            and tenantable repair and condition and good as the tenant found them at
            the commencement of the lease.
        </div>

        <div class="clause-sub">
            q. In case of breach of this tenancy agreement the tenant or the landlord is
            entitled to one month&rsquo;s notice in writing or paying one month rent in lieu
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
            without prejudice to landlord&rsquo;s rights under this agreement.
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
            post or left at the party&rsquo;s last known address in Kenya. The date of the posted service
            is the date when the notice is posted as indicated by postal stamp on the envelope
            or the Lessee notice when received by the Lessor.
        </div>

        <!-- The Schedule -->
        <div class="schedule-title">THE SCHEDULE</div>

        <div class="schedule-item">
            a) The date of commencement of the lease is <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('d') : '' }}</span>/<span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('F') : '' }}</span>/<span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('Y') : '' }}</span>
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

    /**
     * =========================================================================
     * RESIDENTIAL MICRO — "TENANCY AGREEMENT" (2-page PDF)
     * Matches: CHABRIN AGENCIES TENANCY LEASE AGREEMENT - MICRO DWELLING.pdf
     * This is the shorter tenancy agreement with 17 numbered conditions.
     * =========================================================================
     */
    private function getResidentialMicroTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tenancy Agreement</title>
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
        .watermark img {
            width: 100%;
        }
        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 25px 0 20px 0;
        }
        .dotted-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 200px;
        }
        .dotted-line-short {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 100px;
        }
        .dotted-line-medium {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 150px;
        }
        .dotted-line-long {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 350px;
        }
        .dotted-line-full {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 450px;
        }
        .info-row {
            margin: 8px 0;
        }
        .conditions-title {
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0 12px 0;
        }
        .condition {
            margin: 8px 0 8px 25px;
            text-align: justify;
        }
        .witness-text {
            font-weight: bold;
            font-style: italic;
            margin: 25px 0 15px 0;
        }
        .sig-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 250px;
            margin-left: 5px;
        }
        .sig-row {
            margin: 15px 0;
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
                    5<sup>TH</sup> FLOOR &ndash; ROOM 517<br>
                    P.O. Box 16659 &ndash; 00620<br>
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

    <div class="title">TENANCY AGREEMENT</div>

    <p style="text-align: justify;">
        <strong>THIS AGREEMENT</strong> is made this <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('d') : '' }}</span>
        day of <span class="dotted-line-medium">{{ $lease->start_date ? $lease->start_date->format('F') : '' }}</span>
        20<span class="dotted-line-short" style="min-width:30px;">{{ $lease->start_date ? $lease->start_date->format('y') : '' }}</span>
        between <span class="dotted-line">{{ $landlord->name ?? '' }}</span>
        &ldquo;The duly appointed Managing Agent&rdquo; of the said property and:
    </p>

    <div class="info-row">
        <strong>TENANT&rsquo;S NAME:</strong> <span class="dotted-line-full">{{ $tenant->full_name ?? ($tenant->first_name ?? '') . ' ' . ($tenant->last_name ?? '') }}</span>
    </div>

    <div class="info-row">
        <strong>ID</strong> <span class="dotted-line-medium">{{ $tenant->id_number ?? '' }}</span>
        <strong>(Attach copy) ADDRESS:</strong> <span class="dotted-line-medium">{{ $tenant->address ?? $tenant->postal_address ?? '' }}</span>
    </div>

    <div class="info-row">
        <strong>TEL:</strong> <span class="dotted-line-medium">{{ $tenant->phone ?? $tenant->phone_number ?? '' }}</span>
        <strong>PLACE OF WORK:</strong> <span class="dotted-line-medium">{{ $tenant->place_of_work ?? '' }}</span>
    </div>

    <div class="info-row">
        <strong>NEXT OF KIN:</strong> <span class="dotted-line">{{ $tenant->next_of_kin ?? '' }}</span>
        <strong>TEL:</strong> <span class="dotted-line-medium">{{ $tenant->next_of_kin_phone ?? '' }}</span>
    </div>

    <div class="info-row">
        <strong>PROPERTY NAME:</strong> <span class="dotted-line">{{ $property->name ?? $property->property_code ?? '' }}</span>
        <strong>ROOM NO:</strong> <span class="dotted-line-short">{{ $unit->unit_number ?? '' }}</span>
    </div>

    <div class="info-row">
        <strong>HOUSE DEPOSIT PAID:</strong> <span class="dotted-line-short">{{ number_format($lease->deposit_amount ?? 0, 2) }}</span>
        <strong>RECEIPT NO.</strong> <span class="dotted-line-short">{{ $lease->deposit_receipt_number ?? '' }}</span>
        <strong>DATE:</strong> <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('d/m/Y') : '' }}</span>
    </div>

    <p style="font-weight: bold; font-style: italic; margin-top: 15px;">
        WHERE IT IS AGREED BETWEEN the parties as follows:-
    </p>

    <div class="conditions-title">CONDITIONS</div>

    <div class="condition">
        1. Rent is STRICTLY payable on or before the 1<sup>st</sup> day of the month and the deadline will
        be on the <u><strong>5<sup>th</sup></strong></u> of every month during the tenancy period.
    </div>

    <div class="condition">
        2. An equivalent of one-month rent will be paid as deposit and a Kshs. <span class="dotted-line-short">{{ number_format($lease->water_deposit ?? 0, 2) }}</span>
        electricity and water deposits payable to Chabrin Agencies Ltd bank accounts. The
        rent deposit sum is refundable at the termination of this tenancy with proper one (1)
        calendar months&rsquo; written notice. The said sum may be utilized to defray any
        outstanding conservancy charges, damages or expenses which would be at all
        material times may be payable by the tenant within the tenancy period and such,
        the deposit should NEVER be used as the last months&rsquo; rent payment. Refunds done
        on <u><strong>25<sup>th</sup>/26<sup>th</sup> of the month</strong></u> upon following the laid down procedures.
    </div>

    <div class="condition">
        3. Either party can terminate this agreement by giving a one (1) calendar Months&rsquo;
        notice in writing.
    </div>

    <div class="condition">
        4. The property owner will only allow established occupants before renting a out a unit
        in the premise.
    </div>

    <div class="condition">
        5. To permit the Landlord, his agents, workmen or servants at all reasonable times on
        notice from the landlord whether oral or written to enter upon the said premises or
        part thereof and execute structural or other repairs to the building.
    </div>

    <div class="condition">
        6. No reckless use of water will be tolerated. Only authorized occupants will enjoy this
        facility.
    </div>

    <div class="condition">
        7. Anti-social activities likely to inconvenience other tenants like loud music or any other
        unnecessary noise <strong>SHALL NOT</strong> be tolerated and as such, the said behavior shall be
        deemed as breach of this agreement that shall form the basis of terminating the
        tenancy without further reference to the tenant.
    </div>

    <div class="condition">
        8. It will be the responsibility of every tenant to keep the premises clean.
    </div>

    <div class="condition">
        9. Sources of energy such as firewood, charcoal, open lamps or any other smoking
        instrument should not be used in the premises.
    </div>

    <div class="condition">
        10. To use the premises for private residential purposes only and not carry any form of
        business or use them as a boarding house or any other unauthorized purpose without
        the consent of the Landlord in writing.
    </div>

    <div class="condition">
        11. Not to make or permit to made any alterations in or additions to the said premises nor
        to erect any fixtures therein nor drive any nails, screws, bolts or wedges in the floors,
        walls or ceilings thereof without the consent in writing of the Landlord first hand and
        obtained(which consent shall not unreasonably withheld).
    </div>

    <div class="condition">
        12. Not to sublet or let out the space apportioned under the lease. Breach of this clause
        will lead to immediate termination of the running lease.
    </div>

    <div class="condition">
        13. Deposit should be updated from time to time as the house rent is adjusted.
    </div>

    <div class="condition">
        14. In the event of failure to pay the said rents or any other sum due under this lease
        within seven (7) days of the due date whether formally demanded or not the
        Landlord/Agent may take necessary action or sending auctioneers to the lessee to
        recover the said sum due as to costs and any incidentals to be borne by the lessee.
    </div>

    <div class="condition">
        15. The tenant/lessee shall insure his personal and household belongings and indemnify
        the landlord against any action claim or demand arising from any loss, damage,
        theft or injury to the tenant or tenant&rsquo;s family, licensee, invitees or servants.
    </div>

    <div class="condition">
        16. No extension of this agreement shall be implied even though the tenant should
        continue to be in possession of the said premises after the expiration of the said term.
    </div>

    <div class="condition">
        17. Any delay by the lessor in exercising any rights hereunder shall not be deemed to be
        a waiver of such rights in any way.
    </div>

    <p class="witness-text">
        IN WITNESS WHEREOF the parties hereto set their hands and seal the day and the year
        herein before mentioned.
    </p>

    <div class="sig-row">
        SIGNED: <strong>MANAGING AGENT</strong> <span class="sig-line"></span> Date <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('d/m/Y') : '' }}</span>
    </div>

    <div class="sig-row" style="margin-top: 30px;">
        SIGNED: <strong>TENANT</strong>
    </div>

    <div class="sig-row">
        Name <span class="dotted-line-medium">{{ $tenant->full_name ?? '' }}</span>
        Signature <span class="sig-line" style="width:120px;"></span>
        Date <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('d/m/Y') : '' }}</span>
    </div>

</body>
</html>
HTML;
    }

    /**
     * =========================================================================
     * COMMERCIAL — "COMMERCIAL LEASE AGREEMENT" (7-page PDF)
     * Matches: COMMERCIAL LEASE - 2022 (2) (1).pdf
     * Includes cover page, Particulars, Grant of Lease, Lessee's Covenants,
     * Lessor's Covenants, Notice, Repairs, Breach, Dispute Resolution,
     * Amendment, Headings, Governing Law, Captions, Severability,
     * Entire Agreement, Legal Fees, Second Schedule, Signatures.
     * =========================================================================
     */
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
            margin: 80px 50px 80px 50px;
        }
        @page :first {
            margin-top: 50px;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* Header for pages 2+ */
        header {
            position: fixed;
            top: -60px;
            right: 0;
            text-align: right;
        }
        .header-logo-small {
            width: 120px;
            height: auto;
        }

        /* Cover page styles */
        .cover-page {
            text-align: center;
            padding-top: 50px;
        }
        .cover-logo {
            width: 160px;
            height: auto;
            margin-bottom: 20px;
        }
        .cover-title {
            font-size: 36pt;
            font-weight: bold;
            color: #000;
            line-height: 1.2;
            margin-top: 100px;
        }

        /* Content styles */
        .section-number {
            font-weight: bold;
            margin: 25px 0 10px 0;
        }
        .section-title {
            font-weight: bold;
            margin: 25px 0 10px 0;
        }
        .particulars-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .particulars-table td {
            vertical-align: top;
            padding: 6px 5px;
        }
        .particulars-label {
            width: 150px;
            font-weight: bold;
        }
        .dotted-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 150px;
        }
        .dotted-line-short {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 80px;
        }
        .dotted-line-long {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 250px;
        }
        .clause-sub {
            margin: 8px 0 8px 25px;
            text-align: justify;
        }
        .clause-text {
            margin: 10px 0 10px 25px;
            text-align: justify;
        }

        /* Signature section */
        .sig-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 250px;
            margin-left: 20px;
        }
        .sig-row {
            margin: 15px 0;
        }
        .schedule-title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            margin: 30px 0 15px 0;
        }
        .schedule-subtitle {
            text-align: center;
            font-weight: bold;
            margin: 10px 0 15px 0;
        }
        .schedule-item {
            margin: 10px 0 10px 25px;
            text-align: justify;
        }
        .gov-notice {
            margin-top: 40px;
            border: 1px solid #000;
            padding: 10px 15px;
            font-style: italic;
            font-size: 10pt;
        }

        /* Footer */
        .page-number {
            text-align: center;
            font-size: 9pt;
            color: #666;
        }

        .page-break {
            page-break-before: always;
        }

        /* Green accent bar */
        .green-bar {
            background-color: #8BC34A;
            height: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Header on every page (logo top-right) -->
    <header>
        <img src="{{ public_path('images/chabrin-logo.png') }}" class="header-logo-small" alt="Chabrin Agencies Ltd">
    </header>

    <!-- ============================== -->
    <!-- COVER PAGE                     -->
    <!-- ============================== -->
    <div class="cover-page">
        <div style="text-align: right; margin-bottom: 50px;">
            <img src="{{ public_path('images/chabrin-logo.png') }}" class="cover-logo" alt="Chabrin Agencies Ltd">
        </div>

        <div class="cover-title">
            COMMERCIAL<br>
            LEASE<br>
            AGREEMENT
        </div>
    </div>

    <!-- ============================== -->
    <!-- PAGE 2: PARTICULARS            -->
    <!-- ============================== -->
    <div class="page-break"></div>
    <div class="green-bar"></div>

    <p class="section-number">1. &nbsp;&nbsp;&nbsp; <em>Particulars</em></p>

    <table class="particulars-table">
        <tr>
            <td class="particulars-label">Date:</td>
            <td>This Lease Agreement is dated the <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('d') : '' }}</span> day on the month of
            <span class="dotted-line">{{ $lease->start_date ? $lease->start_date->format('F') : '' }}</span>, in the year <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('Y') : '' }}</span>.</td>
        </tr>
        <tr>
            <td class="particulars-label">The Lessor:</td>
            <td><span class="dotted-line-long">{{ $landlord->name ?? '' }}</span> of Post Office
            Box Number <span class="dotted-line">{{ $landlord->postal_address ?? '' }}</span> and where the
            context so admits includes its successors in title and assigns; of
            the other part.</td>
        </tr>
        <tr>
            <td class="particulars-label">The Lessee:</td>
            <td><span class="dotted-line-long">{{ $tenant->full_name ?? '' }}</span> of ID.No
            <span class="dotted-line">{{ $tenant->id_number ?? '' }}</span>
            or Company registration no. <span class="dotted-line">{{ $tenant->company_registration ?? '' }}</span> and of
            Post Office Box Number <span class="dotted-line-short">{{ $tenant->postal_address ?? '' }}</span> Nairobi, and where the
            context so admits includes its successors in title and assigns; of
            the other part.</td>
        </tr>
        <tr>
            <td class="particulars-label">The Building:</td>
            <td>The building and improvement on the parcel identified as
            constructed on all that piece of L.R. <span class="dotted-line-long">{{ $property->lr_number ?? '' }}</span>
            Designed as <span class="dotted-line">{{ $property->name ?? $property->property_code ?? '' }} - {{ $unit->unit_number ?? '' }}</span>.</td>
        </tr>
        <tr>
            <td class="particulars-label">The Term:</td>
            <td><span class="dotted-line-short">{{ $lease->lease_term_years ?? '5' }}</span> years and <span class="dotted-line-short">{{ $lease->lease_term_months ?? '3' }}</span> months from <span class="dotted-line-short">{{ $lease->start_date ? $lease->start_date->format('d/m/Y') : '' }}</span>
            To <span class="dotted-line-short">{{ $lease->end_date ? $lease->end_date->format('d/m/Y') : '' }}</span>.</td>
        </tr>
        <tr>
            <td class="particulars-label">The Base Rent:</td>
            <td>Kshs. <span class="dotted-line">{{ number_format($lease->monthly_rent ?? 0, 2) }}</span> per month</td>
        </tr>
        <tr>
            <td class="particulars-label">Deposit:</td>
            <td>Kshs. <span class="dotted-line">{{ number_format($lease->deposit_amount ?? 0, 2) }}</span>, to be paid as security bond refundable
            after giving vacant possession and the same shall not attract
            any interest.</td>
        </tr>
        <tr>
            <td class="particulars-label">Other Charges:</td>
            <td>Security and any other charges payable by the Lessee either
            statutory or to the County Government.</td>
        </tr>
        <tr>
            <td class="particulars-label">Value Added Tax</td>
            <td>The rent shall be subjected to Value Added Tax (V.A.T) at a
            statutory rate of 16%, which translates to Kshs <span class="dotted-line-short">{{ number_format(($lease->monthly_rent ?? 0) * 0.16, 2) }}</span>
            to be paid over and above the base rent.</td>
        </tr>
        <tr>
            <td class="particulars-label">Rent In Advance:</td>
            <td>The rent shall be paid in advance on or before the 1<sup>st</sup> day of
            every month deadline by 5<sup>th</sup> (fifth) of the month due.</td>
        </tr>
        <tr>
            <td class="particulars-label">Rent Review:</td>
            <td>Shall be reviewed after each <span class="dotted-line-short">{{ $lease->rent_review_years ?? '1' }}</span> year(s) at a guide rate
            of <span class="dotted-line-short">{{ $lease->rent_review_percentage ?? '10' }}</span> %. The review shall be communicated in writing
            and in advance offering a period of 3 months&rsquo; notice.</td>
        </tr>
        <tr>
            <td class="particulars-label">Payment:</td>
            <td>All the payments will be done to <strong>Chabrin Agencies Limited</strong></td>
        </tr>
    </table>

    <!-- ============================== -->
    <!-- GRANT OF LEASE & COVENANTS     -->
    <!-- ============================== -->
    <div class="page-break"></div>
    <div class="green-bar"></div>

    <p class="section-title">2. &nbsp;&nbsp;&nbsp; Grant of Lease</p>

    <div class="clause-text">
        The Lessor leases to the Lessee for a period of <span class="dotted-line-short">{{ $lease->lease_term_years ?? '5' }} years</span> from the date of this
        Agreement all rights, easements, privileges, restrictions, covenants and stipulations of
        whatever nature affecting the Premises and subject to the payment to the Lessor of:
    </div>

    <div class="clause-sub">
        a) The rent, which shall be paid on a monthly basis, that is, in advance.
    </div>

    <div class="clause-sub">
        b) Rent shall be payable on or before the fifth (5<sup>th</sup>) day of the month when the
        rent shall be due.
    </div>

    <p class="section-title">3. &nbsp;&nbsp;&nbsp; The Lessee&rsquo;s Covenants:</p>
    <div class="clause-text">The Lessee covenants with the Lessor:</div>

    <div class="clause-sub">
        a) To pay the rents on the days prescribed and in the manner set out in this lease, not to
        exercise any right or claim to withhold rent or any right or claim to legal or equitable set
        off and if so required by the Lessor, to make such payments to the bank and account
        which the Lessor may from time to time nominate.
    </div>

    <div class="clause-sub">
        b) To pay to the suppliers and to indemnify the Lessor against all charges for electricity,
        water and other services consumed at or in relation to the allocated Premises.
    </div>

    <div class="clause-sub">
        c) To keep the Premises them in clean and habitable condition.
    </div>

    <div class="clause-sub">
        d) Not to commit waste nor make any addition or alteration to the Premises <strong><em>without prior
        written</em></strong> the consent of the Lessor. The Lessee may install internal demountable partitions
        which shall be approved by the Lessor and removed at the expiration of the Term if
        required by the Lessor and any damage to the Premises caused by the removal made
        good.
    </div>

    <div class="clause-sub">
        e) Not to neither affix to nor exhibit on the outside of the premises or to any window of the
        premises or anywhere in the Common parts any name-plate, sign, notice or
        advertisement except with approval from the Lessor.
    </div>

    <div class="clause-sub">
        f) To permit the Lessor to enter on the premises for the purpose of ascertaining that the
        covenants and conditions of this lease have been observed and performed and to carry
        out immediately all work required to comply with any notice given by the Lessor to the
        Lessee specifying any repairs, maintenance, cleaning or decoration which the Lessee
        has failed to execute in breach of the terms of this lease.
    </div>

    <div class="clause-sub">
        g) Not to transfer, charge, sub-let, part with or share possession to the lease and by
        extension, the premises, to any third party not recognized under this agreement.
    </div>

    <div class="clause-sub">
        h) To give notice to the Lessor of any defect in the premises which might give rise to an
        obligation on the Lessor to do or refrain from doing any act or thing to comply with the
        provisions of this lease or the duty of care imposed on the Lessor pursuant to the
        provisions of any law and at all times to display and maintain all notices which the Lessor
        may from time to time require to be displayed on the Premises.
    </div>

    <div class="clause-sub">
        i) At the expiration of the Term, where a renewal has not been approved, to yield up the
        Premises and in accordance with the terms of this lease and to give up all access and
        rights to use over the Premises to the Lessor.
    </div>

    <div class="clause-sub">
        j) The Lessee shall be responsible for the security of the premises, its assets and staff during
        the pendency of this lease.
    </div>

    <!-- ============================== -->
    <!-- LESSOR'S COVENANTS & NOTICE    -->
    <!-- ============================== -->

    <p class="section-title">4. &nbsp;&nbsp;&nbsp; The Lessor&rsquo;s Covenants:</p>

    <div class="clause-sub">
        a) To allow the Lessee peacefully and quietly to hold and enjoy the Premises without any
        interruption or disturbance from or by the Lessor or any person claiming under or in trust
        for the Lessor.
    </div>

    <div class="clause-sub">
        b) To keep the exterior of the premises in good repair and condition.
    </div>

    <div class="clause-sub">
        c) To notify the Lessee in writing, three (3) days in advance of any intended inspection by
        the Lessor.
    </div>

    <div class="clause-sub">
        d) Not to lease, sell, charge or in any way dispose of the premises to any other party during
        the pendency of this lease.
    </div>

    <p class="section-title">5. &nbsp;&nbsp;&nbsp; Notice</p>

    <div class="clause-text">
        Any notice or communications under or in connection with this lease shall be in writing and
        shall be delivered personally or by post to the addresses shown above or to such other
        address as the recipient may have notified to the other party in writing. Proof of posting or
        dispatch shall be deemed to be proof of receipt.
    </div>

    <div class="clause-sub">
        i) In the case of a letter, on the third business day after posting
    </div>

    <div class="clause-sub">
        ii) In the case of a telex, cable or facsimile on the business day immediately
        following the date of despatch.
    </div>

    <!-- ============================== -->
    <!-- REPAIRS THROUGH SEVERABILITY   -->
    <!-- ============================== -->

    <p class="section-title">6. &nbsp;&nbsp;&nbsp; Repairs</p>
    <div class="clause-text">
        The Lessee accepts this lease is an FRI lease under which all repairs and insurance are the
        responsibility of the tenant. The tenant will restore the property to its original state.
    </div>

    <p class="section-title">7. &nbsp;&nbsp;&nbsp; Breach</p>
    <div class="clause-text">
        Any party that does not perform its obligations in accordance to the terms set in this
        agreement shall be deemed to have breached the Agreement.
    </div>
    <div class="clause-text">
        Where a breach occurs the non-breaching party has a right to terminate the agreement
        immediately without notice. The breaching party shall pay the non-breaching party any
        outstanding amount owing at the time of termination including damages for the said breach.
    </div>

    <p class="section-title">8. &nbsp;&nbsp;&nbsp; Dispute Resolution</p>
    <div class="clause-text">
        Any differences between the parties may be resolved by mutual discussion. However, should
        there be any breach of the terms of this Agreement the non-breaching party reserves the right
        to rescind the Agreement and shall be compensated by the breaching party for any
        damages incurred due to the breach.
    </div>
    <div class="clause-text">
        The non-breaching party shall exercise any other rights it has in law when breach occurs.
    </div>

    <p class="section-title">9. &nbsp;&nbsp;&nbsp; Amendment</p>
    <div class="clause-text">
        Review and amendment of this Agreement shall be done by consent of the parties involved
        and both parties must execute the amendments as proof of consent to the changes made.
    </div>

    <p class="section-title">10. &nbsp;&nbsp;&nbsp; Headings</p>
    <div class="clause-text">
        The headings used herein are purely for convenience purposes and shall not be deemed to
        constitute part of the Agreement.
    </div>

    <p class="section-title">11. &nbsp;&nbsp;&nbsp; Governing Law</p>
    <div class="clause-text">
        This Agreement shall be governed by and construed pursuant to the laws of Kenya.
    </div>

    <p class="section-title">12. &nbsp;&nbsp;&nbsp; Captions</p>
    <div class="clause-text">
        The captions of the various Articles and Sections of this Lease are for convenience only and do
        not necessarily define, limit, describe or construe the contents of such Articles or Sections.
    </div>

    <p class="section-title">13. &nbsp;&nbsp;&nbsp; Severability</p>
    <div class="clause-text">
        If any provision of this Lease proves to be illegal, invalid or unenforceable, the remainder of this
        Lease shall not be affected by such finding, and in lieu of each provision of this Lease that is illegal,
        invalid or unenforceable, a provision will be added as part of this Lease as similar in terms to such
        illegal, invalid or unenforceable provision as may be possible and be legal, valid and
        enforceable.
    </div>

    <!-- ============================== -->
    <!-- ENTIRE AGREEMENT, LEGAL FEES   -->
    <!-- ============================== -->

    <p class="section-title">14. &nbsp;&nbsp;&nbsp; Entire Agreement; Amendment</p>
    <div class="clause-text">
        This Lease contains the entire agreement between Lessor and Lessee. No amendment, alteration,
        modification of, or addition to the Lease will be valid or binding unless expressed in writing and
        signed by Lessor and Lessee.
    </div>

    <p class="section-title">15. &nbsp;&nbsp;&nbsp; Legal Fees</p>
    <div class="clause-text">
        The cost of and incidental of preparation and completion of the Lease including stamp duty and
        registration fee shall be borne and paid by the Lessee.
    </div>

    <!-- ============================== -->
    <!-- SECOND SCHEDULE                -->
    <!-- ============================== -->

    <div class="schedule-title">SECOND SCHEDULE</div>
    <div class="schedule-subtitle">Rights granted</div>

    <div class="schedule-item">
        1. The right for the Lessee and all persons expressly or by implication authorised by the Lessee
        in common with the Lessor and all other persons having a like right to use the Common
        Parts for all proper purposes in connection with the use and enjoyment of the Premises.
    </div>

    <div class="schedule-item">
        2. The right for the Lessee and all persons expressly or by implication authorised by the Lessee
        in common with all other Lessees on the same floor of the Building as the Premises having
        a like right to use the shared parts for all proper purposes in connection with the use and
        enjoyment of the premises.
    </div>

    <div class="schedule-item">
        3. The right in common with the Lessor and all other persons having a like right, to the free
        and uninterrupted passage and running subject to temporary interruption for repair,
        alteration or replacement of water, sewage, electricity, telephone and other services or
        supplies to and from the premises in and through the pipes which are laid in on over or
        under other parts of the building and which serve the premises.
    </div>

    <div class="schedule-item">
        4. The right of support and protection for the benefit of the premises as is now enjoyed from
        all other parts of the building.
    </div>

    <div class="schedule-item">
        5. The right to display in the reception area of the Building and immediately outside the
        entrance to the premises a name-plate or sign in a position and of a size and type
        specified by the Lessor showing the Lessee&rsquo;s name and other details approved by the
        Lessor.
    </div>

    <div class="schedule-item">
        6. The right in cases of emergency only for the Lessee and all persons expressly or by
        implication authorised by the Lessee, to break and enter any Lettable Area and to have
        a right of way over such Lettable Area in order to gain access to any fire escapes of the
        Building.
    </div>

    <!-- ============================== -->
    <!-- SIGNATURE PAGE                 -->
    <!-- ============================== -->
    <div class="page-break"></div>

    <p style="text-align: justify;">
        <strong>IN WITNESS</strong> whereof the Parties have hereunto set their respective hands the day and year
        first herein before written.
    </p>

    <div style="margin-top: 30px;">
        <div class="sig-row">
            SIGNED by the said <span style="margin-left: 100px;">)</span>
        </div>
        <div class="sig-row">
            <span style="margin-left: 250px;">)</span>
        </div>
        <div class="sig-row">
            <span class="sig-line" style="width: 200px; margin-left: 0;"></span><br>
            <em>(the Lessor/Assigned agents)</em> <span style="margin-left: 50px;">)</span>
        </div>

        <div class="sig-row">
            Signature
        </div>

        <div class="sig-row" style="margin-top: 20px;">
            in the presence of : <span style="margin-left: 85px;">)</span>
        </div>

        <div class="sig-row">
            ADVOCATE
        </div>

        <div class="sig-row" style="margin-top: 40px;">
            SIGNED by the Lessee <span style="margin-left: 90px;">)</span>
        </div>

        <div class="sig-row" style="margin-top: 20px;">
            in the presence of: <span style="margin-left: 95px;">)</span>
        </div>
        <div class="sig-row">
            <span style="margin-left: 250px;">)</span>
        </div>

        <div class="sig-row" style="margin-top: 20px;">
            ADVOCATE <span style="margin-left: 150px;">)</span> <span class="sig-line"></span>
        </div>
    </div>

    <div class="gov-notice">
        <strong>As per government policy, you are required to provide the following documents prior to registration of this
        lease:</strong>
        <ol>
            <li>Copy of business or company registration</li>
            <li>K.R.A pin certificate of the business/individual</li>
            <li>Director&rsquo;s or business owner Identification Card</li>
        </ol>
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
