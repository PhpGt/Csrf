<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__ . "/src")
    ->in(__DIR__ . "/test")
    ->exclude(__DIR__ . "/test/_Report")
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->setUsingCache(true)
    ->finder($finder)
;