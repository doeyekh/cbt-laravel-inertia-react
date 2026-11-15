<?php

namespace App\Services;

class ExamAccessTokenService
{
    /**
     * Characters omit ambiguous letters and digits to reduce entry mistakes.
     */
    private const TOKEN_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const TOKEN_LENGTH = 6;

    public function generateToken(): string
    {
        $token = '';
        $alphabetLength = strlen(self::TOKEN_ALPHABET);

        for ($index = 0; $index < self::TOKEN_LENGTH; $index++) {
            $token .= self::TOKEN_ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return $token;
    }

    public function hashToken(string $token): string
    {
        return hash_hmac('sha256', strtoupper($token), (string) config('app.key'));
    }

    public function matches(string $token, ?string $tokenHash): bool
    {
        return $tokenHash !== null
            && hash_equals($tokenHash, $this->hashToken($token));
    }
}
