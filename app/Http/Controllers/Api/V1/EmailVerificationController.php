<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;

#[Group('Auth')]
class EmailVerificationController extends Controller
{
    /**
     * Verify email
     *
     * Target of the signed link in the verification email. Submitting price reports
     * requires a verified email.
     *
     * @unauthenticated
     */
    public function verify(int $id, string $hash): JsonResponse
    {
        $user = User::query()->findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->data(['message' => 'Your email address is verified.']);
    }

    /**
     * Resend verification email
     */
    #[Authenticated]
    public function resend(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return $this->data(['message' => 'If your email is not verified yet, a new link has been sent.']);
    }
}
