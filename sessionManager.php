<?php
  require_once('oauth.php');

  class SessionManager {
    public static function checkResponseAndRefreshToken($response, $redirectUri) {
      if ($response['errorNumber'] && $response['errorNumber'] == 401) {
        error_log("Request returned 401, attempting to refresh token.");
        // Use the refresh token to get a new token and update session variables.
        $newTokenInfo = oAuthService::getTokenFromRefreshToken($_SESSION['refresh_token'], $redirectUri);

        if ($newTokenInfo['access_token']) {
          $_SESSION['access_token'] = $newTokenInfo['access_token'];
          $_SESSION['refresh_token'] = $newTokenInfo['refresh_token'];

          error_log("Retrieved new token and updated session variables.");
          return true;
        }

        error_log("No access token returned.");
        return false;
      }
      else {
        return false;
      }
    }
  }
?>
