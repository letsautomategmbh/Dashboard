<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Modules\Dashboard\Support\ModuleCheck;

/** "Schnellzugriff" — no query, a static curated link list into each
 * feature's own real existing create route/page. */
class QuickActionsWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return true;
    }

    public static function render(User $user, string $size): string
    {
        $links = [];

        $mailbox = $user->mailboxesCanViewWithSettings()->first();
        if ($mailbox) {
            $links[] = ['icon' => 'glyphicon-envelope', 'label' => __('New ticket'), 'url' => route('conversations.create', ['mailbox_id' => $mailbox->id])];
        }

        if (ModuleCheck::invoicing()) {
            $links[] = ['icon' => 'glyphicon-time', 'label' => __('Log Time'), 'url' => route('invoicing.time_entries.create')];
            $links[] = ['icon' => 'glyphicon-check', 'label' => __('Tasks'), 'url' => route('invoicing.tasks.index')];
            $links[] = ['icon' => 'glyphicon-briefcase', 'label' => __('Projects'), 'url' => route('invoicing.projects.index')];
        }

        $limit = $size === 'small' ? 2 : 4;
        $links = array_slice($links, 0, $limit);

        if (empty($links)) {
            return '<div class="dash-empty-inline">'.e(__('No quick actions available.')).'</div>';
        }

        $html = '<div class="dash-actions">';
        foreach ($links as $link) {
            $html .= '<a class="dash-action-btn" href="'.$link['url'].'"><i class="glyphicon '.$link['icon'].'"></i> '.e($link['label']).'</a>';
        }
        $html .= '</div>';

        return $html;
    }
}
