<?php
namespace ZEGO;

class ZegoServerAssistant {
    private static function makeNonce() {
        return random_int(1, PHP_INT_MAX);
    }

    private static function makeRandomIv($number = 16) {
        return random_bytes($number);
    }

    public static function generateToken04($appId, $userId, $secret, $effectiveTimeInSeconds, $payload) {
        $assistantToken = new ZegoAssistantToken();
        $assistantToken->code = ZegoErrorCodes::success;

        if ($appId == 0) {
            $assistantToken->code = ZegoErrorCodes::appIDInvalid;
            $assistantToken->message = 'appID invalid';
            return $assistantToken;
        }

        if ($userId == '') {
            $assistantToken->code = ZegoErrorCodes::userIDInvalid;
            $assistantToken->message = 'userID invalid';
            return $assistantToken;
        }

        $keyLen = strlen($secret);
        if ($keyLen != 32) {
            $assistantToken->code = ZegoErrorCodes::secretInvalid;
            $assistantToken->message = 'secret must be a 32 byte string';
            return $assistantToken;
        }

        if ($effectiveTimeInSeconds <= 0) {
            $assistantToken->code = ZegoErrorCodes::effectiveTimeInSecondsInvalid;
            $assistantToken->message = 'effectiveTimeInSeconds invalid';
            return $assistantToken;
        }

        $timestamp = time();
        $nonce = self::makeNonce();
        $data = [
            'app_id' => $appId,
            'user_id' => $userId,
            'nonce' => $nonce,
            'ctime' => $timestamp,
            'expire' => $timestamp + $effectiveTimeInSeconds,
            'payload' => $payload
        ];

        $cipher = 'aes-256-cbc';
        $plaintext = json_encode($data, JSON_BIGINT_AS_STRING);
        $iv = self::makeRandomIv();
        $encrypted = openssl_encrypt($plaintext, $cipher, $secret, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            $assistantToken->code = ZegoErrorCodes::secretInvalid;
            $assistantToken->message = 'Token encryption failed';
            return $assistantToken;
        }

        $binary = pack('J', $data['expire']);
        $binary .= pack('na*na*', strlen($iv), $iv, strlen($encrypted), $encrypted);
        $assistantToken->token = '04' . base64_encode($binary);
        return $assistantToken;
    }
}
