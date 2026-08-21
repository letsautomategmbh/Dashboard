<?php

namespace Modules\Dashboard\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    protected $table = 'dashboard_widgets';

    const SIZE_SMALL = 'small';
    const SIZE_MEDIUM = 'medium';
    const SIZE_LARGE = 'large';

    public static $sizes = [self::SIZE_SMALL, self::SIZE_MEDIUM, self::SIZE_LARGE];

    protected $fillable = [
        'user_id',
        'widget_key',
        'size',
        'position',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Cycles small -> medium -> large -> small, clamped to whatever sizes
     * the widget's own registry entry actually supports (a widget with
     * only one supported size just stays put). */
    public function nextSize(array $supportedSizes)
    {
        $order = array_values(array_intersect(self::$sizes, $supportedSizes));
        if (count($order) < 2) {
            return $this->size;
        }

        $index = array_search($this->size, $order);
        $index = ($index === false) ? 0 : $index;

        return $order[($index + 1) % count($order)];
    }
}
