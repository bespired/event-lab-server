# event-lab-server
### Docker with PHP 8.3 and later versions for MAC with M1/M2


## Prerequisites:

Please install docker for mac, or docker desktop for mac with Apple Chip.
https://docs.docker.com/desktop/install/mac-install/

## Accessing https://

Create a SSL certificate to your system to trust the https connections.

`/event-lab-server/docker/traefik/eventlab.com.crt`

## How to use:

- Clone the repository
- Enter the repository folder
- Run `docker compose up -d` command


Currently, on writting this document, Docker will run 

- Traefik for routing
- Rachet for websockets
- Redis for fast caching
- MySQL for persistent database
- Three webservers endpoints 
  - Profile: Auth and CRM and Score
  - Tracker: This is where the form and tracker handler will go.
  - Public: where the frontend should go. Currently in project event-lab-front

Well.
Loads still needs to be done to call this even a beta


MySQL database can be created in 
`/event-lab-server/profile_html/database`  with command   
`> php createDB.php`   
`> php seedDB.php`   
Building and seeding is done via YAML files.

If you want to rerun the create you can remove the DB with    
`> php createDB.php`   