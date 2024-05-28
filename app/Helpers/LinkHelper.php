<?php
namespace App\Helpers;
use App\Models\Link;
use App\Helpers\BaseHelper;

class LinkHelper {
    static public function checkIfAlreadyShortened($long_link) {
        /**
         * Provided a long link (string),
         * detect whether the link belongs to an URL shortener.
         * @return boolean
         */
        $shortener_domains = [
            'polr.me',
            'bit.ly',
            'is.gd',
            'tiny.cc',
            'adf.ly',
            'ur1.ca',
            'goo.gl',
            'ow.ly',
            'j.mp',
            't.co',
            env('APP_ADDRESS')
        ];

        foreach ($shortener_domains as $shortener_domain) {
            $url_segment = ('://' . $shortener_domain);
            if (strstr($long_link, $url_segment)) {
                return true;
            }
        }
        return false;
    }

    static public function linkExists($link_ending) {
        /**
         * Provided a link ending (string),
         * return the link object, or false.
         * @return Link model instance
         */

        $link = Link::where('short_url', $link_ending)
            ->first();

        if ($link != null) {
            return $link;
        }
        else {
            return false;
        }
    }

    static public function longLinkExists($long_url, $username=false) {
        /**
         * Provided a long link (string),
         * check whether the link is in the DB.
         * If a username is provided, only search for links created by the
         * user.
         * @return boolean
         */
        $link_base = Link::longUrl($long_url)
            ->where('is_custom', 0)
            ->where('secret_key', '');

        if (is_null($username)) {
            // Search for links without a creator only
            $link = $link_base->where('creator', '')->first();
        }
        else if (($username !== false)) {
            // Search for links created by $username only
            $link = $link_base->where('creator', $username)->first();
        }
        else {
            // Search for links created by any user
            $link = $link_base->first();
        }

        if ($link == null) {
            return false;
        }
        else {
            return $link->short_url;
        }
    }

    static public function validateEnding($link_ending) {
        $is_valid_ending = preg_match('/^[a-zA-Z0-9-_]+$/', $link_ending);
        return $is_valid_ending;
    }

    static public function findPseudoRandomEnding() {
        /**
         * Return an available pseudorandom string of length _PSEUDO_RANDOM_KEY_LENGTH,
         * as defined in .env
         * Edit _PSEUDO_RANDOM_KEY_LENGTH in .env if you wish to increase the length
         * of the pseudorandom string generated.
         * @return string
         */

        $pr_str = '';
        $in_use = true;

        while ($in_use) {
            // Generate a new string until the ending is not in use
            $pr_str = str_random(env('_PSEUDO_RANDOM_KEY_LENGTH'));
            $in_use = LinkHelper::linkExists($pr_str);
        }

        return $pr_str;
    }

    public static function findSuitableEnding() {
        $base = env('POLR_BASE');
    
        // Get the latest non-custom link
        $link = Link::where('is_custom', 0)
            ->orderBy('created_at', 'desc')
            ->first();
    
        $base10_val = $link ? BaseHelper::toBase10($link->short_url, $base) + 1 : 0;
    
        // Batch size for checking multiple endings at once
        $batchSize = 100;
        $base_x_val = null;
    
        while ($base_x_val === null) {
            // Generate a batch of potential endings
            $potential_endings = [];
            for ($i = 0; $i < $batchSize; $i++) {
                $potential_endings[] = BaseHelper::toBase($base10_val + $i, $base);
            }
    
            // Check which of these potential endings are already in use
            $existing_endings = Link::whereIn('short_url', $potential_endings)
                ->pluck('short_url')
                ->toArray();
    
            // Find the first potential ending that is not in use
            foreach ($potential_endings as $ending) {
                if (!in_array($ending, $existing_endings)) {
                    $base_x_val = $ending;
                    break;
                }
            }
    
            // Increment base10_val by batch size
            $base10_val += $batchSize;
        }
    
        return $base_x_val;
    }
    
}
