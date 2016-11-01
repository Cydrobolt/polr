<?php
namespace App\Http\Controllers\Api;
use App\Models\User;
use Illuminate\Http\Request;


use App\Factories\LinkFactory;
use App\Helpers\LinkHelper;

class ApiLinkController extends ApiController {
    public static function shortenLink(Request $request) {
        $response_type = $request->input('response_type');
        $long_url = $request->input('url'); // * required
        $is_secret = ($request->input('is_secret') == 'true' ? true : false);

        if (!self::checkRequiredArgs([$long_url])) {
            abort(400, "Missing required arguments.");
        }

        $link_ip = $request->ip();
        $custom_ending = $request->input('custom_ending');

        try {
            $user = User::where('api_key', $request->input('key'))->first();
            $isAnonymousUser = empty($user) && env('SETTING_ANON_API');
            $username = $isAnonymousUser ? 'ANONIP-' . $request->ip() : $user->username;
            $formatted_link = LinkFactory::createLink($long_url, $is_secret, $custom_ending, $link_ip, $username, false, true);
        }
        catch (\Exception $e) {
            abort(400, $e->getMessage());
        }

        return self::encodeResponse($formatted_link, 'shorten', $response_type);
    }

    public static function lookupLink(Request $request) {
        $response_type = $request->input('response_type');
        $url_ending = $request->input('url_ending'); // * required

        if (!self::checkRequiredArgs([$url_ending])) {
            abort(400, "Missing required arguments.");
        }

        // "secret" key required for lookups on secret URLs
        $url_key = $request->input('url_key');

        $link = LinkHelper::linkExists($url_ending);

        if ($link['secret_key']) {
            if ($url_key != $link['secret_key']) {
                abort(401, "Invalid URL code for secret URL.");
            }
        }

        if ($link) {
            return self::encodeResponse([
                'long_url' => $link['long_url'],
                'created_at' => $link['created_at'],
                'clicks' => $link['clicks'],
                'updated_at' => $link['updated_at'],
                'created_at' => $link['created_at']
            ], 'lookup', $response_type, $link['long_url']);
        }
        else {
            abort(404, "Link not found.");
        }

    }
}
