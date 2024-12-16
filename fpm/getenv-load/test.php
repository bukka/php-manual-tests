<?php

//var_dump(getenv());
// var_dump(isset(getenv()['argv']));
// var_dump(isset(getenv()['SERVER_NAME']));
// var_dump(isset(getenv()['DTEST']));
// var_dump(getenv('DTEST'));
putenv('DTEST=dt');
var_dump(getenv()['DTEST']);
var_dump(getenv('DTEST'));

// function notcalled()
// {
//     $_SERVER['argv'];
// }
