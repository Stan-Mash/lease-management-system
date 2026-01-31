<?php

namespace App\Console\Commands;

use App\Models\DigitalSignature;
use App\Models\Lease;
use App\Models\OTPVerification;
use App\Models\Tenant;
use App\Services\DigitalSigningService;
use App\Services\OTPService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestSigningFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:signing-flow
                            {--lease= : Specific lease ID to test}
                            {--create : Create a new test lease}
                            {--full : Run full validation suite}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete digital signing workflow end-to-end';

    private int $passedTests = 0;

    private int $failedTests = 0;

    private array $errors = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧪 Digital Signing Flow Test Suite');
        $this->newLine();

        // Get or create lease
        $lease = $this->getTestLease();
        if (! $lease) {
            $this->error('❌ No lease available for testing');

            return Command::FAILURE;
        }

        $this->info("Testing with Lease: {$lease->reference_number}");
        $this->info("Tenant: {$lease->tenant->name} ({$lease->tenant->phone})");
        $this->newLine();

        // Run tests
        $this->testLeaseReferenceGeneration();
        $this->testOTPGeneration($lease);
        $this->testOTPVerification($lease);
        $this->testSigningLinkGeneration($lease);
        $this->testSignatureCapture($lease);
        $this->testAuditLogging($lease);
        $this->testWorkflowTransitions($lease);

        if ($this->option('full')) {
            $this->testRateLimiting($lease);
            $this->testSecurityValidations($lease);
            $this->testHashVerification();
        }

        // Display results
        $this->displayResults();

        return $this->failedTests > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function getTestLease(): ?Lease
    {
        if ($this->option('lease')) {
            return Lease::find($this->option('lease'));
        }

        if ($this->option('create')) {
            return $this->createTestLease();
        }

        // Find a suitable lease for testing (draft or sent_digital)
        $lease = Lease::whereIn('workflow_state', ['draft', 'approved', 'sent_digital'])
            ->whereDoesntHave('digitalSignatures')
            ->first();

        if (! $lease) {
            $this->warn('No suitable lease found. Creating new test lease...');

            return $this->createTestLease();
        }

        return $lease;
    }

    private function createTestLease(): Lease
    {
        $this->info('Creating test lease...');

        $tenant = Tenant::first();
        if (! $tenant) {
            $tenant = Tenant::create([
                'name' => 'Test Tenant',
                'id_number' => '99999999',
                'phone' => '+254700000000',
                'email' => 'test@example.com',
            ]);
        }

        $lease = Lease::create([
            'tenant_id' => $tenant->id,
            'lease_type' => 'commercial',
            'zone' => 'A',
            'lease_source' => 'chabrin',
            'workflow_state' => 'draft',
            'monthly_rent' => 10000,
            'security_deposit' => 20000,
            'currency' => 'KES',
            'start_date' => now()->addDays(7),
            'end_date' => now()->addYear(),
            'payment_day' => 1,
            'payment_method' => 'bank_transfer',
            'created_by' => 1,
        ]);

        $this->info("✅ Created test lease: {$lease->reference_number}");

        return $lease;
    }

    private function testLeaseReferenceGeneration(): void
    {
        $this->task('Lease Reference Generation', function () {
            try {
                // Test reference format
                $lease = Lease::first();
                $pattern = '/^LSE-(COM|RES)-[A-G]-\d{5}-\d{4}$/';

                if (! preg_match($pattern, $lease->reference_number)) {
                    throw new Exception("Invalid reference format: {$lease->reference_number}");
                }

                // Test uniqueness
                $duplicates = DB::table('leases')
                    ->select('reference_number')
                    ->groupBy('reference_number')
                    ->havingRaw('COUNT(*) > 1')
                    ->count();

                if ($duplicates > 0) {
                    throw new Exception("Found {$duplicates} duplicate reference numbers");
                }

                $this->passedTests++;

                return true;
            } catch (Exception $e) {
                $this->failedTests++;
                $this->errors[] = "Reference Generation: {$e->getMessage()}";

                return false;
            }
        });
    }

    private function testOTPGeneration(Lease $lease): void
    {
        $this->task('OTP Generation', function () use ($lease) {
            try {
                $otp = OTPService::generateAndSend($lease, $lease->tenant->phone);

                if (strlen($otp->code) !== 4) {
                    throw new Exception("Invalid OTP length: {$otp->code}");
                }

                if (! $otp->isValid()) {
                    throw new Exception('Generated OTP is not valid');
                }

                $this->passedTests++;

                return true;
            } catch (Exception $e) {
                $this->failedTests++;
                $this->errors[] = "OTP Generation: {$e->getMessage()}";

                return false;
            }
        });
    }

    private function testOTPVerification(Lease $lease): void
    {
        $this->task('OTP Verification', function () use ($lease) {
            try {
                // Get latest OTP
                $otp = OTPVerification::forLease($lease->id)->valid()->first();

                if (! $otp) {
                    throw new Exception('No valid OTP found for lease');
                }

                // Test correct verification
                $verified = OTPService::verify($lease, $otp->code, '127.0.0.1');

                if (! $verified) {
                    throw new Exception('OTP verification failed with correct code');
                }

                // Test incorrect code
                $verified = OTPService::verify($lease, '0000', '127.0.0.1');

                if ($verified) {
                    throw new Exception('OTP verification succeeded with wrong code');
                }

                $this->passedTests++;

                return true;
            } catch (Exception $e) {
                $this->failedTests++;
                $this->errors[] = "OTP Verification: {$e->getMessage()}";

                return false;
            }
        });
    }

    private function testSigningLinkGeneration(Lease $lease): void
    {
        $this->task('Signing Link Generation', function () use ($lease) {
            try {
                $link = DigitalSigningService::generateSigningLink($lease);

                if (! filter_var($link, FILTER_VALIDATE_URL)) {
                    throw new Exception("Invalid URL generated: {$link}");
                }

                if (! str_contains($link, 'signature=')) {
                    throw new Exception('Signing URL missing signature parameter');
                }

                if (! str_contains($link, 'expires=')) {
                    throw new Exception('Signing URL missing expiry parameter');
                }

                $this->passedTests++;

                return true;
            } catch (Exception $e) {
                $this->failedTests++;
                $this->errors[] = "Signing Link: {$e->getMessage()}";

                return false;
            }
        });
    }

    private function testSignatureCapture(Lease $lease): void
    {
        $this->task('Signature Capture', function () use ($lease) {
            try {
                // Generate test signature data
                $signatureData = 'data:image/png;base64,' . base64_encode('test_signature_data');

                $signature = DigitalSigningService::captureSignature($lease, [
                    'signature_data' => $signatureData,
                    'signature_type' => 'canvas',
                    'latitude' => -1.286389,
                    'longitude' => 36.817223,
                ]);

                if (! $signature->verification_hash) {
                    throw new Exception('Signature hash not generated');
                }

                if (! $signature->verifyHash()) {
                    throw new Exception('Signature hash verification failed');
                }

                if ($lease->workflow_state !== 'tenant_signed') {
                    throw new Exception('Workflow state not updated after signing');
                }

                $this->passedTests++;

                return true;
            } catch (Exception $e) {
                $this->failedTests++;
                $this->errors[] = "Signature Capture: {$e->getMessage()}";

                return false;
            }
        });
    }

    private function testAuditLogging(Lease $lease): void
    {
        $this->task('Audit Logging', function () use ($lease) {
            try {
                $auditCount = $lease->auditLogs()->count();

                if ($auditCount === 0) {
                    throw new Exception('No audit logs created for lease');
                }

                $latestLog = $lease->auditLogs()->latest()->first();

                if (! $latestLog->formatted_description) {
                    throw new Exception('Audit log missing formatted description');
                }

                $this->passedTests++;

                return true;
            } catch (Exception $e) {
                $this->failedTests++;
                $this->errors[] = "Audit Logging: {$e->getMessage()}";

                return false;
            }
        });
    }

    private function testWorkflowTransitions(Lease $lease): void
    {
        $this->task('Workflow Transitions', function () use ($lease) {
            try {
                $transitions = [
                    'draft' => ['pending_landlord', 'approved'],
                    'approved' => ['sent_digital', 'sent_physical'],
                    'sent_digital' => ['pending_otp'],
                    'pending_otp' => ['tenant_signed'],
                ];

                // Check if current state has valid transitions
                if (! isset($transitions[$lease->workflow_state]) && $lease->workflow_state !== 'tenant_signed') {
                    $this->warn("Lease in terminal state: {$lease->workflow_state}");
                }

                $this->passedTests++;

                return true;
            } catch (Exception $e) {
                $this->failedTests++;
                $this->errors[] = "Workflow Transitions: {$e->getMessage()}";

                return false;
            }
        });
    }

    private function testRateLimiting(Lease $lease): void
    {
        $this->task('OTP Rate Limiting', function () use ($lease) {
            try {
                // Clear existing OTPs
                OTPVerification::where('lease_id', $lease->id)->delete();

                // Try to generate 4 OTPs rapidly
                $generated = 0;
                for ($i = 0; $i < 4; $i++) {
                    try {
                        OTPService::generateAndSend($lease, $lease->tenant->phone);
                        $generated++;
                    } catch (Exception $e) {
                        if ($i < 3) {
                            throw new Exception("Rate limiting triggered too early at attempt {$i}");
                        }
                        // Expected to fail on 4th attempt
                        break;
                    }
                }

                if ($generated >= 4) {
                    throw new Exception("Rate limiting not working - generated {$generated} OTPs");
                }

                $this->passedTests++;

                return true;
            } catch (Exception $e) {
                $this->failedTests++;
                $this->errors[] = "Rate Limiting: {$e->getMessage()}";

                return false;
            }
        });
    }

    private function testSecurityValidations(Lease $lease): void
    {
        $this->task('Security Validations', function () {
            try {
                // Test signature capture without OTP
                $leaseWithoutOTP = Lease::whereDoesntHave('otpVerifications')->first();

                if ($leaseWithoutOTP && DigitalSigningService::canSign($leaseWithoutOTP)) {
                    throw new Exception('Security breach: Can sign without OTP verification');
                }

                $this->passedTests++;

                return true;
            } catch (Exception $e) {
                $this->failedTests++;
                $this->errors[] = "Security: {$e->getMessage()}";

                return false;
            }
        });
    }

    private function testHashVerification(): void
    {
        $this->task('Hash Verification', function () {
            try {
                $signatures = DigitalSignature::limit(10)->get();

                foreach ($signatures as $signature) {
                    if (! $signature->verifyHash()) {
                        throw new Exception("Hash verification failed for signature {$signature->id}");
                    }
                }

                $this->passedTests++;

                return true;
            } catch (Exception $e) {
                $this->failedTests++;
                $this->errors[] = "Hash Verification: {$e->getMessage()}";

                return false;
            }
        });
    }

    private function displayResults(): void
    {
        $this->newLine();
        $this->info('📊 Test Results');
        $this->newLine();

        $total = $this->passedTests + $this->failedTests;
        $percentage = $total > 0 ? round(($this->passedTests / $total) * 100, 1) : 0;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Tests', $total],
                ['Passed', "<fg=green>{$this->passedTests}</>"],
                ['Failed', $this->failedTests > 0 ? "<fg=red>{$this->failedTests}</>" : '0'],
                ['Success Rate', "{$percentage}%"],
            ],
        );

        if (! empty($this->errors)) {
            $this->newLine();
            $this->error('❌ Failed Tests:');
            foreach ($this->errors as $error) {
                $this->line("  • {$error}");
            }
        }

        if ($this->failedTests === 0) {
            $this->newLine();
            $this->info('🎉 All tests passed! System is ready for production.');
        } else {
            $this->newLine();
            $this->warn('⚠️  Some tests failed. Please review and fix issues before proceeding.');
        }
    }
}
