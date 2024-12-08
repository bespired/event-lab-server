<?php

echo "\e[H\e[J \n";

echo "Copy letsencrypt keys to apache folders. \n\n";

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/fullchain.pem ~/eventlab/event-lab-server/docker/apache/ssl/mycert.crt";
echo "$cmd \n";
print_r(shell_exec($cmd));

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/privkey.pem   ~/eventlab/event-lab-server/docker/apache/ssl/mycert.key";
echo "$cmd \n";
print_r(shell_exec($cmd));

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/fullchain.pem ~/eventlab/event-lab-server/docker/traefik/mycert.crt";
echo "$cmd \n";
print_r(shell_exec($cmd));

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/privkey.pem   ~/eventlab/event-lab-server/docker/traefik/mycert.key";
echo "$cmd \n";
print_r(shell_exec($cmd));

echo "\n";
