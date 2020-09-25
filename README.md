## This is still in development ##
# Self-Destructing End-to-End Encrypted Note Sharing #
#### [Sample Site](https://secrets.mikej.tech/) ####
This is a Javascript / PHP e2e encrypted note sharing service that encodes nessesary information about the note into a single easy to share URL.
Since this note uses URL hashes the information to decrypt the note is never sent to the server.
All encryption and sensitive information remains in the browser and the encrypted note __without__ the decryption key sent to the server.

For ease of setup it plug and plays with my [framework](https://github.com/Sodium-Hydrogen/PHP-Framework) so all setting can be configured via the framework's interface.

Optionally if you don't want to setup this in standalone mode you can set `$framework_path` to an empty string and it will read from the config variables at the top of the php file.

#### About ####
For the client side javascript libraries this uses:
* [lz-string](https://github.com/pieroxy/lz-string) v1.4.4
  * This is for compressing the message
* [crypto-js](https://github.com/brix/crypto-js) v4.0.0
  * This does all the encrypting and decrypting
* [qrcodejs](https://github.com/davidshimjs/qrcodejs)
  * This generates a QR code for easy URL sharing

For installation this downloads and uses vetted versions and will only run them if the hash of the file matches the provided javascript hash. They are currently:

| File | SHA-256 Hash |
| ---- | ------------ |
| qrcodejs/qrcode.js | 		Puct6facZo+VZzY6k1jflVlguukADZ69ZkFGcPiOhzU= |
| lz-string/libs/lz-string.js | VKnqrEjU/F8ZC4hVDG+ULG96+VduFx/l3iTBag26gsM= |
| crypto-js/crypto-js.js | 	u605MhHOcevkqVw8DJ2q3X7kZTVTVXot4PjxIucLiMM= |
| secrets/script.js | 		NxmF615nq4cn2rDeC/G+a9iblJsOvLOrrgcM5Nseoa0= |
| secrets/style.css | 		8oSW4QhPKEbdBAh8SLtqvRj+hYn2nkqL69mRDjTXEJU= |



If you find any security holes please open an issue or pull request detailing the hole so it can get patched.
