<?php

namespace Modules\Dashboard\Widgets;

use App\Conversation;
use App\User;

/** "Meine Nachrichten" — core-only data (no module owns Conversation),
 * exact query mirrored from core's own "My open conversations" nav link
 * (resources/views/layouts/app.blade.php) and Conversation::
 * getQueryByFolder()'s TYPE_MINE branch. */
class MyConversationsWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return true;
    }

    public static function render(User $user, string $size): string
    {
        $query = Conversation::where('user_id', $user->id)
            ->whereIn('status', [Conversation::STATUS_ACTIVE, Conversation::STATUS_PENDING])
            ->where('state', Conversation::STATE_PUBLISHED);

        $count = (clone $query)->count();
        $searchUrl = route('conversations.search', ['f' => ['assigned' => $user->id, 'status' => [Conversation::STATUS_ACTIVE, Conversation::STATUS_PENDING]]]);

        $html = '<div class="dash-stat-row"><div class="dash-stat"><span class="dash-stat-value">'.$count.'</span><span class="dash-stat-label">'.e(__('My open conversations')).'</span></div></div>';

        if ($size !== 'small') {
            $limit = $size === 'large' ? 6 : 3;
            $conversations = (clone $query)->orderByDesc('updated_at')->limit($limit)->get();

            if ($conversations->isNotEmpty()) {
                $html .= '<div class="dash-list">';
                foreach ($conversations as $conversation) {
                    $html .= '<a class="dash-list-item" href="'.$conversation->url().'">'
                        .'<span class="dash-list-item-label">'.e($conversation->subject).'</span>'
                        .'</a>';
                }
                $html .= '</div>';
            }
        }

        $html .= '<a class="dash-widget-link" href="'.$searchUrl.'">'.e(__('View all')).' &rarr;</a>';

        return $html;
    }
}
