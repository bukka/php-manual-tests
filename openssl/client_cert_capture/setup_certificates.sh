#!/bin/bash

# Certificate Setup Script for PHP SSL Client Certificate Testing
# This script generates all necessary certificates for testing

set -e

echo "Creating certificates for SSL client certificate testing..."

# Create directories
mkdir -p certs
cd certs

# 1. Create CA (Certificate Authority)
echo "1. Creating Certificate Authority (CA)..."

# CA private key
openssl genpkey -algorithm RSA -out ca-key.pem -aes256 -pass pass:capassword

# CA certificate
openssl req -new -x509 -key ca-key.pem -out ca-cert.pem -days 365 -passin pass:capassword -subj "/C=US/ST=Test/L=Test/O=Test CA/CN=Test CA"

# 2. Create Server Certificate
echo "2. Creating Server Certificate..."

# Server private key
openssl genpkey -algorithm RSA -out server-key.pem

# Server certificate signing request
openssl req -new -key server-key.pem -out server-csr.pem -subj "/C=US/ST=Test/L=Test/O=Test Server/CN=localhost"

# Server certificate (signed by CA)
openssl x509 -req -in server-csr.pem -CA ca-cert.pem -CAkey ca-key.pem -CAcreateserial -out server-cert.pem -days 365 -passin pass:capassword

# 3. Create Client Certificate
echo "3. Creating Client Certificate..."

# Client private key
openssl genpkey -algorithm RSA -out client-key.pem

# Client certificate signing request
openssl req -new -key client-key.pem -out client-csr.pem -subj "/C=US/ST=Test/L=Test/O=Test Client/CN=Test Client"

# Client certificate (signed by CA)
openssl x509 -req -in client-csr.pem -CA ca-cert.pem -CAkey ca-key.pem -CAcreateserial -out client-cert.pem -days 365 -passin pass:capassword

# 4. Create combined certificate files (cert + key in one file for easier use)
echo "4. Creating combined certificate files..."

# Server combined (certificate + private key)
cat server-cert.pem server-key.pem > server-combined.pem

# Client combined (certificate + private key)
cat client-cert.pem client-key.pem > client-combined.pem

# Clean up CSR files
rm -f server-csr.pem client-csr.pem ca-cert.srl

cd ..

echo ""
echo "✅ Certificate setup complete!"
echo ""
echo "Generated files in ./certs/:"
echo "  📁 CA files:"
echo "    - ca-cert.pem (CA certificate)"
echo "    - ca-key.pem (CA private key, password: capassword)"
echo ""
echo "  🖥️  Server files:"
echo "    - server-cert.pem (server certificate)"
echo "    - server-key.pem (server private key)"
echo "    - server-combined.pem (certificate + key combined)"
echo ""
echo "  👤 Client files:"
echo "    - client-cert.pem (client certificate)"
echo "    - client-key.pem (client private key)"
echo "    - client-combined.pem (certificate + key combined)"
echo ""
echo "🚀 Now you can run:"
echo "   php server.php    (in one terminal)"
echo "   php client.php    (in another terminal)"
echo ""