<?php
require_once('config.php');

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Signature\Serializer;
use Jose\Component\Signature\JWSLoader;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker;

use Swagger\Client\Configuration;
use Swagger\Client\ApiException;
use Swagger\Client\Api\CharacterApi;

require_once('classes/esi/autoload.php');
require_once('vendor/autoload.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

// Credit to FuzzySteve https://github.com/fuzzysteve/eve-sso-auth/
class ESISSO
{
    private $code = null;
    private $scopes = array();
    private $ownerHash = null;
    private $refreshToken = null;
    private $accessToken = null;
    private $expires = null;
    protected $characterID = 0;
    protected $characterName = null;
    protected $error = false;
    protected $message = null;
    protected $id = null;
    protected $log;
    private $keySet = null;

	function __construct($id = null, $characterID = 0, $refreshToken = null, $failcount = 0)
	{
        $this->log = new ESILOG('log/esi.log');
        if($id != null) {
            $this->id = $id;
            $sql="SELECT * FROM esisso WHERE id=".$id;
            $qry = DB::getConnection();
            $result = $qry->query($sql);
            if($result->num_rows) {
                $row = $result->fetch_assoc();
                $this->characterID = $row['characterID'];
                $this->characterName = $row['characterName'];
                $this->ownerHash = $row['ownerHash'];
            }		
		} elseif ($characterID != 0) {
			$this->characterID = $characterID;
			$qry = DB::getConnection();
			$sql="SELECT * FROM esisso WHERE (characterID='".$characterID."')";
			$result = $qry->query($sql);
			if($result->num_rows) {
                $row = $result->fetch_assoc();
                $this->id = $row['id'];
                $this->characterName = $row['characterName'];
                $this->ownerHash = $row['ownerHash'];
			}
		} elseif (isset($this->refreshToken)) {
			$this->refreshToken = $refreshToken;
			$this->refresh();
		}
	}

	public function setCode($code) {
		$this->code = $code;
        $url = 'https://login.eveonline.com/v2/oauth/token';
        $header = array();
        $header[] = 'Authorization: Basic '.base64_encode(ESI_ID.':'.ESI_SECRET);
        $header[] = 'Content-Type: application/x-www-form-urlencoded';
        $header[] = 'Host: login.eveonline.com';
        $fields = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, ESI_USER_AGENT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields, '', '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $result = curl_exec($ch);
        if ($result === false) {
            $this->error = true;
            $this->message = (curl_error($ch));
            $this->log->error($this->message);
        }
        curl_close($ch);
        if (!$this->error){
            $response = json_decode($result);
            $this->accessToken = $response->access_token;
            $this->expires = (strtotime("now")+$response->expires_in);
            $this->refreshToken = $response->refresh_token;
            $result = $this->verifyLocal();
            return $result;
        } else {
            return false;
        }
	}

    public function verify() {
		if (!isset($this->accessToken)) {
            
            $this->error = true;
            $this->message = "No Access Token to verify.";
            $this->log->error($this->message);
            return false;
		} else {
            $verify_url = 'https://esi.evetech.net/verify';
            $ch = curl_init();
            $header = 'Authorization: Bearer '.$this->accessToken;
            curl_setopt($ch, CURLOPT_URL, $verify_url);
            curl_setopt($ch, CURLOPT_USERAGENT, ESI_USER_AGENT);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            $result = curl_exec($ch);
            if ($result === false) {
                $this->error = true;
                $this->message = (curl_error($ch));
                $this->log->error($this->message);
            }
            curl_close($ch);
            if ($this->error) {
			    return false;
		    }
            $response = json_decode($result);
            if (isset($response->error)) {
                $this->error = true;
                $this->message = $response->error;
                $this->log->error($this->message);
                return false;
            }
            if (!isset($response->CharacterID)) {
                $this->error = true;
                $this->message = "Failed to get character ID.";
                $this->log->error($this->message);
                return false;
            }
            $this->characterID = $response->CharacterID;
            $this->characterName = $response->CharacterName;
            $this->scopes = explode(' ', $response->Scopes);
            if ($this->scopes == null || $this->scopes == '') {
                $this->error = true;
                $this->message = 'Scopes missing.';
                $this->log->error($this->message);
                return false;
            }
            $this->ownerHash = $response->CharacterOwnerHash;
        }
		return true;
	}

    function getKeySet($forcePull = false) {
        $timeout = 3;
        $connect_timeout = 2;
        $jwks_url = "https://login.eveonline.com/oauth/jwks";
        $jwdksfile = __DIR__.'/../jwt_keyset.json';
        if (file_exists($jwdksfile) && !$forcePull) {
            try {
                $this->keySet = JWKSet::createFromJson(file_get_contents($jwdksfile));
            } catch (Exception $e) {
                $this->error = true;
                $this->message = "Failed to create keySet from File, re-downloading.";
                $this->log->error($this->message);
            } 
        } else if (!$forcePull) {
            $this->log->warning('No local key set found downloading from '.$jwks_url);
        }
        $client = new Client([//'handler' => $stack,
                              'defaults' => ['connect_timeout' => $connect_timeout,
                                             'timeout' => $timeout,
                                             'headers' => ['User-Agent' => ESI_USER_AGENT,
                                                           'Accept-Encoding' => 'gzip'],
                                            ]
                             ]);
        $res = $client->request('GET', $jwks_url);
        if ($res->getStatusCode() >= 200 && $res->getStatusCode() <= 299) {
            file_put_contents($jwdksfile, $res->getBody()); 
            $this->keySet = JWKSet::createFromJson($res->getBody()); 
            return $this->keySet;
        } else {
            $this->error = true;
            $this->message = "Failed to get Keyset for local verification from ".$jwks_url;
            $this->log->error($this->message);
            return null;
        }
    }

    public function verifyLocal() {
        if (!isset($this->accessToken)) {
            if (isset($this->refreshToken) && $this->refresh(false)) {
                if (!isset($this->accessToken)) {
                    $this->error = true;
                    $this->message = "No Access Token to verify.";
                    $this->log->error($this->message);
                    return false;
                }
            } else {
                $this->error = true;
                $this->message = "No Access Token to verify, no refresh token available.";
                $this->log->error($this->message);
                return false;
            }
        }
        if (!$this->keySet) {
            if (!$this->getKeySet()) {
                $this->error = true;
                $this->message = "Failed to get keySet.";
                $this->log->error($this->message);
                return false;
            }
        }
        $alg = new RS256();
        $jwk = $this->keySet->selectKey('sig', $alg);
    
        $algorithmManager = AlgorithmManager::create([$alg,]);
    
        $jwsVerifier = new JWSVerifier($algorithmManager);
    
        $jsonConverter = new StandardConverter();
        $serializerManager = Serializer\JWSSerializerManager::create([
            new Serializer\CompactSerializer($jsonConverter),
            new Serializer\JSONFlattenedSerializer($jsonConverter),
            new Serializer\JSONGeneralSerializer($jsonConverter),
        ]);
    
        $headerCheckerManager = HeaderCheckerManager::create([
            new AlgorithmChecker(['RS256']),
            ],[
            new JWSTokenSupport(),
        ]);
    
        $claimCheckerManager = ClaimCheckerManager::create([
            new Checker\IssuedAtChecker(),
            new Checker\NotBeforeChecker(),
            new Checker\ExpirationTimeChecker(),
        ]);

        $jwt = $serializerManager->unserialize($this->accessToken);
        try {
            $headerCheckerManager->check($jwt, 0);
        } catch (Exception $e) {
            $this->error = true;
            $this->message = "Ascess Token failed header verification.";
            $this->log->error($this->message);
            return false;
        }
        try {
            $claims = $jsonConverter->decode($jwt->getPayload());
            $claimCheckerManager->check($claims, ['iss', 'sub', 'owner', 'name', 'kid']);
        } catch (Exception $e) {
            $this->error = true;
            $this->message = "Ascess Token failed claim verification.";
            $this->log->error($this->message);
            return false;
        }
        $isVerified = $jwsVerifier->verifyWithKey($jwt, $jwk, 0);
        if (!$isVerified && $claims['kid'] != $jwk->get('kid')) {
            $this->error = true;
            $this->message = "Unknown JWT key ID. Forcing redownload.";
            $this->log->error($this->message);
            if (!$this->getKeySet()) {
                return false;
            }
            $jwk = $this->keySet->selectKey('sig', $alg);
            if ($claims['kid'] != $jwk->get('kid')) {
                $this->message .= "JWT key ID still doesn't match.";
                return false;
            }
            $isVerified = $jwsVerifier->verifyWithKey($jwt, $jwk, 0);
        }
        if (!$isVerified) {
            $this->error = true;
            $this->message = "Ascess Token could not be verified.";
            $this->log->error($this->message);
            return false;
        }
        if ($claims['iss'] != 'login.eveonline.com') {
            $this->error = true;
            $this->message = "Problem with the access token issuer.";
            $this->log->error($this->message);
            return false;
        }
        try {
            $sub = explode(':', $claims['sub']);
            if ($sub[0] == 'CHARACTER' && $sub[1] == 'EVE') {
                $this->characterID = $sub[2];
            } else {
                $this->error = true;
                $this->message = "Failed to extract character ID.";
                $this->log->error($this->message);
                return false;
            }
        } catch (Exception $e) {
            $this->error = true;
            $this->message = "Failed to get character ID.";
            $this->log->error($this->message);
            return false;
        }
        $this->characterName = $claims['name'];
        if (gettype($claims['scp']) == 'string' && strlen($claims['scp'])) {
            $this->scopes = array($claims['scp']);
        } else {
            $this->scopes = $claims['scp'];
        }
        if (!$this->scopes) {
            $this->error = true;
            $this->message = 'Scopes missing.';
            $this->log->error($this->message);
            return false;
        }
        $this->ownerHash = $claims['owner'];

        return true;
        
    }

	public function addToDb() {
		$refreshToken = $this->refreshToken;
		$ownerHash = $this->ownerHash;
		$characterID = $this->characterID;
        $characterName = $this->characterName;
        $accessToken = $this->accessToken;
        $expires = date('Y-m-d H:i:s', $this->expires);
		$failcount = 0;
		$enabled = true;
		$qry = DB::getConnection();
		$result = $qry->query("SELECT * FROM esisso WHERE (characterID='".$characterID."')");
        if ($result->num_rows == 0) {
            $esiapi = new ESIAPI();
            $charapi = $esiapi->getApi('Character');
            try {
                $charinfo = json_decode($charapi->getCharactersCharacterId($characterID, 'tranquility'));
                $characterName = $charinfo->name;
                $this->characterName = $characterName;
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Could not resolve character name: '.$e->getMessage();
                $this->log->error($this->message);
                return false;
            }
            $stmt = $qry->prepare("INSERT into esisso (characterID,characterName,ownerHash) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('iss', $cid, $cn, $oh);
                $cid = $characterID;
                $cn = $characterName;
                $oh = $ownerHash;
			    $stmt->execute();
                if ($stmt->errno) {
			        $this->error = true;
			        $this->message = $stmt->error;
                    $this->log->error($this->message);
			        return false;
                }
                $stmt->close();
            }
            $this->message = 'SSO credentials succesfully added.';
		} else {
			    $row = $result->fetch_assoc();
			    $id = $row['id'];
                        $this->characterName = $row['characterName'];
			    $sql="UPDATE esisso SET characterID={$characterID},ownerHash='{$ownerHash}' WHERE id={$id};";
                        $result = $qry->query($sql);
                        if (!$result) {
                                $this->error = true;
                                $this->message = $qry->getErrorMsg();
                                $this->log->error($this->message);
                                return false;
                        }
                        $this->message = 'SSO credentials updated.';
		}
        if ($this->scopes) {
            $stmt = $qry->prepare("INSERT into accessTokens (characterID,scope,refreshToken,accessToken,expires,failcount,enabled) VALUES (?, ?, ?, ?, ?, 0, 1) ON DUPLICATE KEY UPDATE refreshToken=?, accessToken=?, expires=?, failcount=0, enabled=1");
            if ($stmt) {
                $stmt->bind_param('isssssss', $cid, $scp, $rt, $at, $exp, $rt2, $at2, $exp2);
                foreach ($this->scopes as $scope) {
                    $cid = $characterID;
                    $scp = $scope;
                    $rt = $refreshToken;
                    $at = $accessToken;
                    $exp = $expires;
                    $rt2 = $refreshToken;
                    $at2 = $accessToken;
                    $exp2 = $expires;
                    $stmt->execute();
                    if ($stmt->errno) {
                        $this->error = true;
                        $this->message = $stmt->error;
                        $this->log->error('Error inserting into scopes table: '.$this->message);
                        return false;
                    }
                }
                $stmt->close();
            } else {
                $this->error = true;
                $this->message = $stmt->error;
                $this->log->error('Error inserting into scopes table: '.$this->message);
                return false;
            }
        }
        return true;
	}
	public function refresh( $verify = true ) {
        if (!isset($this->refreshToken)) {
	    $this->error = true;
             $this->message = "No refresh token set.";
             $this->log->error($this->message);
             return false;
	    }
	    $fields = array('grant_type' => 'refresh_token', 'refresh_token' => $this->refreshToken);
        $url = 'https://login.eveonline.com/v2/oauth/token';
	    $header = 'Authorization: Basic '.base64_encode(ESI_ID.':'.ESI_SECRET);
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_USERAGENT, ESI_USER_AGENT);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
	    curl_setopt($ch, CURLOPT_POST, count($fields));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields, '', '&'));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
 	    $result = curl_exec($ch);
            if ($result === false) {
                $this->error = true;
                $this->message = (curl_error($ch));
                $this->log->error($this->message);
            }
 	    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    curl_close($ch);
            if ($httpCode < 199 || $httpCode > 299) {
                $this->error = true;
                $this->message = ("Error: Response ".$httpCode." when refreshing the Access Token.");
                $this->log->error($this->message);
            }
            if ($this->error) {
                $this->increaseFailCount();
                return false;
            }
	    $response = json_decode($result);
        $this->accessToken = $response->access_token;
        if (!$this->verifyLocal()) {
            return false;
        }
        $this->expires = (strtotime("now")+$response->expires_in);
        $this->refreshToken = $response->refresh_token;
        $qry = DB::getConnection();
        $expires = date('Y-m-d H:i:s', $this->expires);
        $sql="UPDATE accessTokens SET accessToken='{$this->accessToken}',expires='{$expires}',refreshToken='{$this->refreshToken}' 
              WHERE characterID={$this->characterID} AND (scope='".implode("' OR scope='", $this->scopes)."')";
        $result = $qry->query($sql);
        if (!$result) {
                $this->error = true;
                $this->message = $qry->getErrorMsg();
                $this->log->error($this->message);
                return false;
        }
        $this->resetFailCount();
		return true;
	}

	public function increaseFailCount() {
        $this->failcount+=1;
        $qry = DB::getConnection();
        if ($this->failcount >= 10) { 
			$sql="UPDATE accessTokens SET failcount={$this->failcount},enabled=FALSE WHERE characterID={$this->characterID} AND accessToken={$this->accessToken};";
        } else {
            $sql="UPDATE accessTokens SET failcount={$this->failcount} WHERE characterID={$this->characterID} AND accessToken={$this->accessToken};";
        }
        $result = $qry->query($sql);
	}

    public function resetFailCount() {
        if ($this->failcount != 0) {
        	$this->failcount = 0;
        	$qry = DB::getConnection();
                $sql="UPDATE accessTokens SET failcount=0 WHERE characterID={$this->characterID} AND accessToken={$this->accessToken};";
	        $result = $qry->query($sql);
        }
    }


    public function getError() {
		return $this->error;
	}

        public function setMessage($message) {
                $this->message = $message;
        }

        public function getMessage() {
                return $this->message;
        }

        public function checkScopes($scopes) {
            $qry = DB::getConnection();
            $sql = "SELECT * FROM accessTokens WHERE characterID = ".$this->characterID;
            $result = $qry->query($sql);
            $tokens = array();
            if($result->num_rows) {
                while ($row = $result->fetch_assoc()) {
                    $this->scopes[] = $row['scope'];
                    $this->refreshToken = $row['refreshToken'];
                    $this->accessToken = $row['accessToken'];
                    $this->failcount = $row['failcount'];
                    $this->enabled = $row['enabled'];
                    $this->expires = strtotime($row['expires']);
                }
                if (!$this->enabled) {
                    return false;
                }
                if (array_diff($scopes, $this->scopes)) {
                    return false;
                }
                if ($this->hasExpired()) {
                    if (!$this->refresh(false)) {
                        return false;
                    }
                }
            } else {
                return false;
            }
            return true;
        }

        public function getDbScopes() {
            $qry = DB::getConnection();
            $sql = "SELECT * FROM accessTokens WHERE characterID = ".$this->characterID;
            $scopes = array();
            $result = $qry->query($sql);
            if($result->num_rows) {
                while ($row = $result->fetch_assoc()) {
                    $scopes[] = $row['scope'];
                }
            }
            return $scopes;
        }

        public function getAccessToken($scope) {
            if (in_array($scope, $this->scopes)) {
                if ($this->hasExpired()) {
                    if (!$this->refresh(false)) {
                        return null;
                    }
                }
            } else {
                $this->scopes = array();
                $qry = DB::getConnection();
                $sql = "SELECT * FROM accessTokens WHERE accessToken = (SELECT accessToken From accessTokens WHERE scope = '".$scope."' AND characterID = ".$this->characterID.") 
                       AND characterID = ".$this->characterID;
                $result = $qry->query($sql);
                if($result->num_rows) {
                    while ($row = $result->fetch_assoc()) {
                        $this->scopes[] = $row['scope'];
                        $this->refreshToken = $row['refreshToken'];
                        $this->accessToken = $row['accessToken'];
                        $this->failcount = $row['failcount'];
                        $this->enabled = $row['enabled'];
                        $this->expires = strtotime($row['expires']);
                    }
                    if (!$this->enabled) {
                        return null;
                    }
                    if ($this->hasExpired()) {
                        if (!$this->refresh(false)) {
                            return null;
                        }
                    }
                } else {
                    return null;
                }
            }
            return $this->accessToken;
        }

        public function getRefreshToken() {
                return $this->refreshToken;
        }

        public function getOwnerHash() { 
		return $this->ownerHash;
	}

        public function getCharacterID() {
		return $this->characterID;
	}

        public function getCharacterName() {
                if ($this->characterName == null || $this->characterName == '') {
                    $esiapi = new ESIAPI();
                    $charapi = $esiapi->getApi('Character');
                    try {
                        $charinfo = json_decode($charapi->getCharactersCharacterId($this->characterID, 'tranquility'));
                        $characterName = $charinfo->name;
                        $this->characterName = $characterName;
                    } catch (Exception $e) {
                        $this->error = true;
                        $this->message = 'Could not resolve character name: '.$e->getMessage();
                        $this->log->error($this->message);
                    }
                }
                return $this->characterName;
        }

        public function getFailcount() {
		return $this->failcount;
	}

	public function isEnabled() {
		return $this->enabled;
	}

    public function hasExpired() {
        if ($this->expires < strtotime("now")) {
            return true;
        } else {
		    return false;
	    }
    }

    public function getScopes() {
        return $this->scopes;
    }

}
?>
