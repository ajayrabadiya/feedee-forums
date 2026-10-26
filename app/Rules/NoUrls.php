<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NoUrls implements ValidationRule
{
    /**
     * Matches links with a scheme (https://), "www.", bare domains on common
     * TLDs (example.com, bit.ly/x, site[.]com) and IPv4 addresses.
     * Written to work unchanged as a JavaScript RegExp with the "i" flag.
     */
    public const PATTERN = '(?:[a-z][a-z0-9+.-]*:\/\/|\bwww\.|\b\d{1,3}(?:\.\d{1,3}){3}\b|\b[a-z0-9-]+(?:[\[(]?\.[\])]?[a-z0-9-]+)*[\[(]?\.[\])]?(?:com|net|org|io|co|me|ly|app|dev|xyz|info|biz|gg|tv|link|site|shop|online|store|club|top|us|uk|in|ru|de|fr|au|ca|tk|ml|ga|cf|gq|cc|ws|to|so|fm|ai|live|blog|art|page|click|lol|vip|pro|mobi|onion|li|gl|gd|su|eu)\b)';

    public function __construct(private string $message = 'Links aren\'t allowed in the :attribute.') {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && self::containsUrl($value)) {
            $fail($this->message);
        }
    }

    public static function containsUrl(string $value): bool
    {
        return preg_match('/'.self::PATTERN.'/iu', $value) === 1;
    }
}
