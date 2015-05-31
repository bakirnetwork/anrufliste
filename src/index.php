<?php

require __DIR__ . '/vendor/autoload.php';

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);

// variables and arrays to use in the template go here
$context = array('foo' => 'bar');
echo $twig->render('index.twig', $context);
