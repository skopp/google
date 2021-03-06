<?php
// FIX - to include the base OAuth lib not in alphabetical order
$oauth = getPath("helpers/kiss_oauth.php");
( $oauth ) ? require_once( $oauth ) : die("The site is offline as a nessesary plugin is missing. Please install oauth: github.com/kisscms/oauth");

/* Discus for KISSCMS */
class Google_OAuth extends KISS_OAuth_v2 {
	
	function  __construct( $api="google", $url = "https://accounts.google.com/o/oauth2" ) {
		
		$this->url = array(
			'authorize' 		=> $url ."/auth", 
			'access_token' 		=> $url ."/token", 
			'refresh_token' 		=> $url ."/token", 
			//'refresh_token' 	=> $url ."/refresh_token/"
		);
		
		parent::__construct( $api, $url );
		
	}
	
	public static function link( $scope="" ){
		
		$oauth = new Google_OAuth();
		
		// Modify scope to full urls (according to the Google API spec)
		$scope = explode(",", $scope);
		$services = $oauth->services(); 
		foreach( $scope as $i => $permission){
			$scope[$i] = $services[$permission];
		}
		$scope = implode(" ", $scope);
		
		
		$request = array(
			"params" => array(
									"access_type" => "offline",
								 	"approval_prompt" => "force"
								)
		);
		
		parent::link($scope, $request);
		
	}
	
	// additional params not covered by the default OAuth implementation
	public function access_token( $params, $request=array() ){
		
		$request = array(
			"params" => array("grant_type" => "authorization_code")
		);
		
		parent::access_token($params, $request);

	}
	
	public function refreshToken($request=array()){
		
		$request = array(
			"params" => array( "grant_type" => "refresh_token" )
		);
		
		parent::refreshToken($request);
	}
	
	function checkToken(){
		
		// check if theres's an expiry date
		//$expires_in = (int) $_SESSION['oauth']['google']['created'] - strtotime("now"); // seconds
		$expiry = ( empty($_SESSION['oauth']['google']['expiry']) ) ? false : $_SESSION['oauth']['google']['expiry'];
		
		// reset the authentication
		if( !$expiry || !$this->refresh_token) {
			// something is seriously wrong - reinstate authentication
			return false;
		}
		
		$expires_in = strtotime("now") - strtotime( $expiry ); // seconds
		
		if( $expires_in < 500 ){
			//$this->refreshToken();
		}
		
		// all good...
		return true;
	
	}
	
	function creds( $data=NULL ){
		
		// restore credentials externally (from db?)
		if( !empty($data) && empty($_SESSION['oauth']['google']) ) $_SESSION['oauth']['google'] = $data;
		
		// check if the token is valid
		$this->checkToken();
		
		// return the details from the session
		return ( empty($_SESSION['oauth']['google']) ) ? false : $_SESSION['oauth']['google'];
		
	}
	
	function save( $response ){
		
		// erase the existing creds
		unset($_SESSION['oauth']['google']);
		
		// save to the user session 
		$auth = json_decode( $response, TRUE);
		
		// in case of an error - don't save anything...
		if( is_array( $auth ) && array_key_exists("error", $auth) ) return;
		
		if( is_array( $auth ) && array_key_exists("expires_in", $auth) ) {
			// variable expires is the number of seconds in the future - will have to convert it to a date
			$auth['expiry'] = date(DATE_ISO8601, (strtotime("now") + $auth['expires_in'] ) );
		}
		
		// add another attribute 'created' that's used in the official API
		$auth['created'] = strtotime("now");
		
		// FIX: Refresh token isn't passed with auto-confirm validation - will need to merge with existing values
		$_SESSION['oauth']['google'] = ( !empty( $_SESSION['oauth']['google'] ) ) ? array_merge( $_SESSION['oauth']['google'], $auth ): $auth;
		
		
	}
	
	function services(){

		// Reference on what all these services are: 
		// https://code.google.com/oauthplayground/
		return 	array(
				"adsense" 					=> "https://www.googleapis.com/auth/adsense", 
				"gan" 						=> "https://www.googleapis.com/auth/gan", 
				"analytics" 				=> "https://www.googleapis.com/auth/analytics.readonly", 
				"books" 					=> "https://www.googleapis.com/auth/books", 
				"blogger" 					=> "https://www.googleapis.com/auth/blogger", 
				"calendar" 					=> "https://www.googleapis.com/auth/calendar", 
				"storage" 					=> "https://www.googleapis.com/auth/devstorage.read_write", 
				"contacts" 					=> "https://www.google.com/m8/feeds/", 
				"structuredcontent" 		=> "https://www.googleapis.com/auth/structuredcontent", 
				"chromewebstore" 			=> "https://www.googleapis.com/auth/chromewebstore.readonly", 
				"docs" 						=> "https://docs.google.com/feeds/", 
				"gmail" 					=> "https://mail.google.com/mail/feed/atom", 
				"plus" 						=> "https://www.googleapis.com/auth/plus.me", 
				"groups" 					=> "https://apps-apis.google.com/a/feeds/groups/", 
				"latitude" 					=> "https://www.googleapis.com/auth/latitude.all.best", 
				"moderator" 				=> "https://www.googleapis.com/auth/moderator", 
				"nicknames.provisioning" 	=> "https://apps-apis.google.com/a/feeds/alias/", 
				"orkut" 					=> "https://www.googleapis.com/auth/orkut", 
				"picasaweb" 				=> "https://picasaweb.google.com/data/", 
				"sites" 					=> "https://sites.google.com/feeds/", 
				"spreadsheets" 				=> "https://spreadsheets.google.com/feeds/", 
				"tasks" 					=> "https://www.googleapis.com/auth/tasks", 
				"urlshortener" 				=> "https://www.googleapis.com/auth/urlshortener", 
				"userinfo.email" 			=> "https://www.googleapis.com/auth/userinfo.email", 
				"userinfo.profile" 			=> "https://www.googleapis.com/auth/userinfo.profile", 
				"user.provisioning" 		=> "https://apps-apis.google.com/a/feeds/user/", 
				"webmasters.tools" 			=> "https://www.google.com/webmasters/tools/feeds/", 
				"youtube" 					=> "https://gdata.youtube.com"
			);

	}

}