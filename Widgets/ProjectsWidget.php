<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Modules\Dashboard\Support\ModuleCheck;
use Modules\Invoicing\Entities\Project;

/** "Projekte" — "mine" = manager (user_id) OR team member, exact query
 * copied from Invoicing/Resources/views/partials/sidebar.blade.php's own
 * "mine" badge count. Shows the project names directly rather than a
 * bare count (even at "small"), per feedback that a lone number wasn't
 * useful. */
class ProjectsWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return ModuleCheck::invoicing();
    }

    public static function render(User $user, string $size): string
    {
        $query = Project::where('status', '!=', Project::STATUS_COMPLETED)
            ->where(function ($sub) use ($user) {
                $sub->where('user_id', $user->id)
                    ->orWhereHas('members', function ($m) use ($user) {
                        $m->where('users.id', $user->id);
                    });
            });

        $count = (clone $query)->count();
        $listUrl = route('invoicing.projects.index', ['filter' => 'mine']);

        $html = '<div class="dash-empty-inline margin-bottom">'.$count.' '.e(__('open')).'</div>';

        $limit = ['small' => 2, 'medium' => 4, 'large' => 8][$size] ?? 3;
        $projects = (clone $query)->orderBy('name')->limit($limit)->get();

        if ($projects->isEmpty()) {
            $html .= '<div class="dash-empty-inline">'.e(__('No projects yet.')).'</div>';
        } else {
            $html .= '<div class="dash-list">';
            foreach ($projects as $project) {
                $html .= '<a class="dash-list-item" href="'.route('invoicing.projects.show', $project->id).'">'
                    .'<span class="dash-list-item-label">'.e($project->name).'</span>'
                    .'</a>';
            }
            $html .= '</div>';
        }

        $html .= '<a class="dash-widget-link" href="'.$listUrl.'">'.e(__('View all')).' &rarr;</a>';

        return $html;
    }
}
