<?php
/**
 * Basic Twitter API
 * Riley Sun
 * getUserFeed($username) - Get single user's tweets by username ($username STRING)
 * getMultiUserFeed($users) - Get mutliple user's tweets by usernames ($users ARRAY)
 */

class Twitter {
    private $BEARER = 'TWITTER_API_BEARER_TOKEN';
    
    //Get user ID from the API by user name
	private function getUserID($user) {
		$url = 'https://api.twitter.com/2/users/by?usernames='.$user.'&user.fields=profile_image_url';
		if (function_exists('curl_version')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$this->BEARER));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL,$url);
			$result=curl_exec($ch);
			curl_close($ch);
			return $result;
		}
		else {
			error_log("You do not have curl enabled");
		}
	}
	
	//Get all recent tweets from the api by user id
	private function getUsersTweets($userID) {
		$url = 'https://api.twitter.com/2/users/'.$userID.'/tweets?&tweet.fields=created_at&expansions=attachments.media_keys&media.fields=preview_image_url,url';
		if (function_exists('curl_version')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$this->BEARER));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL,$url);
			$result=curl_exec($ch);
			curl_close($ch);
			return $result;
		}
		else {
			error_log("You do not have curl enabled");
		}
	}
	
	//Sort tweet array by date
	private static function sortTweets($a,$b) {
		$A = $a['rawDate'];
		$B = $b['rawDate'];
		return $B - $A;
	}
	
	//Get tweets by username and wrap data as an object for output
	public function getUserFeed($username) {
		$userData = json_decode($this->getUserID($username))->data[0];
		$rawData = json_decode($this->getUsersTweets($userData->id));
		$Tweets = $rawData->data;
		$Images = $rawData->includes->media;
		
		$out = array();
		foreach ($Tweets as &$tweet) {
			$tweetDate = new DateTime($tweet->created_at);
			$date = $tweetDate->format('M jS Y - g:ia');
			
			$formattedTweet = array(
				'name' => $userData->name,
				'user' => $userData->username,
				'img' => $userData->profile_image_url,
				'date' => $date,
				'text' => $tweet->text,
				'rawDate' => $tweetDate->getTimestamp()
			);
			
			foreach($Images as &$image) {
				$imgID = $image->media_key;
				$tweetID = $tweet->attachments->media_keys[0];
				if ($imgID == $tweetID) {
					$formattedTweet['media'] = array(
						'type' => $image->type,
						'url' => $image->url,
						'preview' => $image->preview_image_url
					);
				}
			}
			
			$out[] = $formattedTweet;
		}
		
		usort($out, 'self::sortTweets');	
		
		return $out;
	}
	
	//Get multiple users tweets by an array of usernames
	public function getMultiUserFeed($users) {
		$out = array();
		foreach($users as &$user) {
			$userTweets = $this->getUserFeed($user);
			foreach($userTweets as &$tweet) {
				$out[] = $tweet;
			}
		}
		usort($out, 'self::sortTweets');		
		return $out;
	}
}
