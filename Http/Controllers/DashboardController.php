<?php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Dashboard\Entities\DashboardWidget;
use Modules\Dashboard\Support\WidgetRegistry;

class DashboardController extends Controller
{
    /** Every mutation below is scoped to Auth::id() — a widget id from
     * another user's board is simply not found (404), never trusted from
     * the request. */
    private function ownWidgetOrFail($id)
    {
        return DashboardWidget::where('user_id', Auth::id())->findOrFail($id);
    }

    public function index()
    {
        $user = Auth::user();

        if (DashboardWidget::where('user_id', $user->id)->doesntExist()) {
            $this->seedDefaults($user->id);
        }

        $rows = DashboardWidget::where('user_id', $user->id)->orderBy('position')->get();

        $boardWidgets = [];
        foreach ($rows as $row) {
            $meta = WidgetRegistry::get($row->widget_key);
            if (!$meta || !WidgetRegistry::isAvailable($row->widget_key, $user)) {
                // Source module got deactivated (or the key no longer
                // exists) since this row was added — skip rendering, but
                // leave the row in place so it reappears if the module
                // comes back, per the registry's own doc comment.
                continue;
            }

            $html = '';
            try {
                $html = $meta['class']::render($user, $row->size);
            } catch (\Throwable $e) {
                \Helper::logException($e, 'Dashboard widget render failed ('.$row->widget_key.'): ');
                continue;
            }

            $boardWidgets[] = [
                'id' => $row->id,
                'key' => $row->widget_key,
                'size' => $row->size,
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'cyclable' => count($meta['sizes']) > 1,
                'html' => $html,
            ];
        }

        $addedKeys = $rows->pluck('widget_key')->all();
        $availableToAdd = [];
        foreach (WidgetRegistry::availableKeys($user) as $key) {
            if (in_array($key, $addedKeys)) {
                continue;
            }
            $meta = WidgetRegistry::get($key);
            $availableToAdd[] = ['key' => $key, 'label' => $meta['label'], 'icon' => $meta['icon']];
        }

        return view('dashboard::index', compact('boardWidgets', 'availableToAdd'));
    }

    private function seedDefaults($userId)
    {
        $position = 0;
        foreach (WidgetRegistry::$defaultKeys as $key) {
            $meta = WidgetRegistry::get($key);
            if (!$meta) {
                continue;
            }
            DashboardWidget::firstOrCreate(
                ['user_id' => $userId, 'widget_key' => $key],
                ['size' => $meta['default_size'], 'position' => $position]
            );
            $position++;
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate(['widget_key' => 'required|string']);
        $key = $data['widget_key'];
        $user = Auth::user();

        if (!WidgetRegistry::isAvailable($key, $user)) {
            abort(404);
        }

        if (DashboardWidget::where('user_id', $user->id)->where('widget_key', $key)->exists()) {
            return response()->json(['success' => true]);
        }

        $meta = WidgetRegistry::get($key);
        $nextPosition = (int) DashboardWidget::where('user_id', $user->id)->max('position') + 1;

        DashboardWidget::create([
            'user_id' => $user->id,
            'widget_key' => $key,
            'size' => $meta['default_size'],
            'position' => $nextPosition,
        ]);

        return response()->json(['success' => true]);
    }

    public function updateSize(Request $request, $id)
    {
        $widget = $this->ownWidgetOrFail($id);
        $meta = WidgetRegistry::get($widget->widget_key);

        $widget->size = $widget->nextSize($meta['sizes'] ?? DashboardWidget::$sizes);
        $widget->save();

        return response()->json(['success' => true, 'size' => $widget->size]);
    }

    /** Takes the whole new order of widget ids in one request (same
     * pattern KnowledgeBase's own drag-reorder already uses) and rewrites
     * every row's position accordingly — simpler and safer than trying to
     * reconcile incremental deltas. */
    public function reorder(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $own = DashboardWidget::where('user_id', Auth::id())
            ->whereIn('id', $data['ids'])
            ->get()
            ->keyBy('id');

        foreach ($data['ids'] as $position => $id) {
            if (isset($own[$id])) {
                $own[$id]->update(['position' => $position]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $widget = $this->ownWidgetOrFail($id);
        $widget->delete();

        return response()->json(['success' => true]);
    }
}
