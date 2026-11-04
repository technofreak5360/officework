<?php

namespace App\Catalogue;

use Illuminate\Support\Str;

final readonly class Category
{
    public function __construct(
        public string $name,
        public string $slug,
    ) {}

    /**
     * Source category names aren't URL-safe (e.g. "men's clothing"), so the
     * rest of the app identifies categories by a slug we generate.
     */
    public static function fromName(string $name): self
    {
        return new self($name, Str::slug($name));
    }
}
