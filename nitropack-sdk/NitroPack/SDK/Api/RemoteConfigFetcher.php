<?php
namespace NitroPack\SDK\Api;

// Responsible for fetching the config from the API
class RemoteConfigFetcher extends Base {

    private $siteSecret;

    private static $algorithm = 'sha512'; // which algorithm to use for calculating challenge responses - needs to be one of the algos reported by hash_algos();
    private static $rounds = 5;           // how many times to apply the algorithm

    public function __construct($siteId, $siteSecret) {
        parent::__construct($siteId);
        $this->siteSecret = $siteSecret;
    }

    public function get() {
        // initiate the config request, expecting to receive a challenge
        $initiation = $this->initiateConfigRequest();
        if ($initiation->getStatus() != ResponseStatus::OK) {
            throw new \NitroPack\SDK\ConfigFetcherException('Error while initializing remote config fetch request. Response code: ' . $initiation->getStatus()); // TODO better error message and logging
        }

        try {
            /* Expecting a json response like the following:
             * { "cid": "...", "sc0": "...", "sc1": "...", "resp": "..." }
             * where cid is the challenge id, sc0/1 are the two challenge strings, and resp is the challenge response for sc0 to verify the server knows the secret */
            $challenge = json_decode($initiation->getBody(), true);
        } catch (\Exception $e) {
            throw new \NitroPack\SDK\ChallengeProcessingException('Error while processing remote config challenge'); // TODO better error message and logging
        }

        // verify that the challenge came from the API server
        $expectedResponse = $this->calculateChallengeResponse($challenge['sc0']);

        if (!$this->internal_hash_equals($expectedResponse, $challenge['resp'])) {
            throw new \NitroPack\SDK\ChallengeVerificationException('API server failed challenge verification when fetching the remote config'); // TODO better error message and logging
        }

        $challengeResponse = $this->respondToChallenge($challenge['sc1'], $challenge['cid']);

        if ($challengeResponse->getStatus() != ResponseStatus::OK) {
            throw new \NitroPack\SDK\ConfigFetcherException('Error while receiving remote config. Response code: ' . $challengeResponse->getStatus()); // TODO better error message and logging
        }

        return $challengeResponse->getBody();
    }

    private function initiateConfigRequest() {
        // start the config fetching process by sending the siteId to the server
        $path = 'config/getchallenge/' . $this->siteId;
        $httpResponse = $this->makeRequest($path, array(), array()); // path, headers, cookies
        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        $body = $httpResponse->getBody();
        return new Response($status, $body);
    }

    /**
     * Calculates the challenge response and sends it to the API server
     * $sc1 - the challenge sent by the server, whose challenge response was not provided
     * $cId - the id of the challenge
     */
    private function respondToChallenge($sc1, $cId) {
        $path = 'config/get/' . $this->siteId;
        $headers = array(
            'X-Challenge-Id' => $cId,
            'X-Challenge-Response' => $this->calculateChallengeResponse($sc1)
        );
        $httpResponse = $this->makeRequest($path, $headers, array()); // path, headers, cookies
        $status = ResponseStatus::getStatus($httpResponse->getStatusCode());
        $body = $httpResponse->getBody();
        return new Response($status, $body);
    }

    private function calculateChallengeResponse($challenge) {
        // We cannot use the following algorithms, even if hash_algos reports them as available, since then we cannot offer the same algo across all php versions:
        /**
         * 5.3: Added md2, ripemd256, ripemd320, salsa10, salsa20, snefru256, sha224
         * 5.4: Added joaat, fnv132, fnv164
         *      Removed Salsa10, Salsa 20
         * 5.6: Added gost-crypto
         * 7.1: Added sha512/224, sha512/256, sha3-224, sha3-256, sha3-384, sha3-512
         */
        $str = $this->siteSecret . $challenge;
        for ($i = 0; $i < RemoteConfigFetcher::$rounds; ++$i) {
            $str = hash(RemoteConfigFetcher::$algorithm, $str);
        }
        return $str;
    }

    /**
     * internal_hash_equals is an implementation of the PHP 5.6+ hash_equals function
     * Implementation taken from the MIT Licensed hash_equals library at https://github.com/realityking/hash_equals
     * Copyright notice included below
     *
     * This [function] is part of the hash_equals library
     *
     * For the full copyright and license information, please view the LICENSE
     * file that was distributed with this source code.
     *
     * @copyright Copyright (c) 2013-2014 Rouven Weßling <http://rouvenwessling.de>
     * @license http://opensource.org/licenses/MIT MIT
     *
     * License text acquired from https://github.com/realityking/hash_equals/blob/master/LICENSE February 21, 2018
     *
     * The MIT License (MIT)
     * 
     * Copyright (c) 2014 Rouven Weßling
     *
     * Permission is hereby granted, free of charge, to any person obtaining a copy
     * of this software and associated documentation files (the "Software"), to deal
     * in the Software without restriction, including without limitation the rights
     * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
     * copies of the Software, and to permit persons to whom the Software is
     * furnished to do so, subject to the following conditions:
     *
     * The above copyright notice and this permission notice shall be included in all
     * copies or substantial portions of the Software.
     *
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
     * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
     * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
     * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
     * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
     * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
     * SOFTWARE.
     */
    protected static function internal_hash_equals($known_string, $user_string) {
        // iSenseLabs modification to use the PHP function if available
        if (function_exists('hash_equals')) {
            return hash_equals($known_string, $user_string);
        }
        // We jump trough some hoops to match the internals errors as closely as possible
        $argc = func_num_args();
        $params = func_get_args();

        if ($argc < 2) {
            trigger_error("hash_equals() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }

        if (!is_string($known_string)) {
            trigger_error("hash_equals(): Expected known_string to be a string, " . gettype($known_string) . " given", E_USER_WARNING);
            return false;
        }
        if (!is_string($user_string)) {
            trigger_error("hash_equals(): Expected user_string to be a string, " . gettype($user_string) . " given", E_USER_WARNING);
            return false;
        }

        if (strlen($known_string) !== strlen($user_string)) {
            return false;
        }
        $len = strlen($known_string);
        $result = 0;
        for ($i = 0; $i < $len; $i++) {
            $result |= (ord($known_string[$i]) ^ ord($user_string[$i]));
        }
        // They are only identical strings if $result is exactly 0...
        return 0 === $result;
    }
}
