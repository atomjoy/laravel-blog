<?php

namespace Tests\Dev;

use App\Models\User;
use App\Events\RegisterUser;
use App\Events\RegisterUserMail;
use App\Listeners\RegisterUserListener;
use App\Mail\RegisterMail;
use Database\Seeders\TestSeeder;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Register user.
     */
    public function test_create_user(): void
    {
        $this->seed(TestSeeder::class);

        Mail::fake();

        $name = "Godluck O'Reilly PhD";
        $email = uniqid() . '@gmail.com';

        $response = $this->postJson('web/api/register', [
            'name' => $name,
            'email' => $email,
            'password' => 'Password123#',
            'password_confirmation' => 'Password123#',
        ]);

        $response->assertStatus(201)->assertJsonMissing(['created' => false])->assertJson([
            'message' => 'Account has been created, please confirm your email address.',
            'created' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
        ]);

        $user = User::where([
            'name' => $name,
            'email' => $email,
        ])->first();

        $this->assertNotNull($user->remember_token);

        Mail::assertSent(RegisterMail::class, function ($mail) use ($email, $name) {
            $mail->build();
            $html = $mail->render();
            $this->assertTrue(strpos(html_entity_decode($html), $name) !== false);
            $this->assertEquals("👋 Account activation.", $mail->subject, 'The subject was not the right one.');
            $this->assertMatchesRegularExpression('/\/activate\/[0-9]+\/[a-z0-9]+\?locale=[a-z]{2}"/i', $html);
            return $mail->hasTo($email);
        });
    }

    /**
     * Register user.
     */
    public function test_create_user_event(): void
    {
        $this->seed(TestSeeder::class);

        Event::fake();

        $name = "Godluck O'Reilly PhD";
        $email = uniqid() . '@gmail.com';

        $response = $this->postJson('web/api/register', [
            'name' => $name,
            'email' => $email,
            'password' => 'Password123#',
            'password_confirmation' => 'Password123#',
        ]);

        $response->assertStatus(201)->assertJsonMissing(['created' => false])->assertJson([
            'message' => 'Account has been created, please confirm your email address.',
            'created' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
        ]);

        // Test event
        Event::assertDispatched(RegisterUser::class);

        // Test event listeners
        Event::assertListening(
            RegisterUser::class,
            RegisterUserListener::class,
        );
    }

    function test_check_register_mail_listeners()
    {
        // Mail message
        Mail::fake();

        $user = User::factory()->create([
            'email' => uniqid() . '@gmail.com',
            'password' => Hash::make('Password123#'),
            'remember_token' => uniqid(),
        ]);
        $event = new RegisterUser($user);
        $listener = new RegisterUserListener();
        $listener->handle($event);
        $name = $user->name;
        $email = $user->email;
        $token = $user->token;

        // Mail message
        Mail::assertSent(RegisterMail::class, function ($mail) use ($email, $name) {
            $mail->build();
            $html = $mail->render();
            $this->assertTrue(strpos(html_entity_decode($html), $name) !== false);
            $this->assertEquals("👋 Account activation.", $mail->subject, 'The subject was not the right one.');
            $this->assertMatchesRegularExpression('/\/activate\/[0-9]+\/[a-z0-9]+\?locale=[a-z]{2}"/i', $html);
            return $mail->hasTo($email);
        });
    }

    function test_check_register_event_listeners()
    {
        Event::fake();

        $user = User::factory()->create([
            'email' => uniqid() . '@gmail.com',
            'password' => Hash::make('Password123#')
        ]);
        $event = new RegisterUser($user);
        $listener = new RegisterUserListener();
        $listener->handle($event);
        $name = $user->name;
        $email = $user->email;

        // Mail event
        Event::assertDispatched(MessageSent::class, function ($e) use ($email, $name) {
            $html = $e->message->getHtmlBody();
            $this->assertStringContainsString($name, html_entity_decode($html));
            $this->assertMatchesRegularExpression('/\/activate\/[0-9]+\/[a-z0-9]+\?locale=[a-z]{2}"/i', $html);
            return collect($e->message->getTo())->first()->getAddress() == $email;
            // Password test
            // $this->assertMatchesRegularExpression('/word>[a-zA-Z0-9#]+<\/pass/', $html);
        });

        // Event after sent
        Event::assertDispatched(RegisterUserMail::class);
    }

    function getPassword($html)
    {
        preg_match('/word>[a-zA-Z0-9#]+<\/pass/', html_entity_decode($html), $matches, PREG_OFFSET_CAPTURE);
        return str_replace(['word>', '</pass'], '', end($matches)[0]);
    }

    function test_email_duplicated_error()
    {
        $user = User::factory()->create([
            'email' => uniqid() . '@gmail.com',
            'password' => Hash::make('Password123#')
        ]);

        $res = $this->postJson('/web/api/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'Password123#',
            'password_confirmation' => 'Password123#',
        ]);

        $res->assertStatus(422)->assertJsonMissing(['created'])->assertJson([
            'message' => 'The email has already been taken.'
        ]);
    }

    function test_register_user_error_name()
    {
        $user = User::factory()->make();

        $res = $this->postJson('/web/api/register', [
            'name' => '',
            'email' => $user->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertStatus(422)->assertJsonMissing(['created'])->assertJson([
            'message' => 'The name field is required.'
        ]);
    }

    function test_error_email()
    {
        $user = User::factory()->make();

        $res = $this->postJson('/web/api/register', [
            'name' => $user->name,
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertStatus(422)->assertJsonMissing(['created'])->assertJson([
            'message' => 'The email field is required.'
        ]);
    }

    function test_error_password()
    {
        $user = User::factory()->make([
            'email' => uniqid() . '@gmail.com',
            'password' => Hash::make('Password123#')
        ]);

        $res = $this->postJson('/web/api/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => 'password123',
        ]);

        $res->assertStatus(422)->assertJsonMissing(['created'])->assertJson([
            'message' => 'The password field is required.'
        ]);
    }

    function test_error_password_confirmation()
    {
        $user = User::factory()->make([
            'email' => uniqid() . '@gmail.com',
            'password' => Hash::make('Password123#')
        ]);

        $res = $this->postJson('/web/api/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'password1234#',
            'password_confirmation' => '',
        ]);

        $res->assertStatus(422)->assertJsonMissing(['created'])->assertJson([
            'message' => 'The password field must contain at least one uppercase and one lowercase letter.'
        ]);

        $res = $this->postJson('/web/api/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'Password1234#',
            'password_confirmation' => 'Password1234#1',
        ]);

        $res->assertStatus(422)->assertJsonMissing(['created'])->assertJson([
            'message' => 'The password field confirmation does not match.'
        ]);
    }

    function test_http_validate_user()
    {
        $user = User::factory()->make([
            'email' => uniqid() . '@gmail.com',
            'password' => Hash::make('Password123#')
        ]);

        $res = $this->postJson('/web/api/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'Password1234#',
            'password_confirmation' => 'Password1234#1',
        ]);

        $res->assertStatus(422)->assertJsonMissing(['created'])->assertJson([
            'message' => 'The password field confirmation does not match.'
        ]);

        $res = $this->postJson('/web/api/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'Password1234',
            'password_confirmation' => 'Password1234',
        ]);

        $res->assertStatus(422)->assertJsonMissing(['created'])->assertJson([
            'message' => 'The password field must contain at least one symbol.'
        ]);

        $res = $this->postJson('/web/api/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'password1234#',
            'password_confirmation' => 'password1234#',
        ]);

        $res->assertStatus(422)->assertJsonMissing(['created'])->assertJson([
            'message' => 'The password field must contain at least one uppercase and one lowercase letter.'
        ]);

        $res = $this->postJson('/web/api/register', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'Passwordoooo#',
            'password_confirmation' => 'Passwordoooo#',
        ]);

        $res->assertStatus(422)->assertJsonMissing(['created'])->assertJson([
            'message' => 'The password field must contain at least one number.'
        ]);
    }
}
