<?php

include_once __DIR__ . '/../traits/Contacts.php';
include_once __DIR__ . '/../traits/Access.php';
include_once __DIR__ . '/../traits/Projects.php';

class Login
{
    use Access;
    use Contacts;
    use Projects;

    private $db;
    private $redis;
    private $project;
    private $contacts;
    private $attribs;

    public function __construct()
    {
        include_once __DIR__ . '/../utils/MyDB.php';
        include_once __DIR__ . '/../utils/MyCache.php';
        include_once __DIR__ . '/../utils/Response.php';
        include_once __DIR__ . '/../utils/Jwt.php';

        $this->db    = new MyDB();
        $this->redis = new MyCache();
    }

    private function validateUsername($payload)
    {
        if (! isset($payload->username)) {
            return false;
        }
        $aap = strpos($payload->username, '@');
        if ($aap < 1) {
            return false;
        }

        $dot = strpos($payload->username, '.', $aap);
        if ($dot < $aap) {
            return false;
        }

        return true;
    }

    private function validatePassword($payload)
    {
        if (! isset($payload->password)) {
            return false;
        }

        return strlen($payload->password) > 8;
    }

    private function anyFill($template, $count)
    {
        $wheres = [];
        for ($number = 1; $number <= $count; $number++) {
            $wheres[] = str_replace('{#}', $number, $template);
        }

        return $wheres;
    }

    public function handle($router)
    {
        $this->project = $router->projectChar;

        switch ($router->action) {
            case 'member':
            case 'backend':

                if (! $this->validateUsername($router->payload)) {
                    Response::error('Not an email.');
                }
                if (! $this->validatePassword($router->payload)) {
                    Response::error('Not a password.');
                }

                // Multiple projects and multiple roles make this not easy.
                $profiles = [];
                $handles  = null;
                $contacts = $this->loadContacts($router->payload->username);
                if ($contacts) {
                    $handles = array_map(fn($a) => $a['handle'], $contacts);
                    foreach ($contacts as $contact) {
                        $profiles[$contact['project']] = $contact['profile'];
                    }
                }

                if (! $handles) {
                    Response::error('Not a user.');
                }

                $accesses = $this->loadAccess($handles, $router->action);

                $roles    = [];
                $areaKeys = ['area_1', 'area_2', 'area_3'];

                $dateNow = new DateTime();

                // Because we have the password versions in one record
                // we need to check all of them

                foreach ($accesses as $access) {
                    foreach ($areaKeys as $areaKey) {
                        $expireKey = str_replace('area', 'expire', $areaKey);
                        $hashKey   = str_replace('area', 'hash', $areaKey);
                        if ($access->$areaKey == $router->action) {
                            $expire = $access->$expireKey;
                            $hash   = $access->$hashKey;

                            // is the hash outdated?
                            $dateThen = new DateTime($access->$expireKey);
                            $dateDiff = $dateThen->getTimestamp() - $dateNow->getTimestamp();

                            if ($dateDiff > 0) {
                                // is the hash the right one ?
                                if (password_verify($router->payload->password, $hash)) {
                                    $roles[$access->project] = $access->role;
                                    $contactHandle           = $access->contact;
                                }
                            }
                        }
                    }
                }

                // If user has no roles on any project...

                if (count($roles) == 0) {
                    Response::error('Expired.');
                }

                // If user has cloned rights on any other project...
                // ( we don't copy same password hashes to other projects )

                foreach ($accesses as $key => $access) {
                    // clone is on contact and can point to wrong record for this area
                    if ($access->clone && isset($accesses[$access->clone])) {
                        $clone  = $accesses[$access->clone];
                        $accprj = $access->project;
                        if (isset($roles[$clone->project])) {
                            $roles[$accprj] = isset($access->role) ? $access->role : $clone->role;
                        }
                    }
                }

                //
                // Should implement TFA here...
                //

                // Keep the jwt clean.
                $rolesConcats = [];
                foreach ($roles as $project => $role) {
                    $rolesConcats[] = $project . ':' . $role;
                    $projectChars[] = $project;
                }

                $domains = $this->loadDomains($projectChars);
                foreach ($domains as $key => $domain) {
                    $domains[$key]['profile'] = $profiles[$key];
                }

                $payload = [
                    'rol' => join(';', $rolesConcats),
                    'hdl' => str_replace('-cont', '', $contactHandle),
                ];

                $jwt   = new JWT();
                $token = $jwt->create($payload);

                $success = [
                    'contact' => $payload['hdl'],
                    'role'    => $payload['rol'],
                    'area'    => $router->action,
                    'domains' => $domains,
                ];

                // Put login in redis...
                $this->redis->labLogin($token, $success);

                // Put login event on timeline

                // close connections
                $this->db->close();
                $this->redis->close();

                // Tell
                Response::success($success, $token);

            // no break
            case 'create':
                echo 'create login';
        }
    }
}
