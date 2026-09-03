/**
 * SOUND — Audius streaming integration.
 * Uses the official Audius SDK-compatible discovery flow:
 *   1. Fetch a live discovery node from the Audius gateway.
 *   2. Stream via that node's /v1/tracks/{id}/stream endpoint.
 * The `app_name` parameter is required per Audius API guidelines.
 */
(function (window) {

    var APP_NAME = 'SOUND';
    var GATEWAY_URL = 'https://api.audius.co';
    var FALLBACK_HOSTS = [
        'https://discoveryprovider.audius.co',
        'https://discoveryprovider2.audius.co',
        'https://discoveryprovider3.audius.co'
    ];

    var _discoveredHost = null;

    /**
     * Returns a promise resolving to a live Audius discovery host string.
     */
    function getHost() {
        if (_discoveredHost) return Promise.resolve(_discoveredHost);

        return fetch(GATEWAY_URL + '?app_name=' + encodeURIComponent(APP_NAME))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var hosts = data && data.data;
                if (Array.isArray(hosts) && hosts.length > 0) {
                    // Pick a random live node from the list
                    var host = hosts[Math.floor(Math.random() * hosts.length)];
                    _discoveredHost = host.replace(/\/$/, '');
                    return _discoveredHost;
                }
                throw new Error('No hosts returned from gateway.');
            })
            .catch(function () {
                // Fall back to a known host if gateway is unreachable
                _discoveredHost = FALLBACK_HOSTS[0];
                return _discoveredHost;
            });
    }

    /**
     * Attempt to stream a track from Audius, trying multiple hosts on failure.
     * @param {string} trackId - Audius track ID stored in source_track_id.
     * @param {HTMLAudioElement} audio - The <audio> element to play into.
     * @returns {Promise}
     */
    function play(trackId, audio) {
        if (!trackId || !audio) return Promise.reject(new Error('Track is unavailable.'));

        return getHost().then(function (host) {
            var url = host + '/v1/tracks/' + encodeURIComponent(trackId) + '/stream?app_name=' + encodeURIComponent(APP_NAME);
            audio.src = url;
            return audio.play();
        }).catch(function (err) {
            // Reset cached host and try a fallback host directly
            _discoveredHost = null;
            var tried = FALLBACK_HOSTS.shift();
            if (tried) {
                FALLBACK_HOSTS.push(tried); // rotate
                var url = tried + '/v1/tracks/' + encodeURIComponent(trackId) + '/stream?app_name=' + encodeURIComponent(APP_NAME);
                audio.src = url;
                return audio.play();
            }
            return Promise.reject(err);
        });
    }

    window.SOUNDAudius = { play: play, getHost: getHost };

})(window);
