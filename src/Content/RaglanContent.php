<?php

namespace App\Content;

final class RaglanContent
{
    public static function data(): array
    {
        return json_decode(
            file_get_contents(__DIR__ . '/../../resources/content/raglan.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
