<?php

echo "\e[H\e[J \n";

echo "Clear manual changes.\n";
$cmd = "git restore .";
echo "$cmd \n";
print_r(shell_exec($cmd));
echo "\n";

echo "Clear manual changes.\n";
$cmd = "git clean -f ";
echo "$cmd \n";
print_r(shell_exec($cmd));
echo "\n";

echo "Pull new code.\n";
$cmd = "git pull";
echo "$cmd \n";
print_r(shell_exec($cmd));
echo "\n";

echo "Copy letsencrypt keys to apache folders. \n\n";

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/fullchain.pem ";
$cmd .= "~/eventlab/event-lab-server/docker/apache/ssl/mycert.crt";
echo "$cmd \n";
print_r(shell_exec($cmd));

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/privkey.pem ";
$cmd .= "~/eventlab/event-lab-server/docker/apache/ssl/mycert.key";
echo "$cmd \n";
print_r(shell_exec($cmd));

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/fullchain.pem ";
$cmd .= "~/eventlab/event-lab-server/docker/traefik/mycert.crt";
echo "$cmd \n";
print_r(shell_exec($cmd));

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/privkey.pem ";
$cmd .= "~/eventlab/event-lab-server/docker/traefik/mycert.key";
echo "$cmd \n";
print_r(shell_exec($cmd));

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/fullchain.pem ";
$cmd .= "~/eventlab/event-lab-server/docker/mysql/dbdata/ca.pem";
echo "$cmd \n";
print_r(shell_exec($cmd));

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/privkey.pem ";
$cmd .= "~/eventlab/event-lab-server/docker/mysql/dbdata/ca-key.pem";
echo "$cmd \n";
print_r(shell_exec($cmd));

echo "Change server names.\n";
$cmd = "php php/servername.php";
echo "$cmd \n";
print_r(shell_exec($cmd));
echo "\n";

echo "Change arm to amd.\n";
$cmd = "php php/amd.php";
echo "$cmd \n";
print_r(shell_exec($cmd));
echo "\n";

echo "\n";
