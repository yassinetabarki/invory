<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use RuntimeException;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registering_creates_a_new_workshop_with_the_new_user_as_its_only_member()
    {
        $this->post(route('register.store'), $this->registrationInput('Test User', 'test@example.com'));

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertSame(1, Workshop::query()->count());
        $this->assertSame([$user->id], $user->workshop->users()->pluck('id')->all());
    }

    public function test_two_registrations_get_two_different_workshops()
    {
        $this->post(route('register.store'), $this->registrationInput('First', 'first@example.com'));
        auth()->logout();
        $this->post(route('register.store'), $this->registrationInput('Second', 'second@example.com'));

        $first = User::query()->where('email', 'first@example.com')->firstOrFail();
        $second = User::query()->where('email', 'second@example.com')->firstOrFail();

        $this->assertSame(2, Workshop::query()->count());
        $this->assertNotSame($first->workshop_id, $second->workshop_id);
        $this->assertSame(1, $first->workshop->users()->count());
        $this->assertSame(1, $second->workshop->users()->count());
    }

    public function test_a_failed_registration_leaves_no_workshop_behind()
    {
        // The workshop is created first, then the user fails to save: the transaction takes the workshop with it.
        User::creating(fn () => throw new RuntimeException('The account could not be saved.'));

        $this->post(route('register.store'), $this->registrationInput('Test User', 'test@example.com'))->assertServerError();

        $this->assertSame(0, Workshop::query()->count());
        $this->assertSame(0, User::query()->count());
    }

    /** @return array<string, string> */
    private function registrationInput(string $name, string $email): array
    {
        return [
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
    }
}
