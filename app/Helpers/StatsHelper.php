<?php
namespace App\Helpers;
use App\Models\Click;
use App\Models\Link;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsHelper {
    function __construct($link_id, $left_bound, $right_bound) {
        $this->link_id = $link_id;
        $this->left_bound_parsed = Carbon::parse($left_bound);
        $this->right_bound_parsed = Carbon::parse($right_bound);

        if (!$this->left_bound_parsed->lte($this->right_bound_parsed)) {
            // If left bound is not less than or equal to right bound
            throw new \Exception('Invalid bounds.');
        }

        $days_diff = $this->left_bound_parsed->diffInDays($this->right_bound_parsed);
        $max_days_diff = env('_ANALYTICS_MAX_DAYS_DIFF') ?: 365;

        if ($days_diff > $max_days_diff) {
            throw new \Exception('Bounds too broad.');
        }
    }

    public function getBaseRows() {
        /**
        * Fetches base rows given left date bound, right date bound, and link id
        *
        * @param integer $link_id
        * @param string $left_bound
        * @param string $right_bound
        *
        * @return DB rows
        */

        return DB::table('clicks')
            ->where('link_id', $this->link_id)
            ->where('created_at', '>=', $this->left_bound_parsed)
            ->where('created_at', '<=', $this->right_bound_parsed);
    }

    public function uniqueClicks() {
        $stats = DB::table('clicks')
            ->where('link_id', $this->link_id)
            ->select(DB::raw("count(DISTINCT ip) as uniques"))
            ->first();

        return $stats->uniques;
    }

    public function getDayStats() {
        // Return stats by day from the last 30 days
        // date => x
        // clicks => y
        $stats = $this->getBaseRows()
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') AS x, count(*) AS y"))
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d')"))
            ->orderBy('x', 'asc')
            ->get();

        return $stats;
    }

    public function getCountryStats() {
        $stats = $this->getBaseRows()
            ->select(DB::raw("country AS label, count(*) AS clicks"))
            ->groupBy('country')
            ->orderBy('clicks', 'desc')
            ->whereNotNull('country')
            ->get();

        return $stats;
    }

    public function getRefererStats() {
        $stats = $this->getBaseRows()
            ->select(DB::raw("COALESCE(referer_host, 'Direct') as label, count(*) as clicks"))
            ->groupBy('referer_host')
            ->orderBy('clicks', 'desc')
            ->get();

        return $stats;
    }
}
