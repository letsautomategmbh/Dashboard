<?php

namespace Modules\Dashboard\Widgets;

use App\User;
use Modules\Invoicing\Entities\Project;

/** "Projekte" — "mine" = manager (user_id) OR team member, exact query
 * copied from Invoicing/Resources/views/partials/sidebar.blade.php's own
 * "mine" badge count. */
class ProjectsWidget implements Widget
{
    public static function isAvailable(User $user): bool
    {
        return class_exists('Modules\Invoicing\Entities\Project');
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

        $html = '<div class="dash-stat-row"><div class="dash-stat"><span class="dash-stat-value">'.$count.'</span><span class="dash-stat-label">'.e(__('Projects')).'</span></div></div>';

        if ($size !== 'small') {
            $limit = $size === 'large' ? 6 : 3;
            $projects = (clone $query)->orderBy('name')->limit($limit)->get();

            if ($projects->isNotEmpty()) {
                $html .= '<div class="dash-list">';
                foreach ($projects as $project) {
                    $html .= '<a class="dash-list-item" href="'.route('invoicing.projects.show', $project->id).'">'
                        .'<span class="dash-list-item-label">'.e($project->name).'</span>'
                        .'</a>';
                }
                $html .= '</div>';
            }
        }

        $html .= '<a class="dash-widget-link" href="'.$listUrl.'">'.e(__('View all')).' &rarr;</a>';

        return $html;
    }
}
