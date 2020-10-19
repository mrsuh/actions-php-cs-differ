<?php

$finder = PhpCsFixer\Finder::create()->in('/code');

return PhpCsFixer\Config::create()
                        ->setFinder($finder)
                        ->setRiskyAllowed(true)
                        ->setRules([
                            'function_type_hint' => true,
                            'type_hint_return'   => true,
                            'strict_comparison'  => true
                        ]);
