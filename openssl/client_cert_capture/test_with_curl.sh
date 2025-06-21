#!/bin/bash

# Test SSL server with curl commands
# Make sure server.php is running first!

PORT=8443
HOST=localhost

echo "Testing SSL server with curl..."
echo "Make sure server.php is running on port $PORT"
echo ""

# # Test 1: No client certificate
# echo "=== Test 1: curl WITHOUT client certificate ==="
# curl -v \
#   --insecure \
#   --connect-timeout 5 \
#   https://$HOST:$PORT/ 2>&1 | grep -E "(SSL|TLS|certificate|connected|error)"

# echo ""
# echo "=== Test 2: curl WITH client certificate ==="

# Test 2: With client certificate
curl -v \
  --insecure \
  --cert ./certs/client-cert.pem \
  --key ./certs/client-key.pem \
  --connect-timeout 5 \
  https://$HOST:$PORT/ 2>&1 | grep -E "(SSL|TLS|certificate|connected|error)"

# echo ""
# echo "=== Test 3: curl WITH combined client certificate ==="

# # Test 3: With combined certificate file
# curl -v \
#   --insecure \
#   --cert ./certs/client-combined.pem \
#   --connect-timeout 5 \
#   https://$HOST:$PORT/ 2>&1 | grep -E "(SSL|TLS|certificate|connected|error)"

# echo ""
# echo "=== Test 4: openssl s_client WITHOUT client certificate ==="

# # Test 4: openssl s_client without client cert
# echo | openssl s_client -connect $HOST:$PORT -verify_return_error 2>/dev/null | grep -E "(Verify return code|Certificate chain|subject|issuer)"

# echo ""
# echo "=== Test 5: openssl s_client WITH client certificate ==="

# # Test 5: openssl s_client with client cert
# echo | openssl s_client -connect $HOST:$PORT -cert ./certs/client-cert.pem -key ./certs/client-key.pem -verify_return_error 2>/dev/null | grep -E "(Verify return code|Certificate chain|subject|issuer)"

# echo ""
# echo "Done! Check server output to see which connections received client certificates."