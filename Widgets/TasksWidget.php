<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Modules\Invoicing\Entities\Task;

/** "Aufgaben" — "mine" = user_id (no team concept on tasks, unlike
 * Projekte), exact query copied from Invoicing's own sidebar "mine"
 * badge count. Shows the task titles directly rather than a bare count
 * (even at "small"), per feedback that a lone number wasn't useful. */
class TasksWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return class_exists('Modules\Invoicing\Entities\Task');
    }

    public static function render(User $user, string $size): string
    {
        $query = Task::where('user_id', $user->id)->where('status', '!=', Task::STATUS_DONE);

        $count = (clone $query)->count();
        $listUrl = route('invoicing.tasks.index', ['filter' => 'mine']);

        $html = '<div class="dash-empty-inline margin-bottom">'.$count.' '.e(__('open')).'</div>';

        $limit = ['small' => 2, 'medium' => 4, 'large' => 8][$size] ?? 3;
        $tasks = (clone $query)->orderByRaw('due_date IS NULL, due_date ASC')->limit($limit)->get();

        if ($tasks->isEmpty()) {
            $html .= '<div class="dash-empty-inline">'.e(__('No tasks')).'</div>';
        } else {
            $html .= '<div class="dash-list">';
            foreach ($tasks as $task) {
                $due = $task->due_date ? $task->due_date->format('d.m.') : '';
                $html .= '<a class="dash-list-item" href="'.route('invoicing.tasks.show', $task->id).'">'
                    .'<span class="dash-list-item-label">'.e($task->title).'</span>'
                    .($due ? '<span class="dash-list-item-value">'.e($due).'</span>' : '')
                    .'</a>';
            }
            $html .= '</div>';
        }

        $html .= '<a class="dash-widget-link" href="'.$listUrl.'">'.e(__('View all')).' &rarr;</a>';

        return $html;
    }
}
