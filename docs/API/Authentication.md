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

Some JavaScript pseudocode for calculating the secret (including additional query string nonce)
```js
request.url.query.add("timestamp=" + Math.round(Date.now()/1000).toString());
request.url.query.add("_nonce=" + CryptoJS.lib.WordArray.random(16).toString());
hmacSig = CryptoJS.HmacSHA256(request.url.query.toString(), secret);
pm.request.addHeader({key: "X-AUTH-SIGNATURE", value: hmacSig.toString(CryptoJS.enc.Base64)});
pm.request.addHeader({key: "X-AUTH-KEY", value: apiKey});
```

## Update

Version 2 of the authentication expands on using `[query string]`, to use `[method].[hash(request body)].[full url]` as
the content for signing, and specifying the auth version (2) in the `X-AUTH-VERSION` header. Additionally, a nonce is
no longer required, assuming that two identical requests are not made within the same second.

e.g.
```
GET.e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855.https://access-national-travel-survey.service.gov.uk/api/v1/survey-data?householdSerials=308802/2/1&timestamp=1772016731&_nonce=af9b8890
```

```js
request.url.query.add("timestamp=" + Math.round(Date.now()/1000).toString());
sigBody = `${request.method}.${CryptoJS.SHA256(request.body.toString())}.${request.url.toString()}`
hmac = CryptoJS.HmacSHA256(sigBody, secret);
request.addHeader({key: "X-AUTH-SIGNATURE", value: hmac.toString(CryptoJS.enc.Base64)});
request.addHeader({key: "X-AUTH-KEY", value: apiKey});
request.addHeader({key: "X-AUTH-VERSION", value: 2});
```