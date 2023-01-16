[Home](../../README.md) > [API](../API.md) > Authentication

# Authentication

- API access requires an api key and secret
- For each request:
    - The api key must be passed via the `X-AUTH-KEY` header
    - Calculate a signature and pass via the `X-AUTH-SIGNATURE` header
- Each request must include a `timestamp` parameter
- The signature is calculated by taking the request parameters in query string format (e.g.
  `option=foo&timestamp=1499827319559`) and calculating an HMAC of this string, using the secret key and the SHA256 algorithm.
- Signed requests are:
    - valid for 30 seconds after the given timestamp, and 5 seconds before (to allow for clock differences)
    - only valid once (a new timestamp/signature is required to make the same request again)

*Note*: Due to the low granularity of the timestamp, and exclusion of URL or request body from the signature it is
possible for a subsequent valid request to be rejected as a replayed signature. In this instance, adding a nonce to the
query string would resolve the issue.

Example postman script for calculating the secret (including additional query string nonce)
```js
let secret = CryptoJS.enc.Base64.parse(pm.variables.get("API_SECRET"));
pm.request.url.query.add("timestamp=" + Math.round(Date.now()/1000).toString());
pm.request.url.query.add("_nonce=" + Math.floor(Math.random()*4294967296).toString(16));
let hmacSig = CryptoJS.HmacSHA256(pm.request.url.query.toString(), secret);
pm.request.addHeader({key: "X-AUTH-SIGNATURE", value: hmacSig.toString(CryptoJS.enc.Base64)});
pm.request.addHeader({key: "X-AUTH-KEY", value: pm.variables.get("API_KEY")});
```
