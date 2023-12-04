<?php

namespace NitroPack\HttpClient;

class HttpClientState {
    const READY = "Ready";
    const INIT = "Initializing for new request";
    const CONNECT = "Connecting to remote host";
    const SSL_HANDSHAKE = "Initializing SSL";
    const SEND_REQUEST = "Sending request data";
    const DOWNLOAD = "Downloading response data";
}