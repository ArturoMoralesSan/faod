<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El registro público está desactivado: las cuentas las crea Administración.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_disabled()
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing(User::class, ['email' => 'test@example.com']);
    }
}
