<?php

namespace Tests\Dev;

use Mockery;
use Exception;
use Mockery\MockInterface;
use App\Models\User;
use App\Events\PasswordResetError;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Requests\Auth\PasswordResetRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPasswordResetValidationTest extends TestCase
{
    use RefreshDatabase;

    function test_email_database_error()
    {
        Event::fake(PasswordResetError::class);

        $user = User::factory()->create([
            'email' => uniqid() . '@gmail.com',
            'password' => Hash::make('Password123#')
        ]);

        $valid = [
            'email' => $user->email,
        ];

        $request = null;

        // Test error
        putenv('TEST_DATABASE=true');
        $response = $this->postJson('web/api/password', [
            'email' => $user->email,
        ]);
        $response->assertStatus(422)->assertJson([
            'message' => 'Password has not been updated.',
        ]);
        putenv('TEST_DATABASE=false');

        try {
            $request = $this->partialMock(PasswordResetRequest::class, static function (MockInterface $mock) use ($valid) {
                // Add only updated methods
                $mock->shouldReceive('validated')->andReturn($valid);
                $mock->shouldReceive('testDatabase')->andThrow(new Exception());
            });

            // Build controller
            $controller = $this->controller();

            // Call custom controller method
            $response = $this->app->call([$controller, 'index'], [
                'request' => $request,
            ]);
        } catch (Exception $e) {
            // Catch exception
            $this->assertEquals($e->getMessage(), 'Password has not been updated.');
        }

        // Then catch event
        Event::assertDispatched(PasswordResetError::class, function ($e) use ($valid) {
            return $valid == $e->valid;
        });

        // Call anonymous controller method
        $response = $this->app->call($controller, [
            'request' => $request,
        ]);
        $this->assertSame($valid, $request->validated());
        $this->assertSame($valid, $response);
    }

    protected function controller(): PasswordResetController
    {
        return new class extends PasswordResetController
        {
            public function __invoke(PasswordResetRequest $request): array
            {
                return $request->validated();
            }
        };
    }
}
