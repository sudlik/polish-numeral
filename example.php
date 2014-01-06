<!doctype html>
<meta charset="utf-8">

<?php

require 'vendor/autoload.php';

use Sudlik\PolishNumeral\Converter;

echo new Converter(123456789) . '<br>';
echo new Converter(987654321) . '<br>';