<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use DatabaseMigrations;
    public function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }
    //ログイン機能のテスト
     /** 一般ユーザーはログインできる */
    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::where('role', 'user')->first();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    /** ログインーーメアドバリデーション */
    public function test_email_is_required_for_login()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
        $this->assertGuest();
    }

    /** ログインーーパスワードバリデーション */
    public function test_password_is_required_for_login()
    {
        $user = User::where('role', 'user')->first();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください',]);
        $this->assertGuest();
    }


    /** 登録されていないユーザーはログインできない */
    public function test_unregistered_user_cannot_login()
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'notfound@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();

        $response->assertRedirect('/login');

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /** 認証時ーーバリデーション */
    public function test_cannot_login_with_invalid_email()
    {
        // 正しいユーザーを1人作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // 存在しないメールアドレスでログイン
        $response = $this->from('/login')->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        // 認証されていない
        $this->assertGuest();

        // ログイン画面にリダイレクト
        $response->assertRedirect('/login');

        // Fortifyで定義したメッセージが出ていること
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

}