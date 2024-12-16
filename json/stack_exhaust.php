<?php

class Node
{
    public $next;
}

$firstNode = new Node();
$node = $firstNode;
for ($i = 0; $i < 200000; $i++) {
    $newNode = new Node();
    $node->next = $newNode;
    $node = $newNode;
}

//json_encode($circularDoublyLinkedList);
