
Navigate to the directory that you would like to install the dependencies
in and run the following code in a terminal.

```
mkdir tmp;
cd tmp;
wget https://github.com/pieroxy/lz-string/archive/1.4.4.tar.gz;
wget https://github.com/brix/crypto-js/archive/4.0.0.tar.gz;
wget https://github.com/davidshimjs/qrcodejs/tarball/04f46c6a0708418cb7b96fc563eacae0fbf77674 -O qrcode.tar.gz;

for arch in *.tar.gz; do tar -xf "$arch"; done;

mv ../resources/secrets ./;

rm -r ../resources;
mkdir ../resources;

mv secrets ../resources/;
mv lz-string* ../resources/lz-string;
mv crypto-js* ../resources/crypto-js;
mv *-qrcodejs* ../resources/qrcodejs;

cd ..;
rm -r tmp;
```
