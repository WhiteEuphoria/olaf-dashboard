<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\FraudClaim;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['integration.theme_routes' => true]);
        config(['filesystems.documents_disk' => 'documents_test']);
        Storage::fake('documents_test');
    }

    public function test_user_can_register_with_documents(): void
    {
        Storage::fake('public');

        $response = $this->post(route('user.register.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'documents' => [
                UploadedFile::fake()->create('passport.pdf', 120, 'application/pdf'),
                UploadedFile::fake()->image('selfie.jpg'),
            ],
        ]);

        $response->assertRedirect(route('user.verify'));
        $this->assertAuthenticated();

        $user = User::whereEmail('jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('pending', $user->verification_status);

        $documents = Document::where('user_id', $user->id)->get();
        $this->assertCount(2, $documents);

        $documentsDisk = Document::storageDisk();
        foreach ($documents as $document) {
            Storage::disk($documentsDisk)->assertExists($document->path);
            $this->assertSame('pending', $document->status);
        }
    }

    public function test_pending_user_redirected_to_verify_from_dashboard(): void
    {
        $user = User::factory()->create([
            'verification_status' => 'pending',
            'password' => Hash::make('Password123!'),
        ]);

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertRedirect(route('user.verify'));
    }

    public function test_login_redirects_pending_user_to_verify(): void
    {
        $user = User::factory()->create([
            'verification_status' => 'pending',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->post(route('user.login.attempt'), [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('user.verify'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_approved_user_can_submit_card_withdrawal(): void
    {
        $user = User::factory()->create([
            'verification_status' => 'approved',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->actingAs($user)
            ->from(route('user.withdraw'))
            ->post(route('user.withdraw.store'), [
            'method' => 'card',
            'card' => [
                'number' => '4111 2222 3333 4444',
                'holder' => 'Test Holder',
            ],
            'amount' => 150,
        ]);

        $response->assertRedirect(route('user.withdraw'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'method' => 'card',
            'amount' => 150,
        ]);

        $withdrawal = Withdrawal::where('user_id', $user->id)->first();
        $this->assertNotNull($withdrawal);
        $requisites = json_decode($withdrawal->requisites ?? '[]', true);
        $this->assertSame('4111222233334444', $requisites['card_number'] ?? null);
        $this->assertSame('Test Holder', $requisites['card_holder'] ?? null);
    }

    public function test_user_can_submit_violation_with_attachments(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'verification_status' => 'approved',
            'password' => Hash::make('Password123!'),
        ]);

        $files = [
            UploadedFile::fake()->create('screenshot.pdf', 120, 'application/pdf'),
            UploadedFile::fake()->image('proof.png'),
        ];

        $response = $this->actingAs($user)
            ->from(route('user.dashboard'))
            ->post(route('user.violation.store'), [
                'details' => 'I want to report a suspicious activity with attachments.',
                'attachments' => $files,
            ]);

        $response->assertRedirect(route('user.dashboard'));
        $response->assertSessionHas('violation_status');

        $claim = FraudClaim::with('attachments')->where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($claim);
        $this->assertSame('I want to report a suspicious activity with attachments.', $claim->details);
        $this->assertCount(2, $claim->attachments);

        foreach ($claim->attachments as $index => $attachment) {
            Storage::disk('public')->assertExists($attachment->path);
            $this->assertSame($files[$index]->getClientOriginalName(), $attachment->original_name);
            $this->assertSame('public', $attachment->disk);
        }

        $historyResponse = $this->actingAs($user)->get(route('user.violation'));
        $historyResponse->assertOk();
        $historyResponse->assertSee('История отправленных обращений', false);
        $historyResponse->assertSee('screenshot.pdf', false);
        $historyResponse->assertSee('proof.png', false);
    }

    public function test_pending_user_cannot_submit_withdrawal(): void
    {
        $user = User::factory()->create([
            'verification_status' => 'pending',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->actingAs($user)
            ->from(route('user.withdraw'))
            ->post(route('user.withdraw.store'), [
            'method' => 'card',
            'card' => [
                'number' => '4111 2222 3333 4444',
                'holder' => 'Pending User',
            ],
            'amount' => 99,
        ]);

        $response->assertRedirect(route('user.verify'));
        $this->assertDatabaseMissing('withdrawals', [
            'user_id' => $user->id,
        ]);
    }

    public function test_approved_user_can_view_transactions_index(): void
    {
        $user = User::factory()->create([
            'verification_status' => 'approved',
            'currency' => 'USD',
            'password' => Hash::make('Password123!'),
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'from' => 'Wallet 1234567890',
            'to' => 'Broker 9988776655',
            'type' => 'classic',
            'amount' => 250.75,
            'status' => 'approved',
            'currency' => 'USD',
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->get(route('user.transactions'));

        $response->assertOk();
        $response->assertSee('All transactions', false);
        $response->assertSee('CLASSIC', false);
        $response->assertSee('250.75', false);
    }

    public function test_pending_user_is_redirected_from_transactions_index(): void
    {
        $user = User::factory()->create([
            'verification_status' => 'pending',
            'password' => Hash::make('Password123!'),
        ]);

        $this->actingAs($user)
            ->get(route('user.transactions'))
            ->assertRedirect(route('user.verify'));
    }

    public function test_user_can_submit_multiple_violation_reports(): void
    {
        $user = User::factory()->create([
            'verification_status' => 'approved',
            'password' => Hash::make('Password123!'),
        ]);

        $firstPayload = ['details' => 'First violation description goes here.'];
        $secondPayload = ['details' => 'Second violation message for admin review.'];

        $this->actingAs($user)
            ->from(route('user.dashboard'))
            ->post(route('user.violation.store'), $firstPayload)
            ->assertRedirect(route('user.dashboard'))
            ->assertSessionHas('violation_status');

        $this->actingAs($user)
            ->from(route('user.dashboard'))
            ->post(route('user.violation.store'), $secondPayload)
            ->assertRedirect(route('user.dashboard'))
            ->assertSessionHas('violation_status');

        $claims = FraudClaim::where('user_id', $user->id)->orderBy('id')->get();

        $this->assertCount(2, $claims);
        $this->assertSame('First violation description goes here.', $claims->first()->details);
        $this->assertSame('Second violation message for admin review.', $claims->last()->details);
    }

    public function test_user_can_logout_via_route(): void
    {
        $user = User::factory()->create([
            'verification_status' => 'approved',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->actingAs($user)->post(route('user.logout'));

        $response->assertRedirect(route('user.login'));
        $this->assertGuest();
    }
}
