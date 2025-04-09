<?php

namespace App\Http\Controllers\Auth;

use Exception;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Events\EmailChangeConfirm;
use App\Events\EmailChangeConfirmError;
use App\Exceptions\JsonException;
use App\Http\Requests\Auth\EmailChangeConfirmRequest;
use App\Models\ChangeEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EmailChangeConfirmController extends Controller
{
	function index(EmailChangeConfirmRequest $request)
	{
		$valid = $request->validated();
		$user = null;

		try {
			$request->testDatabase();

			$user = Auth::user();

			if (!$user instanceof User) {
				throw new Exception('Invalid change email user.', 422);
			}

			$change = ChangeEmail::latest()
				->where('user_id', $user->id)
				->where('code', $valid['code'])
				->whereTime('created_at', '>', now()->subHours(1))
				->first();

			if (!$change instanceof ChangeEmail) {
				throw new Exception("Invalid change email code.", 422);
			}

			if (app()->isProduction()) {
				$validator = Validator::make(['email' => $change->email_new], [
					'email' => 'required|email:rfc,dns'
				]);

				if ($validator->fails()) {
					throw new Exception("Invalid change email address.", 422);
				}
			}

			if ($change->code != $valid['code']) {
				throw new Exception("Invalid change email code.", 422);
			}

			if ($change->user_id != $user->id) {
				throw new Exception("Invalid change email user id.", 422);
			}

			$user->forceFill([
				'email' => $change->email_new
			])->save();

			EmailChangeConfirm::dispatch($user, $change->code);

			return response()->json([
				'message' => __("email.change.confirm.success")
			], 200);
		} catch (Exception $e) {
			report($e);
			EmailChangeConfirmError::dispatch($valid);
			throw new JsonException(__("email.change.confirm.error"), 422);
		}
	}
}
