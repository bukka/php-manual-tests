<?php
var_dump($p = proc_open(["invalid_command"], [], $pipes)); 
var_dump(proc_close($p));