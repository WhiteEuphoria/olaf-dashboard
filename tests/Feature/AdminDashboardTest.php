<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Document;
use App\Models\FraudClaim;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected string $documentsDisk;

    protected function setUp(): void
    {
        parent::setUp();

        config(['integration.theme_routes' => true]);
        config(['filesystems.documents_disk' => 'documents_test']);
        Storage::fake('documents_test');
        Storage::fake('public');

        $this->documentsDisk = Document::storageDisk();
    }

    public function test_admin_can_switch_between_clients(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'currency' => 'EUR',
            'main_balance' => 0,
            'verification_status' => 'approved',
        ]);

        $firstClient = User::factory()->create([
            'name' => 'Alice Client',
            'is_admin' => false,
            'currency' => 'USD',
            'main_balance' => 1500,
            'verification_status' => 'approved',
        ]);

        $secondClient = User::factory()->create([
            'name' => 'Bob Client',
            'is_admin' => false,
            'currency' => 'GBP',
            'main_balance' => 2750,
            'verification_status' => 'approved',
        ]);

        Account::create([
            'user_id' => $secondClient->id,
            'number' => 'ACC-2001',
            'type' => 'Classic',
            'organization' => 'Acme Corp',
            'client_initials' => 'B. C.',
            'broker_initials' => 'Agent',
            'term' => now()->addYear(),
            'status' => 'Active',
            'balance' => 9999.99,
            'currency' => 'GBP',
            'is_default' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['user' => $secondClient->id]));

        $response->assertOk();
        $response->assertViewHas('selectedUserId', $secondClient->id);
        $response->assertSee('Bob Client', false);
        $response->assertSee('ACC-2001', false);
        $response->assertSee("value=\"{$secondClient->id}\" selected", false);
    }

    public function test_dashboard_shows_pending_user_select(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $approved = User::factory()->create([
            'name' => 'Approved Client',
            'is_admin' => false,
            'verification_status' => 'approved',
            'currency' => 'EUR',
            'main_balance' => 0,
        ]);

        $pending = User::factory()->create([
            'name' => 'Pending Client',
            'is_admin' => false,
            'verification_status' => 'pending',
            'currency' => 'USD',
            'main_balance' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Pending user', false);
        $response->assertSee('All users', false);
        $response->assertSee("value=\"{$pending->id}\"", false);
        $response->assertSee("value=\"{$approved->id}\"", false);
    }

    public function test_pending_user_select_hidden_when_no_pending(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        User::factory()->create([
            'is_admin' => false,
            'verification_status' => 'approved',
            'currency' => 'EUR',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Pending user', false);
    }

    public function test_dashboard_defaults_to_first_client_when_selected_user_missing(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'currency' => 'EUR',
            'main_balance' => 0,
            'verification_status' => 'approved',
        ]);

        $firstClient = User::factory()->create([
            'name' => 'First Client',
            'is_admin' => false,
            'currency' => 'EUR',
            'main_balance' => 1000,
            'verification_status' => 'approved',
        ]);

        $secondClient = User::factory()->create([
            'name' => 'Second Client',
            'is_admin' => false,
            'currency' => 'USD',
            'main_balance' => 2000,
            'verification_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['user' => 99999]));

        $response->assertOk();
        $response->assertViewHas('selectedUserId', function ($value) use ($firstClient, $secondClient) {
            return in_array($value, [$firstClient->id, $secondClient->id], true);
        });
        $response->assertSee('First Client', false);
        $response->assertSee('Second Client', false);
    }

    public function test_admin_can_update_user_from_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $client = User::factory()->create([
            'is_admin' => false,
            'currency' => 'EUR',
            'main_balance' => 1000,
            'verification_status' => 'pending',
            'password' => Hash::make('Secret123'),
        ]);

        $account = Account::create([
            'user_id' => $client->id,
            'number' => 'ACC-001',
            'type' => 'Classic',
            'organization' => 'Org',
            'client_initials' => 'C. I.',
            'broker_initials' => 'B. I.',
            'term' => now()->addMonth(),
            'status' => 'Active',
            'balance' => 1000,
            'currency' => 'EUR',
            'is_default' => true,
        ]);

        $payload = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'NewPassword123',
            'currency' => 'USD',
            'main_balance' => 1500,
            'verification_status' => 'approved',
            'created_at' => now()->format('Y-m-d\TH:i'),
        ];

        $response = $this->actingAs($admin)->from(route('admin.dashboard', ['user' => $client->id]))
            ->put(route('admin.dashboard.users.update', $client), $payload);
        $response->assertRedirect(route('admin.dashboard', ['user' => $client->id]));

        $client->refresh();
        $this->assertSame('Updated Name', $client->name);
        $this->assertSame('updated@example.com', $client->email);
        $this->assertSame('USD', $client->currency);
        $this->assertSame(1500.0, (float) $client->main_balance);
        $this->assertSame('approved', $client->verification_status);
        $this->assertTrue(Hash::check('NewPassword123', $client->password));

        $account->refresh();
        $this->assertSame(1500.0, (float) $account->balance);
        $this->assertSame('USD', $account->currency);
    }

    public function test_admin_can_update_account_from_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['is_admin' => false, 'currency' => 'EUR', 'main_balance' => 500]);

        $account = Account::create([
            'user_id' => $client->id,
            'number' => 'ACC-100',
            'type' => 'Classic',
            'organization' => 'Old Org',
            'client_initials' => 'O. O.',
            'broker_initials' => 'B. B.',
            'term' => now()->addMonth(),
            'status' => 'Active',
            'balance' => 500,
            'currency' => 'EUR',
            'is_default' => false,
        ]);

        $newTerm = now()->addMonthsNoOverflow(2)->format('Y-m-d');

        $payload = [
            'editing_account_id' => $account->id,
            'number' => 'ACC-200',
            'type' => 'Classic',
            'balance' => 750,
            'currency' => 'USD',
            'organization' => 'New Org',
            'bank' => 'New Bank',
            'client_initials' => 'N. O.',
            'broker_initials' => 'N. B.',
            'term' => $newTerm,
            'status' => 'Hold',
            'is_default' => '1',
        ];

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard', ['user' => $client->id, 'account' => $account->id]))
            ->put(route('admin.dashboard.accounts.update', $account), $payload);
        $response->assertRedirect(route('admin.dashboard', ['user' => $client->id, 'account' => $account->id]));

        $account->refresh();
        $this->assertSame('ACC-200', $account->number);
        $this->assertSame('Classic', $account->type);
        $this->assertSame(750.0, (float) $account->balance);
        $this->assertSame('USD', $account->currency);
        $this->assertSame('New Org', $account->organization);
        $this->assertSame('New Bank', $account->bank);
        $this->assertSame('N. O.', $account->client_initials);
        $this->assertSame('N. B.', $account->broker_initials);
        $this->assertSame('Hold', $account->status);
        $this->assertTrue($account->is_default);
    }

    public function test_admin_can_update_transaction_from_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['is_admin' => false, 'currency' => 'EUR', 'main_balance' => 1000]);
        $account = Account::create([
            'user_id' => $client->id,
            'number' => 'ACC-500',
            'type' => 'Classic',
            'organization' => 'Org',
            'client_initials' => 'C. I.',
            'broker_initials' => 'B. I.',
            'term' => now()->addMonth(),
            'status' => 'Active',
            'balance' => 1000,
            'currency' => 'EUR',
            'is_default' => true,
        ]);

        $transaction = Transaction::create([
            'user_id' => $client->id,
            'account_id' => $account->id,
            'from' => 'Old From',
            'to' => 'Old To',
            'type' => 'classic',
            'amount' => 100,
            'status' => 'pending',
            'currency' => 'EUR',
            'created_at' => now()->subDay(),
        ]);

        $newCreatedAt = now()->addMinutes(5);

        $payload = [
            'editing_transaction_id' => $transaction->id,
            'created_at' => $newCreatedAt->format('Y-m-d\TH:i'),
            'account_id' => $account->id,
            'from' => 'New From',
            'to' => 'New To',
            'type' => 'fast',
            'amount' => 250,
            'status' => 'approved',
        ];

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard', ['user' => $client->id]))
            ->put(route('admin.dashboard.transactions.update', $transaction), $payload);
        $response->assertRedirect(route('admin.dashboard', ['user' => $client->id]));

        $transaction->refresh();
        $this->assertSame('New From', $transaction->from);
        $this->assertSame('New To', $transaction->to);
        $this->assertSame('fast', $transaction->type);
        $this->assertSame('approved', $transaction->status);
        $this->assertSame(250.0, (float) $transaction->amount);
        $this->assertSame($payload['created_at'], $transaction->created_at->format('Y-m-d\TH:i'));
        $this->assertSame('EUR', $transaction->currency);
    }

    public function test_admin_can_create_transaction_from_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['is_admin' => false, 'currency' => 'EUR']);
        $account = Account::create([
            'user_id' => $client->id,
            'number' => 'ACC-900',
            'type' => 'Classic',
            'organization' => 'Org',
            'client_initials' => 'C. I.',
            'broker_initials' => 'B. I.',
            'term' => now()->addMonth(),
            'status' => 'Active',
            'balance' => 1000,
            'currency' => 'EUR',
            'is_default' => true,
        ]);

        $payload = [
            'user_id' => $client->id,
            'account_id' => $account->id,
            'from' => 'Sender',
            'to' => 'Receiver',
            'type' => 'classic',
            'amount' => 345.67,
            'status' => 'pending',
            'created_at' => now()->format('Y-m-d\TH:i'),
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.dashboard.transactions.store'), $payload);

        $response->assertRedirect(route('admin.dashboard', ['user' => $client->id]));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $client->id,
            'account_id' => $account->id,
            'from' => 'Sender',
            'to' => 'Receiver',
            'type' => 'classic',
            'amount' => 345.67,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_update_withdrawal_from_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['is_admin' => false, 'currency' => 'EUR']);

        $account = Account::create([
            'user_id' => $client->id,
            'number' => 'ACC-9001',
            'type' => 'Classic',
            'organization' => 'Sample Org',
            'client_initials' => 'J. D.',
            'broker_initials' => 'Agent',
            'term' => now()->addYear(),
            'status' => 'Active',
            'balance' => 5000,
            'currency' => 'EUR',
        ]);

        $withdrawal = Withdrawal::create([
            'user_id' => $client->id,
            'amount' => 100,
            'status' => 'pending',
            'method' => 'card',
            'from_account_id' => null,
            'requisites' => json_encode([
                'card_number' => '4000 0000 0000 0002',
                'card_holder' => 'Jane Doe',
            ]),
        ]);

        $payload = [
            'editing_withdrawal_id' => $withdrawal->id,
            'method' => 'bank',
            'from_account_id' => (string) $account->id,
            'amount' => 200,
            'status' => 'rejected',
            'requisites' => [
                'iban' => 'DE89370400440532013000',
                'bic' => 'COBADEFFXXX',
                'bank_name' => 'Commerzbank',
                'account_holder' => 'John Doe',
            ],
        ];

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard', ['user' => $client->id]))
            ->put(route('admin.dashboard.withdrawals.update', $withdrawal), $payload);
        $response->assertRedirect(route('admin.dashboard', ['user' => $client->id]));

        $withdrawal->refresh();
        $this->assertSame(200.0, (float) $withdrawal->amount);
        $this->assertSame('rejected', $withdrawal->status);
        $this->assertSame('bank', $withdrawal->method);
        $this->assertSame($account->id, $withdrawal->from_account_id);

        $decoded = json_decode($withdrawal->requisites ?? '[]', true);
        $this->assertSame('DE89370400440532013000', $decoded['iban'] ?? null);
        $this->assertSame('COBADEFFXXX', $decoded['bic'] ?? null);
    }

    public function test_admin_can_create_fraud_claim_with_attachments(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['is_admin' => false]);

        $files = [
            UploadedFile::fake()->image('screenshot-1.png'),
            UploadedFile::fake()->create('evidence.pdf', 80, 'application/pdf'),
        ];

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard', ['user' => $client->id]))
            ->post(route('admin.dashboard.fraud-claims.store'), [
                'user_id' => $client->id,
                'details' => 'Admin created claim with files',
                'attachments' => $files,
            ]);

        $response->assertRedirect(route('admin.dashboard', ['user' => $client->id]));

        $claim = FraudClaim::with('attachments')->where('user_id', $client->id)->latest()->first();
        $this->assertNotNull($claim);
        $this->assertSame('Admin created claim with files', $claim->details);
        $this->assertCount(2, $claim->attachments);

        foreach ($claim->attachments as $index => $attachment) {
            Storage::disk('public')->assertExists($attachment->path);
            $this->assertSame($files[$index]->getClientOriginalName(), $attachment->original_name);
        }
    }

    public function test_admin_can_manage_fraud_claim_attachments(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['is_admin' => false]);

        $claim = FraudClaim::create([
            'user_id' => $client->id,
            'details' => 'Initial details',
            'status' => 'pending',
        ]);

        $initialFiles = [
            UploadedFile::fake()->image('initial.png'),
            UploadedFile::fake()->create('initial.pdf', 40, 'application/pdf'),
        ];

        $claim->addAttachments($initialFiles);
        $claim->refresh();

        $oldAttachment = $claim->attachments->first();
        Storage::disk('public')->assertExists($oldAttachment->path);

        $newEvidence = UploadedFile::fake()->create('new-evidence.pdf', 55, 'application/pdf');

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard', ['user' => $client->id]))
            ->put(route('admin.dashboard.fraud-claims.update', $claim), [
                'editing_fraud_claim_id' => $claim->id,
                'details' => 'Updated with new evidence',
                'status' => 'approved',
                'attachments' => [$newEvidence],
                'remove_attachments' => [$oldAttachment->id],
            ]);

        $response->assertRedirect(route('admin.dashboard', ['user' => $client->id]));

        Storage::disk('public')->assertMissing($oldAttachment->path);

        $claim->refresh()->load('attachments');
        $this->assertSame('Updated with new evidence', $claim->details);
        $this->assertSame('approved', $claim->status);
        $this->assertCount(2, $claim->attachments);
        $this->assertTrue($claim->attachments->contains(fn ($attachment) => $attachment->original_name === 'new-evidence.pdf'));
    }

    public function test_admin_can_update_document_from_dashboard(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['is_admin' => false]);

        Storage::disk($this->documentsDisk)->put('documents/old.pdf', 'old');

        $document = Document::create([
            'user_id' => $client->id,
            'path' => 'documents/old.pdf',
            'original_name' => 'old.pdf',
        ]);

        $file = UploadedFile::fake()->create('new.pdf', 10);

        $payload = [
            'editing_document_id' => $document->id,
            'file' => $file,
            'document_type' => 'Passport',
            'status' => 'approved',
        ];

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard', ['user' => $client->id]))
            ->put(route('admin.dashboard.documents.update', $document), $payload);
        $response->assertRedirect(route('admin.dashboard', ['user' => $client->id]));

        $document->refresh();
        Storage::disk($this->documentsDisk)->assertMissing('documents/old.pdf');
        Storage::disk($this->documentsDisk)->assertExists($document->path);
        $this->assertSame('new.pdf', $document->original_name);
        $this->assertSame('Passport', $document->document_type);
        $this->assertSame('approved', $document->status);
    }

    public function test_admin_can_create_document_from_dashboard(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['is_admin' => false]);

        $fileA = UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf');
        $fileB = UploadedFile::fake()->image('passport.jpg', 300, 200);

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard', ['user' => $client->id]))
            ->post(route('admin.dashboard.documents.store'), [
                'user_id' => $client->id,
                'files' => [$fileA, $fileB],
                'document_type' => 'Statement',
                'status' => 'pending',
            ]);

        $response->assertRedirect(route('admin.dashboard', ['user' => $client->id]));

        $documents = Document::where('user_id', $client->id)->get();

        $this->assertCount(2, $documents);
        $this->assertTrue($documents->pluck('original_name')->contains('statement.pdf'));
        $this->assertTrue($documents->pluck('original_name')->contains('passport.jpg'));
        $documentsDisk = $this->documentsDisk;
        foreach ($documents as $document) {
            $this->assertSame('Statement', $document->document_type);
            $this->assertSame('pending', $document->status);
            Storage::disk($documentsDisk)->assertExists($document->path);
        }
    }

    public function test_admin_can_preview_document_image(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['is_admin' => false]);

        Storage::fake('public');

        $file = UploadedFile::fake()->image('passport.jpg', 300, 200);
        $storedPath = $file->storeAs('documents', 'passport.jpg', $this->documentsDisk);

        $document = Document::create([
            'user_id' => $client->id,
            'path' => $storedPath,
            'original_name' => 'passport.jpg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.documents.preview', $document));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $response->assertHeader('Content-Disposition', 'inline; filename="passport.jpg"');
    }

    public function test_admin_can_delete_user_from_dashboard(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['is_admin' => false]);

        $documentFile = UploadedFile::fake()->create('identity.pdf', 50, 'application/pdf');
        $documentPath = $documentFile->store('documents', $this->documentsDisk);

        Document::create([
            'user_id' => $client->id,
            'path' => $documentPath,
            'original_name' => 'identity.pdf',
            'document_type' => 'statement',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard', ['user' => $client->id]))
            ->delete(route('admin.dashboard.users.destroy', $client));

        $response->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('users', ['id' => $client->id]);
        $this->assertDatabaseMissing('documents', ['user_id' => $client->id]);
        Storage::disk($this->documentsDisk)->assertMissing($documentPath);
    }

    public function test_admin_can_update_fraud_claim_from_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['is_admin' => false]);

        $claim = FraudClaim::create([
            'user_id' => $client->id,
            'details' => 'Old details',
            'status' => 'pending',
        ]);

        $payload = [
            'editing_fraud_claim_id' => $claim->id,
            'details' => 'Updated details',
            'status' => 'approved',
        ];

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard', ['user' => $client->id]))
            ->put(route('admin.dashboard.fraud-claims.update', $claim), $payload);
        $response->assertRedirect(route('admin.dashboard', ['user' => $client->id]));

        $claim->refresh();
        $this->assertSame('Updated details', $claim->details);
        $this->assertSame('approved', $claim->status);
    }
}
