<?php

namespace NitroPack\SDK;

class Crypto {
    public static function generateKeyPair() {
        $config = array(
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privKey);

        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];

        $result = new \stdClass;
        $result->publicKey = $pubKey;
        $result->privateKey = $privKey;

        return $result;
    }

    public static function encrypt($data, $publicKey) {
        if (openssl_seal($data, $encrypted, $envKeys, array($publicKey), "aes256", $iv)) {
            return base64_encode($envKeys[0]) . "---END ENVKEY---" . base64_encode($encrypted) . "---END ENVKEY---" . base64_encode($iv);
        }

        return "";
    }

    public static function decrypt($data, $privateKey) {
        list($envKey, $sealedData, $iv) = array_map('base64_decode', explode("---END ENVKEY---", $data));

        if (openssl_open($sealedData, $decrypted, $envKey, $privateKey, "aes256", $iv)) {
            return $decrypted;
        }

        return "";
    }
}
