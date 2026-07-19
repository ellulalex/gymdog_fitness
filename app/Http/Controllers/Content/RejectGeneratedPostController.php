<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * One-click reject from the review email. The route is signed, so the link
 * itself is the authorization — no login needed. Only a still-scheduled
 * generated post can be rejected (the brake window).
 */
class RejectGeneratedPostController extends Controller
{
    public function __invoke(Post $post): Response
    {
        if ($post->source !== 'generated' || $post->status !== 'scheduled') {
            throw new NotFoundHttpException;
        }

        $post->update(['status' => 'rejected']);

        return response(
            "Rejected “{$post->title}”. It will not be published.",
            200,
        );
    }
}
