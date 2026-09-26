<?php

namespace App\Traits;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * A sequential integer in a URL (/wallets/1, /wallets/2, ...) lets
 * anyone probe for other records just by changing the number, and
 * reveals how many records exist even when every route still
 * authorizes correctly. This swaps the URL segment for an opaque,
 * encrypted token instead — the real numeric ID is still used
 * everywhere else (relations, queries, the database); only what
 * appears in the address bar changes. Nothing else needs to change
 * to use this — route()/URL generation and implicit route model
 * binding both call these two methods automatically.
 *
 * Laravel's default route pattern for a parameter is `[^/]+` — it
 * never matches a literal slash, so standard base64 (which can
 * contain '/') would silently break route matching. This uses the
 * base64url variant instead (- and _ in place of +  and /, padding
 * stripped) specifically to stay a single, safe URL segment.
 */
trait HasEncryptedRouteKey
{
    public function getRouteKey()
    {
        $encrypted = Crypt::encryptString((string) $this->getKey());

        return rtrim(strtr($encrypted, '+/', '-_'), '=');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $padded = $value . str_repeat('=', (4 - strlen($value) % 4) % 4);
        $standard = strtr($padded, '-_', '+/');

        try {
            $id = Crypt::decryptString($standard);
        } catch (DecryptException) {
            // A malformed or tampered token — Laravel treats a null
            // binding as "not found" and renders its own 404, the
            // same as a genuinely missing record. Never leak *why*
            // it failed to resolve.
            return null;
        }

        return $this->where($this->getKeyName(), $id)->first();
    }
}
