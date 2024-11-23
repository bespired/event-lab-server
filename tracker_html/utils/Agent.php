<?php

class Agent
{

    public function browserAgent($agent)
    {

        $values = $this->clearArray();

        $version = null;
        $hook1   = '';
        $hook2   = '';
        $hook3   = '';

        // compatible; MSIE is just MSIE
        if (strpos($agent, 'compatible; MSIE') > -1) {
            $agent = trim(str_replace('compatible;', '', $agent));
        }

        $values['device'] = 'desktop';

        // -- EXTRACT HOOKS TO KEEP PARTS

        $re = '/\([\s\S]*?\)/m';
        preg_match($re, $agent, $matches, PREG_OFFSET_CAPTURE, 0);

        $hook1 = $matches[0][0];
        $agent = str_replace($hook1, '§', $agent);

        preg_match($re, $agent, $matches, PREG_OFFSET_CAPTURE, 0);
        if ($matches) {
            $hook2 = $matches[0][0];
            $agent = str_replace($hook2, '§', $agent);
        }
        preg_match($re, $agent, $matches, PREG_OFFSET_CAPTURE, 0);
        if ($matches) {
            $hook3 = $matches[0][0];
            $agent = str_replace($hook3, '§', $agent);
        }

        // hook2 can be gecko
        $values['like_gecko'] = ($hook2 === '(KHTML, like Gecko)');

        // -- SPLIT PARTS

        $parts = explode(' ', $agent);

        // -- FIRST PART IS MOZILLA
        $values['mozilla'] = trim(array_shift($parts));

        // -- GET BROWSER VERSION
        // -- (iPhone14 ---    ) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/19A346 Safari/602.1',
        // -- (Android 7.0 ---)  AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/52.0.2743.98 Safari/537.36',
        // -- (Macintosh --)     Gecko/20100101 Goanna/6.7 Firefox/102.0 PaleMoon/33.3.0',

        // Versions
        $reads = [
            'safari'  => 'browser_engine_version',
            'gecko'   => 'browser_version',
            'version' => 'browser_version',
            'chrome'  => 'browser_version',
            'mobile'  => 'device',
        ];

        foreach ($reads as $start => $store) {
            foreach ($parts as $part) {
                $read = strtolower(trim($part));
                if (str_starts_with($read, $start)) {
                    if (strpos($read, '/')) {
                        $values[$store] = explode('/', $read)[1];
                    } else {
                        $values[$store] = $read;
                    }
                }
            }
        }

        // latest part is the sub_family
        $last = $parts[count($parts) - 1];
        if (strpos($last, '/')) {
            list($name, $version)  = explode("/", $last);
            $values['sub_family']  = trim($name);
            $values['sub_version'] = trim($version);
        }

        // Names
        $reads = [
            'gecko'           => 'family',
            'applewebkit'     => 'family',
            'heytapbrowser'   => 'sub_family',
            'kindle'          => 'sub_family',
            'nintendobrowser' => 'sub_family',
            'safari'          => 'browser',
            'chrome'          => 'browser',
            'firefox'         => 'browser',
        ];

        foreach ($reads as $start => $store) {
            foreach ($parts as $part) {
                $read = strtolower(trim($part));
                if (str_starts_with($read, $start)) {
                    $values[$store] = explode('/', $read)[0];
                }
            }
        }

        // Weird ones
        if (strpos($hook1, 'Nintendo 3DS') > -1) {
            $values['family']     = 'safari';
            $values['sub_family'] = 'nintendo 3ds';
        }

        // -- BOTS --
        $read = strtolower(trim($hook1));
        if (str_starts_with($read, '(compatible;')) {
            $parts = explode(';', $hook1);
            if (strpos($parts[1], '/')) {
                list($name, $version) = explode("/", $parts[1]);
            } else {
                $name = $parts[1];
            }
            $values['is_bot']          = true;
            $values['browser']         = trim($name);
            $values['browser_version'] = $version ? trim($version) : '';

        }
        $read = strtolower(trim($hook2));
        if (str_starts_with($read, '(applebot')) {
            $parts                     = explode(';', $hook2);
            list($name, $version)      = explode("/", $parts[0]);
            $values['is_bot']          = true;
            $values['browser']         = trim($name);
            $values['browser_version'] = trim($version);
        }

        // -- SYSTEM

        $nonalpha = str_split('0123456789,.-\/');
        $devices  = ['android', 'phone', 'nintendo', 'kindle'];

        if (! $values['is_bot']) {
            $parts = explode(';', str_replace(['(', ')'], '', $hook1));
            $parts = array_map(fn($a) => trim($a), $parts);

            $os       = trim(str_replace($nonalpha, '', $parts[0]));
            $platform = explode(' ', $os)[0];

            $values['platform']   = $platform;
            $values['os']         = $os;
            $values['os_variant'] = isset($parts[1]) ? trim($parts[1]) : '';

            foreach ($devices as $device) {
                foreach ($parts as $part) {
                    $read = strtolower(trim($part));
                    if (strpos($read, $device) > -1) {

                        $values['device']         = 'mobile';
                        $values['device_version'] = $part;
                    }
                }
            }

            $last    = trim($parts[count($parts) - 1]);
            $locales = ['en', 'en - us', 'en - gb', 'zh - cn', 'nl - be', 'nl - nl', 'nl'];
            if (in_array($last, $locales)) {
                $values['locale'] = $last;
            }
        }

        if (str_starts_with($values['os_variant'], 'Windows Phone')) {
            $values['browser'] = 'Windows Phone';
        }

        if ($values['browser'] === '') {
            $values['browser'] = $values['family'] === 'applewebkit' ? 'safari' : 'firefox';
        }

        foreach ($values as $key => $value) {
            $values[$key] = trim(strtolower($value));
        }

        $values['hash'] = md5($this->hashing($values));

        // echo "agent                  : " . $values['hash'] . "\n";

        // echo "is_bot                 : " . ($values['is_bot'] ? 1 : 0) . "\n";
        // echo "mozilla                : " . $values['mozilla'] . "\n";

        // echo "browser                : " . $values['browser'] . "\n";
        // echo "browser_version        : " . $values['browser_version'] . "\n";
        // echo "browser_engine_version : " . $values['browser_engine_version'] . "\n";

        // echo "like_gecko             : " . ($values['like_gecko'] ? 1 : 0) . "\n";

        // echo "family                 : " . $values['family'] . "\n";
        // echo "sub_family             : " . $values['sub_family'] . "\n";
        // echo "sub_version            : " . $values['sub_version'] . "\n";

        // echo "platform               : " . $values['platform'] . "\n";
        // echo "os                     : " . $values['os'] . "\n";
        // echo "os_variant             : " . $values['os_variant'] . "\n";
        // echo "device                 : " . $values['device'] . "\n";
        // echo "device_version         : " . $values['device_version'] . "\n";
        // echo "locale                 : " . $values['locale'] . "\n";
        // echo "\n\n";

        return (object) $values;
    }

    public function insertOnNew($db, $values)
    {
        $sql    = sprintf('SELECT COUNT(`id`) FROM `sys_browsers` WHERE `hash` = "%s"', $values->hash);
        $result = $db->count($sql);

        if ($result > 0) {
            return;
        }

        include_once "Handle.php";
        $items = [
            'hash', 'mozilla', 'browser', 'browser_engine_version', 'browser_version',
            'family', 'sub_family', 'sub_version', 'platform', 'os', 'os_variant',
            'device', 'device_version', 'locale',
        ];

        $slots['handle'] = Handle::create('brua', 'BUAT', time());
        foreach ($items as $item) {
            $slots[$item] = $values->$item;
        }
        $slots['is_bot']     = $values->is_bot ? 1 : 0;
        $slots['like_gecko'] = $values->like_gecko ? 1 : 0;

        $db->insert('sys_browsers', $slots);
    }

    public function hashing($values)
    {

        $terms = [
            'is_bot', 'mozilla', 'browser', 'browser_engine_version', 'browser_version', 'like_gecko',
            'family', 'sub_family', 'sub_version', 'platform', 'os', 'os_variant',
            'device', 'device_version', 'locale',
        ];

        foreach ($terms as $term) {

            switch ($term) {
                case 'like_gecko':
                case 'is_bot':
                    $hash[] = $values[$term] ? '1' : '0';
                    break;
                case 'mozilla':
                    $hash[] = strtolower(substr($values[$term], -3));
                    break;
                case 'browser_version':
                    $hash[] = strtolower($values[$term]);
                    break;
                default:
                    $hash[] = strtolower(substr($values[$term], -7));
            }
        }

        $hashing = str_replace(' ', '', rtrim(join(':', $hash), ":"));

        return $hashing;
    }

    private function clearArray()
    {
        $values['hash']                   = '';
        $values['is_bot']                 = '';
        $values['mozilla']                = '';
        $values['browser']                = '';
        $values['browser_engine_version'] = '';
        $values['browser_version']        = '';
        $values['like_gecko']             = '';
        $values['family']                 = '';
        $values['sub_family']             = '';
        $values['sub_version']            = '';
        $values['platform']               = '';
        $values['os']                     = '';
        $values['os_variant']             = '';
        $values['device']                 = '';
        $values['device_version']         = '';
        $values['locale']                 = '';

        return $values;
    }

    public function testAgents()
    {
        return [
            'Mozilla / 4.0(compatible; MSIE7.0; Windows PhoneOS7.0; Trident / 3.1; IEMobile / 7.0)',
            'Mozilla / 4.0(compatible; MSIE7.0; Windows PhoneOS7.0; Trident / 3.1; IEMobile / 7.0)Asus; Galaxy6 - 411',
            'Mozilla / 4.0(compatible; MSIE7.0; Windows PhoneOS7.0; Trident / 3.1; IEMobile / 7.0; HTC; T8788)',
            'Mozilla / 5.0(compatible; MSIE9.0; Windows PhoneOS7.5; Trident / 5.0; IEMobile / 9.0; Xbox)',
            'Mozilla / 5.0(compatible; MSIE10.0; Windows Phone10.0; Trident / 6.0; IEMobile / 10.0; ARM; Touch; NOKIA; Lumia920)',
            'Mozilla / 5.0(compatible; MSIE10.0; Windows Phone8.0; Trident / 6.0; IEMobile / 10.0; ARM; Touch; NOKIA; Lumia920)',
            'Mozilla / 5.0(compatible; MSIE10.0; Windows Phone8.0; Trident / 6.0; IEMobile / 10.0; ARM; Touch; NOKIA; Lumia920) - 449',
            'Mozilla / 5.0(compatible; MSIE10.0; Windows Phone8.0; Trident / 6.0; IEMobile / 10.0; ARM; Touch; NOKIA; Lumia920) - 619',
            'Mozilla / 5.0(compatible; Googlebot / 2.1;+http: //www.google.com/bot.html)',
            'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
            'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)',
            'Mozilla/5.0 (Mobile; Windows Phone 8.1; Android 4.0; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; Microsoft; Lumia 435) like iPhone OS 7_0_3 Mac OS X AppleWebKit/537 (KHTML, like Gecko) Mobile Safari/537',
            'Mozilla/5.0 (Mobile; Windows Phone 8.1; Android 4.0; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; Microsoft; Lumia 640 LTE; ANZ1074) like iPhone OS 7_0_3 Mac OS X AppleWebKit/537 (KHTML, like Gecko) Mobile Safari/537',
            'Mozilla/5.0 (Mobile; Windows Phone 8.1; Android 4.0; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; Microsoft; RM-1092) like iPhone OS 7_0_3 Mac OS X AppleWebKit/537 (KHTML, like Gecko) Mobile Safari/537',
            'Mozilla/5.0 (Mobile; Windows Phone 8.1; Android 4.0; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; NOKIA; Lumia 1320) like iPhone OS 7_0_3 Mac OS X AppleWebKit/537 (KHTML, like Gecko) Mobile Safari/537',
            'Mozilla/5.0 (Mobile; Windows Phone 8.1; Android 4.0; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; SAMSUNG; GT-I8750) like iPhone OS 7_0_3 Mac OS X AppleWebKit/537 (KHTML, like Gecko) Mobile Safari/537',
            'Mozilla/5.0 (Windows Phone 10.0; ARM; Trident/8.0; Touch; rv:11.0; IEMobile/11.0; Microsoft; Lumia 650; Cortana 1.6.1.1032; 10.0.0.0.10586.21) like Gecko',
            'Mozilla/5.0 (Windows Phone 8.1; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; NOKIA; Lumia 530) like Gecko',
            'Mozilla/5.0 (Windows Phone 8.1; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; NOKIA; Lumia 530) like Gecko-135',
            'Mozilla/5.0 (Windows Phone 8.1; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; NOKIA; Lumia 920) like Gecko-623',
            'Mozilla/5.0 (Windows Phone 8.1; ARM; Trident/8.0; Touch; rv:11.0; WebBrowser/8.1; IEMobile/11.0; NOKIA; Lumia 950) like Gecko-411',
            'Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Microsoft; Lumia 950) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Mobile Safari/537.36 Edge/13.1058',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.1958',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 OPR/114.0.0.',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 15.0; rv:102.0) Gecko/20100101 Goanna/6.7 Firefox/102.0 PaleMoon/33.3.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.1 Safari/605.1.15 (Applebot/0.1; +http://www.apple.com/go/applebot)',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.3',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9',
            'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Mobile Safari/537.36,gzip(gfe)',
            'Mozilla/5.0 (Linux; Android 13; SM-S901B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 13; SM-S901U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 14; XQ-EC72 Build/69.0.A.2.26; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/127.0.6533.97 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 7.0; Pixel C Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/52.0.2743.98 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 5.1; HUAWEI LYO-L02) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.101 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 10; SAMSUNG SM-G975F) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/12.1 Chrome/79.0.3945.136 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; U; Android 8.1.0; zh-cn; OPPO R11s Build/OPM1.171019.011) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/70.0.3538.80 Mobile Safari/537.36 HeyTapBrowser/10.7.5.5',
            'Mozilla/5.0 (Linux; Android 11; Lenovo YT-J706X) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36',
            'Mozilla/5.0 (X11; CrOS x86_64 8172.45.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.64 Safari/537.36',
            'Mozilla/5.0 (X11; U; Linux armv7l like Android; en-us) AppleWebKit/531.2+ (KHTML, like Gecko) Version/5.0 Safari/533.2+ Kindle/3.0+',
            'Mozilla/5.0 (iPhone14,3; U; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/19A346 Safari/602.1',
            'Mozilla/5.0 (iPhone12,1; U; CPU iPhone OS 13_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/15E148 Safari/602.1',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/69.0.3497.105 Mobile/15E148 Safari/605.1',
            'Mozilla/5.0 (PlayStation; PlayStation 5/2.26) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0 Safari/605.1.15',
            'Mozilla/5.0 (Nintendo Switch; WifiWebAuthApplet) AppleWebKit/601.6 (KHTML, like Gecko) NF/4.0.0.5.10 NintendoBrowser/5.1.0.13343',
            'Mozilla/5.0 (Nintendo 3DS; U; ; en) Version/1.7412.EU',
        ];
    }
}
