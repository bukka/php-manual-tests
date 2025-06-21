#!/bin/bash

echo "🔍 Checking certificate files..."
echo ""

# Check if files exist
for file in "./certs/server-combined.pem" "./certs/client-cert.pem" "./certs/client-key.pem" "./certs/client-combined.pem"; do
    if [ -f "$file" ]; then
        echo "✅ $file exists"
    else
        echo "❌ $file missing"
    fi
done

echo ""
echo "📜 Server certificate details:"
openssl x509 -in ./certs/server-combined.pem -text -noout | grep -A1 "Subject:"

echo ""
echo "📜 Client certificate details:"
openssl x509 -in ./certs/client-cert.pem -text -noout | grep -A1 "Subject:"

echo ""
echo "🔑 Testing client certificate and key match:"
client_cert_modulus=$(openssl x509 -in ./certs/client-cert.pem -noout -modulus)
client_key_modulus=$(openssl rsa -in ./certs/client-key.pem -noout -modulus)

if [ "$client_cert_modulus" = "$client_key_modulus" ]; then
    echo "✅ Client certificate and key match"
else
    echo "❌ Client certificate and key do NOT match"
fi

echo ""
echo "🔑 Testing combined client certificate file:"
if openssl x509 -in ./certs/client-combined.pem -noout -text >/dev/null 2>&1; then
    echo "✅ Combined client certificate is valid"
else
    echo "❌ Combined client certificate is invalid"
fi

if openssl rsa -in ./certs/client-combined.pem -noout -check >/dev/null 2>&1; then
    echo "✅ Combined client private key is valid"
else
    echo "❌ Combined client private key is invalid"
fi
