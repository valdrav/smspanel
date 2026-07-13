<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Tüm DTO sınıfları için temel sınıf.
 *
 * @implements Arrayable<string, mixed>
 */
abstract readonly class BaseData implements Arrayable
{
    /**
     * DTO'yu dizi olarak döndürür.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Dizi verisinden DTO oluşturur.
     *
     * @param  array<string, mixed>  $data
     */
    abstract public static function fromArray(array $data): static;
}
