<?php

// echo chr(27) . chr(91) . 'H' . chr(27) . chr(91) . 'J';
echo "\e[H\e[J \n";

echo "Copy letsencrypt keys to apache folders. \n\n";

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/fullchain.pem ~/eventlab/event-lab-server/docker/apache/mycert.crt";
echo "$cmd \n";
print_r(shell_exec($cmd));

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/privkey.pem   ~/eventlab/event-lab-server/docker/apache/mycert.key";
echo "$cmd \n";
print_r(shell_exec($cmd));

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/fullchain.pem ~/eventlab/event-lab-server/docker/traefik/mycert.crt";
echo "$cmd \n";
print_r(shell_exec($cmd));

$cmd = "sudo cp /etc/letsencrypt/live/bespired.com/privkey.pem   ~/eventlab/event-lab-server/docker/traefik/mycert.key";
echo "$cmd \n";
print_r(shell_exec($cmd));

echo "\n";
