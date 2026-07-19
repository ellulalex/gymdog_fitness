<x-mail::message>
# A new post is scheduled

An AI-generated post is scheduled to publish
@if ($post->published_at) **{{ $post->published_at->diffForHumans() }}** ({{ $post->published_at->format('d M Y, H:i') }})@endif.
It will go live automatically unless you reject it.

**{{ $post->title }}**

{{ $post->excerpt }}

<x-mail::button :url="$rejectUrl" color="error">
Reject this post
</x-mail::button>

You can also edit or publish it now from the admin.

Thanks,<br>
GymDog platform
</x-mail::message>
