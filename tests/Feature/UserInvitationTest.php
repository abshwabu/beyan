<?php

namespace Tests\Feature;

use App\Domain\Auth\Mail\UserInvitationMail;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Models\UserInvitation;
use App\Domain\Institution\Audit\Models\AuditLog;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Al-Hikmah Academy',
            'slug' => 'al-hikmah',
            'contact' => ['email' => 'admin@alhikmah.edu'],
            'mode' => 'physical',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'am',
            'status' => 'active',
            'subscription_plan' => 'standard',
        ]);

        app(TenantManager::class)->setTenant($this->institution);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);

        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Sheikh Omar',
            'email' => 'omar@alhikmah.edu',
            'password' => Hash::make('password123'),
            'role' => 'owner',
        ]);

        $this->owner->assignRolesWithAudit('Owner');
    }

    public function test_can_invite_user_with_multiple_roles_and_mail_is_dispatched(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->owner)
            ->postJson('/invitations', [
                'email' => 'ustaz.khalid@example.com',
                'roles' => ['Teacher', 'Academic Coordinator'],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'invitation' => ['id', 'email', 'roles', 'token', 'status'],
                'accept_url',
            ]);

        $invitation = UserInvitation::withoutInstitutionScope()->where('email', 'ustaz.khalid@example.com')->first();
        $this->assertNotNull($invitation);
        $this->assertEquals(['Teacher', 'Academic Coordinator'], $invitation->roles);
        $this->assertEquals('pending', $invitation->status);

        Mail::assertSent(UserInvitationMail::class, function (UserInvitationMail $mail) use ($invitation) {
            return $mail->hasTo('ustaz.khalid@example.com') && $mail->invitation->id === $invitation->id;
        });
    }

    public function test_can_view_accept_invitation_page_with_valid_token(): void
    {
        $invitation = UserInvitation::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'invited_by_id' => $this->owner->id,
            'email' => 'teacher@example.com',
            'roles' => ['Teacher'],
            'token' => 'valid-test-token-12345',
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(3),
        ]);

        $response = $this->get('/invitations/valid-test-token-12345/accept');

        $response->assertStatus(200);
    }

    public function test_user_can_accept_invitation_and_acquire_multiple_scoped_roles(): void
    {
        $invitation = UserInvitation::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'invited_by_id' => $this->owner->id,
            'email' => 'multi.role@example.com',
            'roles' => ['Teacher', 'Accountant'],
            'token' => 'token-multi-role-accept',
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        $response = $this->postJson('/invitations/token-multi-role-accept/accept', [
            'name' => 'Ustaz Bilal',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Account setup complete.',
            ]);

        // User created
        $user = User::withoutInstitutionScope()->where('email', 'multi.role@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals($this->institution->id, $user->institution_id);
        $this->assertEquals('Ustaz Bilal', $user->name);

        // Roles verified with spatie
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);
        $this->assertTrue($user->hasRole('Teacher'));
        $this->assertTrue($user->hasRole('Accountant'));

        // Invitation marked as accepted
        $invitation->refresh();
        $this->assertEquals('accepted', $invitation->status);

        // Audit log created for role assignment
        $audit = AuditLog::withoutInstitutionScope()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('action', 'role_assigned')
            ->first();

        $this->assertNotNull($audit);
        $this->assertContains('Teacher', $audit->new_values['roles']);
        $this->assertContains('Accountant', $audit->new_values['roles']);

        // Authenticated as new user
        $this->assertAuthenticatedAs($user);
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $invitation = UserInvitation::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'invited_by_id' => $this->owner->id,
            'email' => 'expired@example.com',
            'roles' => ['Teacher'],
            'token' => 'expired-token-12345',
            'status' => 'pending',
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->postJson('/invitations/expired-token-12345/accept', [
            'name' => 'Expired User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_cannot_accept_already_accepted_invitation(): void
    {
        $invitation = UserInvitation::withoutInstitutionScope()->create([
            'institution_id' => $this->institution->id,
            'invited_by_id' => $this->owner->id,
            'email' => 'already.accepted@example.com',
            'roles' => ['Teacher'],
            'token' => 'accepted-token-12345',
            'status' => 'accepted',
            'expires_at' => Carbon::now()->addDays(5),
        ]);

        $response = $this->postJson('/invitations/accepted-token-12345/accept', [
            'name' => 'Already Accepted',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }
}
