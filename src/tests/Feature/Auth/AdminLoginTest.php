<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use DatabaseMigrations;
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** 管理者はログインできる */
    public function test_admin_can_login_with_valid_credential()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
    }

    /** 管理者ログイン時、メールアドレスは必須 */
    public function test_email_is_required_for_admin_login()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);

        $this->assertGuest();
    }

    /** 管理者ログイン時、パスワードは必須 */
    public function test_password_is_required_for_admin_login()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);

        $this->assertGuest();
    }

    /** 登録されていない管理者はログインできない */
    public function test_unregistered_admin_cannot_login()
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'admin_notfound@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();

        $response->assertRedirect('/login');

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
