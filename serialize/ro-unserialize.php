<?php

class ImmutableUserV1 {
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly DateTime $createdAt
    ) {}
    
    private $isActive = true;
    
    public function __sleep(): array {
        return ['id', 'email', 'createdAt'];
    }
    
    public function __wakeup(): void {
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
        if (empty($this->id)) {
            throw new InvalidArgumentException('ID cannot be empty');
        }
        $this->isActive = true;
    }
        
    public function getStatus(): string {
        return $this->isActive ? 'active' : 'inactive';
    }
}

class ImmutableUserV2 {
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly DateTime $createdAt
    ) {}

    private $isActive = true;
    
    public function __serialize(): array {
        return [
            'id' => $this->id,
            'email' => $this->email, 
            'createdAt' => $this->createdAt,
        ];
    }
    
    public function __unserialize(array $data): void {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
        if (empty($data['id'])) {
            throw new InvalidArgumentException('ID cannot be empty');
        }
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->createdAt = $data['createdAt'];
        $this->isActive = true;
    }
        
    public function getStatus(): string {
        return $this->isActive ? 'active' : 'inactive';
    }
}

// Testing both approaches
echo "=== Using __wakeup() ===\n";
$user = new ImmutableUserV1('123', 'user1@example.com', new DateTime('2024-01-01'));
echo "Original: {$user->id} - {$user->email} - {$user->getStatus()}\n";

// Serialize
$serialized = serialize($user);
echo "Serialized length: " . strlen($serialized) . " bytes\n";
echo "Serialized format: " . $serialized . "\n";

// Unserialize (this will call __wakeup)
$restored = unserialize($serialized);
echo "Restored: {$restored->id} - {$restored->email} - {$restored->getStatus()}\n";
echo "Date preserved: " . $restored->createdAt->format('Y-m-d') . "\n\n";

echo "=== Using __unserialize() ===\n";
$user2 = new ImmutableUserV2('456', 'user2@example.com', new DateTime('2024-02-01'));
echo "Original: {$user2->id} - {$user2->email} - {$user2->getStatus()}\n";

// Serialize
$serialized2 = serialize($user2);
echo "Serialized length: " . strlen($serialized2) . " bytes\n";
echo "Serialized format: " . $serialized2 . "\n";

// Unserialize (this will call __unserialize)
$restored2 = unserialize($serialized2);
echo "Restored: {$restored2->id} - {$restored2->email} - {$restored2->getStatus()}\n";
echo "Date preserved: " . $restored2->createdAt->format('Y-m-d') . "\n\n";

// Error handling example
echo "=== Error Handling ===\n";
try {
    $badUser = new ImmutableUserV1('789', 'invalid-email', new DateTime());
    $badSerialized = serialize($badUser);
    
    // This will throw an exception during __wakeup()
    unserialize($badSerialized);
} catch (InvalidArgumentException $e) {
    echo "Caught error with __wakeup(): " . $e->getMessage() . "\n";
}

try {
    $badUser2 = new ImmutableUserV2('789', 'another-invalid-email', new DateTime());
    $badSerialized2 = serialize($badUser2);
    
    // This will throw an exception during __unserialize()
    unserialize($badSerialized2);
} catch (InvalidArgumentException $e) {
    echo "Caught error with __unserialize(): " . $e->getMessage() . "\n";
}

echo "\n=== Performance Comparison ===\n";

// Performance test parameters
$iterations = 100000;
echo "Testing with {$iterations} iterations...\n\n";


// Test __unserialize() performance
$testUser2 = new ImmutableUserV2('perf-test-2', 'test2@example.com', new DateTime('2024-01-01'));
$serializedUser2 = serialize($testUser2);

echo "Testing __unserialize() performance:\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $temp = unserialize($serializedUser2);
}
$end = microtime(true);
$unserializeTime = $end - $start;
echo "Time: " . number_format($unserializeTime, 4) . " seconds\n";
echo "Per operation: " . number_format(($unserializeTime / $iterations) * 1000000, 2) . " microseconds\n\n";

// Test __wakeup() performance
$testUser1 = new ImmutableUserV1('perf-test-1', 'test1@example.com', new DateTime('2024-01-01'));
$serializedUser1 = serialize($testUser1);

echo "Testing __wakeup() performance:\n";
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $temp = unserialize($serializedUser1);
}
$end = microtime(true);
$wakeupTime = $end - $start;
echo "Time: " . number_format($wakeupTime, 4) . " seconds\n";
echo "Per operation: " . number_format(($wakeupTime / $iterations) * 1000000, 2) . " microseconds\n\n";

// Compare results
$difference = abs($wakeupTime - $unserializeTime);
$percentDiff = ($difference / min($wakeupTime, $unserializeTime)) * 100;
$faster = $wakeupTime < $unserializeTime ? '__wakeup()' : '__unserialize()';

echo "Results:\n";
echo "- __wakeup() total time: " . number_format($wakeupTime, 4) . "s\n";
echo "- __unserialize() total time: " . number_format($unserializeTime, 4) . "s\n";
echo "- Difference: " . number_format($difference, 4) . "s\n";
echo "- {$faster} is " . number_format($percentDiff, 1) . "% faster\n\n";

// Serialization size comparison
echo "=== Serialization Format Comparison ===\n";
echo "__wakeup() serialized size: " . strlen($serializedUser1) . " bytes\n";
echo "__unserialize() serialized size: " . strlen($serializedUser2) . " bytes\n";
echo "__wakeup() format: O (Object)\n";
echo "__unserialize() format: C (Custom)\n\n";

// Memory usage test
echo "=== Memory Usage Test ===\n";
$memoryTests = 10000;

// Memory test for __wakeup()
$startMemory = memory_get_usage();
$objects1 = [];
for ($i = 0; $i < $memoryTests; $i++) {
    $objects1[] = unserialize($serializedUser1);
}
$endMemory = memory_get_usage();
$wakeupMemory = $endMemory - $startMemory;
unset($objects1);

// Memory test for __unserialize()
$startMemory = memory_get_usage();
$objects2 = [];
for ($i = 0; $i < $memoryTests; $i++) {
    $objects2[] = unserialize($serializedUser2);
}
$endMemory = memory_get_usage();
$unserializeMemory = $endMemory - $startMemory;
unset($objects2);

echo "Memory usage for {$memoryTests} objects:\n";
echo "- __wakeup(): " . number_format($wakeupMemory / 1024, 2) . " KB\n";
echo "- __unserialize(): " . number_format($unserializeMemory / 1024, 2) . " KB\n";
echo "- Per object difference: " . number_format(abs($wakeupMemory - $unserializeMemory) / $memoryTests, 2) . " bytes\n";