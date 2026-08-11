<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NumberSequence extends Model
{
    protected $fillable = [
        'code',
        'label',
        'prefix',
        'suffix',
        'next_number',
        'padding_length',
    ];

    /**
     * Atomically fetch-and-increment the next number for a given sequence
     * code, returning the fully formatted string (prefix + padded number +
     * suffix). Locks the row for the duration of the transaction so two
     * concurrent requests can never receive the same number.
     */
    public static function next(string $code): string
    {
        return DB::transaction(function () use ($code) {
            $sequence = static::where('code', $code)->lockForUpdate()->first();

            if (! $sequence) {
                throw new \RuntimeException("Number sequence '{$code}' has not been set up yet. Create it under Administration > Number Sequences.");
            }

            $number = $sequence->next_number;
            $sequence->increment('next_number');

            return $sequence->format($number);
        });
    }

    /**
     * Preview what the next number would look like without consuming it.
     * Useful for showing "next up" in the UI.
     */
    public function previewNext(): string
    {
        return $this->format($this->next_number);
    }

    public function format(int $number): string
    {
        $padded = str_pad((string) $number, $this->padding_length, '0', STR_PAD_LEFT);

        return "{$this->prefix}{$padded}{$this->suffix}";
    }
}
