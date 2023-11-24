<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class YoutubeRule implements Rule {

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value) {
        return (bool) preg_match('/^(https?\:\/\/)?(www\.)?(youtube\.com|youtu\.be)/', $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        return @implode('', [
                    "{$this->caption} - Invalid",
                    "- Can start with 'http://www/youtube.com' or 'https://www/youtube.com'"
        ]);
    }

}
