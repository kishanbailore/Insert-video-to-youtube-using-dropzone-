<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {

	
	public function __construct() 
	 {
		 
		 session_start();
        parent::__construct();
		//$this->load->library('Google/autoload');
		//$this->load->library('Google/Client');
		//$this->load->library('Google/Youtube');
		$this->client_id = 'CLIENT_ID';
		$this->client_secret = 'CLIENT SECRET';
		$this->redirect_uri = 'http://localhost/youtube_upload/';
		//$this->simple_api_key = 'API_KEY ';
		$this->application_name = 'sampleupload';
	
	 }
	public function index()
	{
		$flag='';
		$res=$this->google_access($flag);
			
		if($res=='1')
		{
			$this->load->view('home');
		}
		else{
			echo $res;
		}
		
	}
		
	public function google_access($flag)
	{
		include_once APPPATH . "third_party/Google/autoload.php";
		//include_once APPPATH . "libraries/Google/Client.php";
		//include_once APPPATH . "libraries/google-api-php-client-master/src/Google/Service/Oauth2.php";
		//include_once APPPATH . "libraries/Google/Service/Youtube.php";
		$scope = array('https://www.googleapis.com/auth/youtube.upload','https://www.googleapis.com/auth/youtube', 'https://www.googleapis.com/auth/youtubepartner');
        
		$client = new Google_Client();
		$client->setClientId($this->client_id);
		$client->setClientSecret($this->client_secret);
		//$client->setScopes('https://www.googleapis.com/auth/youtube');
		$client->setScopes($scope);
		$client->setRedirectUri($this->redirect_uri);
		$client->setApplicationName($this->application_name);
		$client->setAccessType('offline');
		
		$youtube = new Google_Service_YouTube($client);
		if (isset($_GET['code'])) 
		{
			$client->authenticate($_GET['code']);
			$_SESSION['token'] = $client->getAccessToken();
			
		}
		if($flag=='')
		{
			if (isset($_SESSION['token'])) 
			{
				$client->setAccessToken($_SESSION['token']);
				$client->getAccessToken();
			 
				$_SESSION['token'] = $client->getAccessToken();
				//unset($_SESSION['token']);
				var_dump($_SESSION);
				return 1;
			}
			else
			{
				$authUrl = $client->createAuthUrl();
				$string= '<script src="'. base_url().'"assets/jquery-1.10.2.js"></script><a id="load_page" href="'.$authUrl.'">Connect Me!!</a><script>window.location = $("#load_page"").attr(""href");</script>';
				return $string;
			}
		}
		if($flag)
		{
			if (isset($_SESSION['token'])) 
			{
				$client->setAccessToken($_SESSION['token']);
				$client->getAccessToken();
			 
				$_SESSION['token'] = $client->getAccessToken();
				try
				{
					
						/*if($client->isAccessTokenExpired()) 
						{
							$newToken = json_decode($client->getAccessToken());
							$client->refreshToken($newToken->access_token);
						}*/
						$video_det=explode(',' , $flag);
						// $video_det[0],	$video_det[1]; 
					
						$videoPath = $video_det[0];
						$videoTitle = $video_det[1];
						$videoDescription = "A video tutorial on how to upload to YouTube";
						$videoCategory = "22";
						$videoTags = array("youtube", "tutorial");
					
					
						$youtube = new Google_Service_YouTube($client);
						// Create a snipet with title, description, tags and category id
						$snippet = new Google_Service_YouTube_VideoSnippet();
						$snippet->setTitle($videoTitle);
						$snippet->setDescription($videoDescription);
						$snippet->setCategoryId($videoCategory);
						$snippet->setTags($videoTags);
 
						// Create a video status with privacy status. Options are "public", "private" and "unlisted".
						$status = new Google_Service_YouTube_VideoStatus();
						$status->setPrivacyStatus('private');
 
						// Create a YouTube video with snippet and status
						$video = new Google_Service_YouTube_Video();
						$video->setSnippet($snippet);
						$video->setStatus($status);
 
						// Size of each chunk of data in bytes. Setting it higher leads faster upload (less chunks,
						// for reliable connections). Setting it lower leads better recovery (fine-grained chunks)
						$chunkSizeBytes = 1 * 1024 * 1024;
 
						// Setting the defer flag to true tells the client to return a request which can be called
						// with ->execute(); instead of making the API call immediately.
						$client->setDefer(true);
 
						// Create a request for the API's videos.insert method to create and upload the video.
						$insertRequest = $youtube->videos->insert("status,snippet", $video);
 
						// Create a MediaFileUpload object for resumable uploads.
						$media = new Google_Http_MediaFileUpload($client,$insertRequest,'video/*',null,true,$chunkSizeBytes);
				
						$media->setFileSize(filesize($videoPath));
						//var_dump(filesize($videoPath));
 
						// Read the media file and upload it chunk by chunk.
						$status = false;
						$handle = fopen($videoPath, "rb");
						while (!$status && !feof($handle)) 
						{
							$chunk = fread($handle, $chunkSizeBytes);
							$status = $media->nextChunk($chunk);
						}
 
						fclose($handle);
						// Video has successfully been upload, now lets perform some cleanup functions for this video
						if ($status->status['uploadStatus'] == 'uploaded') 
						{
							
							// Actions to perform for a successful upload
							redirect('home', 'refresh');
						}
						// If you want to make other calls after the file upload, set setDefer back to false
						$client->setDefer(false);
					
					
				}
				catch(Google_Service_Exception $e) 
				{
					print "Caught Google service Exception ".$e->getCode(). " message is ".$e->getMessage();
					print "Stack trace is ".$e->getTraceAsString();
				}
				catch (Exception $e) 
				{
					print "Caught Google service Exception ".$e->getCode(). " message is ".$e->getMessage();
					print "Stack trace is ".$e->getTraceAsString();
				}
				
			}
			else
			{
				$authUrl = $client->createAuthUrl();
				$string= '<script src="'. base_url().'assets/jquery-1.10.2.js"></script><a id="load_page" href="'.$authUrl.'">Connect Me!!</a><script>window.location = $("#load_page"").attr(""href");</script>';
				return $string;
			}
			
			if ($client->getAccessToken()) 
			{
				try
				{
					// Call the channels.list method to retrieve information about the
					// currently authenticated user's channel.
					$channelsResponse = $youtube->channels->listChannels('contentDetails', array('mine' => 'true',));
 
					$htmlBody = '';
					foreach ($channelsResponse['items'] as $channel) 
					{
						// Extract the unique playlist ID that identifies the list of videos
						// uploaded to the channel, and then call the playlistItems.list method
						// to retrieve that list.
						$uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];
 
						$playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array('playlistId' => $uploadsListId,'maxResults' => 50));
 
						$htmlBody .= "<h3>Videos in list $uploadsListId</h3><ul>";
						foreach ($playlistItemsResponse['items'] as $playlistItem) 
						{
							$htmlBody .= sprintf('<li>%s (%s)</li>', $playlistItem['snippet']['title'],
							$playlistItem['snippet']['resourceId']['videoId']);
						}
						$htmlBody .= '</ul>';
					}
				} 
				catch (Google_ServiceException $e) 
				{
					$htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
					htmlspecialchars($e->getMessage()));
				}
				catch (Google_Exception $e) 
				{
					$htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
					htmlspecialchars($e->getMessage()));
				}
				$_SESSION['token'] = $client->getAccessToken();
	
			}
			else 
			{
				$state = mt_rand();
				$client->setState($state);
				$_SESSION['state'] = $state;
 
				$authUrl = $client->createAuthUrl();
				$string= '<script src="'. base_url().'"assets/jquery-1.10.2.js"></script><a id="load_page" href="'.$authUrl.'">Connect Me!!</a><script>window.location = $("#load_page"").attr(""href");</script>';
				return $string;
			}
			
			
		}
			
	}
	public function upload($details='')
	{
		
		if($details)
		{
			echo '<script>alert("video not present");</script>';
		}
		else
		{
			if (!empty($_FILES)) 
			{
				echo '<script>alert("video present");</script>';
				$tempFile = $_FILES['file']['tmp_name'];
				$fileName = $_FILES['file']['name'];
				$flag=$tempFile.','.$fileName;
				$this->google_access($flag);
				
				/*echo'<script src="'. base_url().'assets/jquery-1.10.2.js"></script>';
				echo'<script src="'. base_url().'assets/bootstrap.js"></script>';
				echo '<button type="button" class="btn btn-primary extra_button" ></button>';

				echo '<div id="myModal" class="modal fade" role="dialog">
						<div class="modal-dialog">
							<!-- Modal content-->
							<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal">&times;</button>
								<h4 class="modal-title">Insert video Details</h4>
								</div>
									<div class="modal-body">
									<p>Some text in the modal.</p>
										</div>
									<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
							</div>
						</div>
						</div>';
						echo '<script>$(".extra_button").click(function(){ 
							$("#myModal").modal("show");
						});</script>';
				
				$targetPath = './uploads/' ;
				if (!file_exists($targetPath)) 
				mkdir($targetPath, 0777, true);
				$targetFile = $targetPath . $fileName ;
				move_uploaded_file($tempFile, $targetFile);*/
			
			}
		}
		
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */