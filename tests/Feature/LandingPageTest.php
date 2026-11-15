<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Finance\Models\Plan;
use App\Domain\Institution\Models\Institution;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::create([
            'name' => 'Starter (Nour)',
            'slug' => 'starter',
            'monthly_price' => 60000,
            'yearly_price' => 600000,
            'currency' => 'ETB',
            'student_limit' => 50,
            'features' => ['Up to 50 active students', 'Hifz progress tracking', 'Session attendance'],
            'badge' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_guest_can_view_landing_page_with_plans_and_content(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Landing')
            ->has('plans', 1)
            ->where('plans.0.name', 'Starter (Nour)')
            ->where('plans.0.monthly_price_major', 600)
            ->where('plans.0.currency', 'ETB')
            ->has('content.hero')
            ->has('content.problems')
            ->has('content.features')
            ->has('content.modes')
            ->has('content.how_it_works')
            ->has('content.pricing')
            ->has('content.faq')
            ->has('content.demo')
            ->has('content.footer')
            ->where('locale', 'en')
            ->where('direction', 'ltr')
        );
    }

    public function test_authenticated_tenant_user_is_redirected_to_dashboard(): void
    {
        $institution = Institution::create([
            'name' => 'Darul Uloom',
            'slug' => 'darul-uloom',
            'mode' => 'physical',
            'status' => 'active',
            'locale' => 'en',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
        ]);

        $user = User::factory()->create([
            'institution_id' => $institution->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_authenticated_platform_super_admin_is_redirected_to_platform_institutions(): void
    {
        $superAdmin = User::factory()->create([
            'institution_id' => null,
            'role' => 'platform_super_admin',
        ]);

        $response = $this->actingAs($superAdmin)->get('/');

        $response->assertRedirect(route('platform.institutions.index'));
    }

    public function test_locale_switcher_changes_language_and_direction(): void
    {
        // Switch to Arabic (ar) -> RTL
        $switchResponse = $this->get('/locale/ar');
        $switchResponse->assertRedirect();
        $this->assertEquals('ar', session('locale'));

        $landingResponse = $this->get('/');
        $landingResponse->assertOk();
        $landingResponse->assertInertia(fn (Assert $page) => $page
            ->component('Landing')
            ->where('locale', 'ar')
            ->where('direction', 'rtl')
        );

        // Switch to Amharic (am) -> LTR
        $this->get('/locale/am');
        $this->assertEquals('am', session('locale'));

        $amLanding = $this->get('/');
        $amLanding->assertOk();
        $amLanding->assertInertia(fn (Assert $page) => $page
            ->component('Landing')
            ->where('locale', 'am')
            ->where('direction', 'ltr')
        );

        // Switch to Afaan Oromo (om) -> LTR
        $this->get('/locale/om');
        $this->assertEquals('om', session('locale'));

        $omLanding = $this->get('/');
        $omLanding->assertOk();
        $omLanding->assertInertia(fn (Assert $page) => $page
            ->component('Landing')
            ->where('locale', 'om')
            ->where('direction', 'ltr')
        );
    }

    public function test_demo_request_form_submits_and_stores_in_database(): void
    {
        $payload = [
            'name' => 'Ustadh Bilal',
            'madrasa_name' => 'An-Nur Academy',
            'phone' => '+251 91 234 5678',
            'email' => 'bilal@annur.edu.et',
            'mode' => 'hybrid',
            'student_count_range' => '150_300',
            'message' => 'We want to migrate from paper registers.',
            'hp_website' => '', // Empty honeypot
        ];

        $response = $this->post('/demo-request', $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('demo_requests', [
            'name' => 'Ustadh Bilal',
            'madrasa_name' => 'An-Nur Academy',
            'phone' => '+251 91 234 5678',
            'email' => 'bilal@annur.edu.et',
            'mode' => 'hybrid',
            'student_count_range' => '150_300',
            'status' => 'new',
        ]);
    }

    public function test_demo_request_honeypot_discards_spam_silently(): void
    {
        $payload = [
            'name' => 'Spam Bot',
            'madrasa_name' => 'Spam Academy',
            'phone' => '+1 800 000 0000',
            'email' => 'bot@spammer.com',
            'mode' => 'physical',
            'student_count_range' => 'under_50',
            'message' => 'Buy cheap links now',
            'hp_website' => 'http://spam-link.com', // Filled honeypot
        ];

        $response = $this->post('/demo-request', $payload);

        $response->assertRedirect();
        $this->assertDatabaseMissing('demo_requests', [
            'email' => 'bot@spammer.com',
        ]);
    }

    public function test_demo_request_validates_required_fields(): void
    {
        $response = $this->post('/demo-request', []);

        $response->assertSessionHasErrors([
            'name',
            'madrasa_name',
            'phone',
            'mode',
            'student_count_range',
        ]);
    }

    public function test_demo_request_is_rate_limited(): void
    {
        $payload = [
            'name' => 'Ustadh Khalid',
            'madrasa_name' => 'Al-Hikmah Madrasa',
            'phone' => '+251 92 000 1122',
            'mode' => 'physical',
            'student_count_range' => '50_150',
            'hp_website' => '',
        ];

        // 5 requests allowed within 10 minutes
        for ($i = 0; $i < 5; $i++) {
            $res = $this->post('/demo-request', $payload);
            $res->assertRedirect();
        }

        // 6th request should be throttled (429)
        $sixth = $this->post('/demo-request', $payload);
        $sixth->assertStatus(429);
    }

    public function test_pricing_cards_reflect_database_changes_immediately(): void
    {
        Plan::create([
            'name' => 'Growth (Beyan)',
            'slug' => 'growth',
            'monthly_price' => 140000,
            'yearly_price' => 1400000,
            'currency' => 'ETB',
            'student_limit' => 150,
            'features' => ['Up to 150 students', 'Complete Hifz engine'],
            'badge' => 'Most Popular',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Landing')
            ->has('plans', 2)
            ->where('plans.1.name', 'Growth (Beyan)')
            ->where('plans.1.monthly_price_major', 1400)
            ->where('plans.1.badge', 'Most Popular')
        );
    }

    public function test_sitemap_returns_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        $this->assertStringContainsString('<urlset', $content);
        $this->assertStringContainsString('<loc>', $content);
        $this->assertStringContainsString('/register-institution', $content);
        $this->assertStringContainsString('/login', $content);
        $this->assertStringContainsString('hreflang="ar"', $content);
    }

    public function test_privacy_and_terms_pages_can_be_rendered(): void
    {
        $privacyResponse = $this->get('/privacy');
        $privacyResponse->assertOk();
        $privacyResponse->assertInertia(fn (Assert $page) => $page->component('Legal/Privacy'));

        $termsResponse = $this->get('/terms');
        $termsResponse->assertOk();
        $termsResponse->assertInertia(fn (Assert $page) => $page->component('Legal/Terms'));
    }

    public function test_new_institution_registration_gets_14_day_trial_by_default(): void
    {
        $payload = [
            'institution_name' => 'Trial Verification Madrasa',
            'institution_slug' => 'trial-verification-madrasa',
            'mode' => 'physical',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'am',
            'phone' => '+251 91 999 8888',
            'email' => 'admin@trialmadrasa.org',
            'address' => 'Bole, Addis Ababa',
            'owner_name' => 'Ustadh Zayd',
            'owner_email' => 'zayd@trialmadrasa.org',
            'owner_password' => 'Password123!',
            'owner_password_confirmation' => 'Password123!',
        ];

        $response = $this->post('/register-institution', $payload);
        $response->assertRedirect(route('dashboard'));

        $institution = Institution::where('slug', 'trial-verification-madrasa')->first();
        $this->assertNotNull($institution);
        $this->assertEquals('trial', $institution->subscription_plan);
        $this->assertNotNull($institution->subscription_ends_at);

        // Ends at should be approximately 14 days from now
        $daysUntilEnd = Carbon::now()->diffInDays($institution->subscription_ends_at, false);
        $this->assertGreaterThanOrEqual(13, $daysUntilEnd);
        $this->assertLessThanOrEqual(14, $daysUntilEnd);
    }
}
