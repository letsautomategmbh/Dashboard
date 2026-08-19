<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Modules\Notes\Entities\Note;

class NotesWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return class_exists('Modules\Notes\Entities\Note');
    }

    public static function render(User $user, string $size): string
    {
        $count = Note::visibleTo($user->id)->count();

        $html = '<div class="dash-stat-row"><div class="dash-stat"><span class="dash-stat-value">'.$count.'</span><span class="dash-stat-label">'.e(__('Notes')).'</span></div></div>';

        if ($size !== 'small') {
            $limit = $size === 'large' ? 6 : 3;
            $notes = Note::visibleTo($user->id)->orderByDesc('id')->limit($limit)->get();

            if ($notes->isNotEmpty()) {
                $html .= '<div class="dash-list">';
                foreach ($notes as $note) {
                    $color = $note->color ?: '#f5f5f5';
                    $title = $note->title ?: __('Untitled');
                    $html .= '<a class="dash-list-item" href="'.route('notes.show', $note->id).'">'
                        .'<span class="dash-list-item-label"><span class="dash-note-dot" style="background:'.e($color).'"></span> '.e($title).'</span>'
                        .'</a>';
                }
                $html .= '</div>';
            }
        }

        $html .= '<a class="dash-widget-link" href="'.route('notes.index').'">'.e(__('View all')).' &rarr;</a>';

        return $html;
    }
}
