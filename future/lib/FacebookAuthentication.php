<?php

class FacebookAuthentication extends AuthenticationAuthority
{
    protected $api_key;
    protected $api_secret;
    protected $redirect_uri;
    protected $access_token;
    protected $expires;
    protected $useCache = true;
    protected $cache;
    
    public function auth($login, $password, &$user)
    {
        return AUTH_FAILED;
    }

    // facebook can only get the current user
    public function getUser($login)
    {
        if (empty($login)) {
            return new AnonymousUser();       
        }
        
        if ($this->useCache && $login != 'me') {
            $cacheFilename = "user_$login";
            if ($this->cache === NULL) {
                  $this->cache = new DiskCache(CACHE_DIR . "/Facebook", 900, TRUE);
                  $this->cache->setSuffix('.json');
                  $this->cache->preserveFormat();
            }

            if ($this->cache->isFresh($cacheFilename)) {
                $data = $this->cache->read($cacheFilename);
            } else {

                $url = sprintf("https://graph.facebook.com/%s?%s", $login, http_build_query(array(
                'fields'=>'id,first_name,last_name,email,picture,gender',
                'access_token'=>$this->access_token
                )));

                if ($data = @file_get_contents($url)) {
                    $this->cache->write($data, $cacheFilename);
                }
                
            }
        } else {
            $url = sprintf("https://graph.facebook.com/%s?%s", $login, http_build_query(array(
            'fields'=>'id,first_name,last_name,email,picture,gender',
            'access_token'=>$this->access_token
            )));

            $data = @file_get_contents($url);
        }
        
		// make the call
		if ($data) {
            $json = @json_decode($data, true);

            if (isset($json['id'])) {
                $user = new FacebookUser($this);
                $user->setUserID($json['id']);
                $user->setFirstName($json['first_name']);
                $user->setLastName($json['last_name']);
                if (isset($json['email'])) {
                    $user->setEmail($json['email']);
                }
                return $user;
            }        
        }
        
        return false;
    }
    
    public function login($login, $pass, Module $module)
    {
        if (isset($_GET['code'])) {
        
            // if a redirect_uri isn't set than we can't get an access token
            if (!isset($_SESSION['redirect_uri'])) {
                return AUTH_FAILED;
            }
            
            $this->redirect_uri = $_SESSION['redirect_uri'];
            unset($_SESSION['redirect_uri']);
            
            //get access token
            $url = "https://graph.facebook.com/oauth/access_token?" . http_build_query(array(
                'client_id'=>$this->api_key,
                'redirect_uri'=>$this->redirect_uri,
                'client_secret'=>$this->api_secret,
                'code'=>$_GET['code']
            ));
                                    
            if ($result = @file_get_contents($url)) {
                
                // results are in query string form
                $vars = explode("&", $result);
                foreach ($vars as $var) {
                    $var = explode("=", $var);
                    $arg = $var[0];
                    $value = $var[1];
                    switch ($arg) 
                    {
                        case 'access_token':
                        case 'expires':
                            $this->$arg = $_SESSION['fb_' . $arg] = $value;                        
                            break;
                    }
                }

                // get the current user via API
                if ($user = $this->getUser('me')) {
                    $session = $module->getSession();
                    $session->login($user);
                    return AUTH_OK;
                }  else {
                    return AUTH_FAILED; // something is amiss
                }

            } else {
                return AUTH_FAILED; //something is amiss
            }
            
        } elseif (isset($_GET['error'])) {
            //most likely the user denied
            return AUTH_FAILED;
        } else {
            //show the authorization/login screen
            
            //find out which "display" to use based on the device
            $deviceClassifier = $GLOBALS['deviceClassifier'];
            switch ($deviceClassifier->getPagetype())
            {
                case 'compliant':
                    $display = $deviceClassifier->isComputer() ? 'page' : 'touch';
                    break;
                case 'basic':
                    $display = 'wap';
                    break;
                default:
                    $display = 'page';
                    break;
            }
            
            
            $this->redirect_uri = $_SESSION['redirect_uri'] = FULL_URL_BASE . 'login/login?' . http_build_query(array('authority'=>$this->getAuthorityIndex()));
            $url = "https://graph.facebook.com/oauth/authorize?" . http_build_query(array(
            'client_id'=>$this->api_key,
            'redirect_uri'=>$this->redirect_uri,
            'scope'=>'user_about_me,email',
            'display'=>$display
            ));
            
            header("Location: $url");
            exit();
        }
    }
    
    protected function reset()
    {
        unset($_SESSION['fb_expires']);
        unset($_SESSION['fb_access_token']);
    }
    
    //does not support groups
    public function getGroup($group)
    {
        return false;
    }

    public function init($args)
    {
        $args = is_array($args) ? $args : array();
        if (!isset($args['API_KEY']) || !isset($args['API_SECRET'])) {
            throw new Exception("API key and secret not set");
        }
        
        $this->api_key = $args['API_KEY'];
        $this->api_secret = $args['API_SECRET'];
        if (isset($_SESSION['fb_access_token'])) {
            $this->access_token = $_SESSION['fb_access_token'];
        }
    }
}

class FacebookUser extends BasicUser
{
}