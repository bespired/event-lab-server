<?php

// echo chr(27) . chr(91) . 'H' . chr(27) . chr(91) . 'J';
echo "\e[H\e[J \n";

echo "This will cleanout everything in Docker \n";
$input = readline("Do you want to continue [y/N]: ");

if (! str_starts_with(strtoupper($input), 'Y')) {
    echo "Break requested. \n\n";
    exit;
}

$cmd = "docker image prune --all --force";
echo "$cmd \n";
print_r(shell_exec($cmd));
echo "\n";

$cmd = "docker volume prune --all --force";
echo "$cmd \n";
print_r(shell_exec($cmd));
echo "\n";

$cmd = "docker system prune --all --force";
echo "$cmd \n";
print_r(shell_exec($cmd));
echo "\n";

// $cmd = "docker image rmi -f $(docker images -a -q)";
// echo "$cmd \n";
// print_r(shell_exec($cmd));
// echo "\n";
