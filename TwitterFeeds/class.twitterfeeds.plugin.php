<?php if (!defined('APPLICATION')) exit();

/**
 * Define the plugin
 */
$PluginInfo['TwitterFeeds'] = array(
	'Name'			=> 'Twitter Feeds',
	'Description'	=> 'Allow Users to add a Twitter Stream to their profile page.',
	'Version'		=> '1.6.1',
	'Author'		=> 'Oliver Raduner',
	'AuthorEmail'	=> 'vanilla@raduner.ch',
	'AuthorUrl'		=> 'http://raduner.ch/',
	'RequiredApplications' => array('Vanilla' => '2.0.17'),
	'RequiredTheme'	=> FALSE,
	'RequiredPlugins' => FALSE,
	'HasLocale'		=> FALSE,
	'RegisterPermissions' => FALSE,
	'RegisterPermissions' => FALSE,
	'SettingsUrl'	=> '/dashboard/plugin/twitterfeeds',
	'SettingsPermission' => 'Garden.Settings.Manage',
	'MobileFriendly' => FALSE,
);


/**
 * Twitter Feeds Plugin
 *
 * Allows Users to add their Twitter Feed to their Profile.
 *
 * @version 1.6
 * @author Oliver Raduner <vanilla@raduner.ch>
 *
 * @todo Gdn_Format::Links() breaks the Tweet output containing links in the Feed summary panel
 * @todo There is a glitch with linkifyed Twitternames on the Twitter Feed summary panel
 * @todo Too long Links must be forced to a new line
 */
class TwitterFeedsPlugin extends Gdn_Plugin
{	
	/**
	 * Adds a new Panel with the User's last Tweets to the Profile Page
	 * 
	 * @version 1.2
	 * @since 1.0
	 * @author Oliver Raduner <vanilla@raduner.ch>
	 *
	 * @todo (low prio) Make use of http://twitter.com/javascripts/blogger.js ??
	 */
	public function ProfileController_AddProfileTabs_Handler($Sender)
	{
		// Get the selected User's Twitter Name
		$TwitterName = $Sender->User->TwitterName;
		
		if (!empty($TwitterName))
		{	
			// Initialize HTML Content for the new Side Panel
			$HtmlOut = '';
			
			// Initialize Twitter @Anywhere
			$HtmlOut .= $this->TwitterAnywhereHeaders();
			
			// Tweets Output
			$Sender->AddCssFile('plugins/TwitterFeeds/twitterfeeds.css');
			
			$HtmlOut .= $this->GetTweets($TwitterName);
			
			// Add the new Panel
			$Sender->AddAsset('Panel', $HtmlOut, 'TwitterFeeds');
			
		} else {
			return FALSE;
		}
	}
	
	
	/**
	 * Add a textfield to the Profile, so the User can save his Twitter Name
	 * 
	 * @version 1.1
	 * @since 1.0
	 * @author Oliver Raduner <vanilla@raduner.ch>
	 */
	public function ProfileController_EditMyAccountAfter_Handler($Sender)
	{
		echo '<li>';
		echo $Sender->Form->Label(T('Twitter Name'), 'TwitterName');
		echo $Sender->Form->TextBox('TwitterName', array('maxlength' => 15));
		echo '</li>';	
	}
	
	
	/**
	 * Add an Activity when a User adds/changes a Twitter name
	 * 
	 * @version 1.0
	 * @since 1.5
	 * @author Oliver Raduner <vanilla@raduner.ch>
	 */
	/*public function ProfileController_Edit_After_Handler()
	{
	// Create activity entry
        $Sender->ActivityModel->Add(
			$ActivityUserID,
			$ActivityType,
			$Comment,
			$RegardingUserID,
			'',
			'/profile/'.$Sender->ProfileUrl(),
			FALSE);
	}*/
	
	
	/**
	 * Hack the basic rendering in order to add a Twitter Feed panel
	 * 
	 * @version 1.1
	 * @since 1.2
	 * @author Oliver Raduner <vanilla@raduner.ch>
	 */
	public function Base_Render_Before($Sender)
	{
		if (C('Plugins.TwitterFeeds.ShowSummaryPanel') == TRUE)
		{
			// Continue the Plugin output only on the desired pages...
			$DisplayOn =  array('activitycontroller', 'discussionscontroller'); // Pages where the Feed should be displayed on
			if (!InArrayI($Sender->ControllerName, $DisplayOn)) return;
			
			// Initialize HTML Content for the new Side Panel
			$HtmlOut = '';
			
			// Initialize Twitter @Anywhere
			$HtmlOut .= $this->TwitterAnywhereHeaders();
			
			// Tweets Output
			$Sender->AddCssFile('plugins/TwitterFeeds/twitterfeeds.css');
			
			$HtmlOut .= $this->GetTweets();
			
			// Add the new Panel
			$Sender->AddAsset('Panel', $HtmlOut, 'TwitterFeeds');
		}
	}
	
	
	/**
	 * Add a Settings menu to the Admin Dashboard
	 *
	 * @version 1.1
	 * @since 1.2
	 * @author Oliver Raduner <vanilla@raduner.ch>
	 */
	public function Base_GetAppSettingsMenuItems_Handler($Sender) {
		$Menu = $Sender->EventArguments['SideMenu'];
		$Menu->AddLink('Site Settings', 'Twitter Feeds', 'plugin/twitterfeeds', 'Garden.Settings.Manage');
	}
	
	
	/**
	 * Plugin Settings Page
	 *
	 * @version 1.1
	 * @since 1.2
	 * @author Oliver Raduner <vanilla@raduner.ch>
	 */
	public function PluginController_TwitterFeeds_Create($Sender, $Args = array())
	{
		$Sender->Permission('Garden.Settings.Manage');
		$Sender->Form = new Gdn_Form();
		$Validation = new Gdn_Validation();
		$ConfigurationModel = new Gdn_ConfigurationModel($Validation);
		$ConfigurationModel->SetField(array('Plugins.TwitterFeeds.TwitterApiKey', 'Plugins.TwitterFeeds.MaxTweets', 'Plugins.TwitterFeeds.LinkifyUsernames', 'Plugins.TwitterFeeds.HovercardsEnabled', 'Plugins.TwitterFeeds.FollowbuttonEnabled', 'Plugins.TwitterFeeds.ExcludeReplies', 'Plugins.TwitterFeeds.IncludeRetweets'));
		$Sender->Form->SetModel($ConfigurationModel);
		
		if ($Sender->Form->AuthenticatedPostBack() === FALSE)
		{
			$Sender->Form->SetData($ConfigurationModel->Data);
		} else {
			$Data = $Sender->Form->FormValues();
			
			if ($Sender->Form->Save() !== FALSE) {
				$Sender->StatusMessage = T("Your settings have been saved.");
			}
		}

		$Sender->AddSideMenu('plugin/twitterfeeds');		
		$Sender->SetData('Title', 'Twitter Feeds');
		$Sender->Render($this->GetView('twitterfeeds.php'));
	}
	
	
	/**
	 * Initialize Twitter @Anywhere
	 * 
	 * @version 1.0
	 * @since 1.2
	 * @author Oliver Raduner <vanilla@raduner.ch>
	 * @link https://dev.twitter.com/docs/anywhere/
	 */
	private function TwitterAnywhereHeaders()
	{
		// Get Settings
		$ApiKey				= C('Plugins.TwitterFeeds.TwitterApiKey');
		$LinkifyUsernames	= C('Plugins.TwitterFeeds.LinkifyUsernames');
		$UseHovercards		= C('Plugins.TwitterFeeds.HovercardsEnabled');
		
		
		// Build JavaScript
		$HeaderHtml  = '';
		$HeaderHtml .= '<script src="http://platform.twitter.com/anywhere.js?id='.$ApiKey.'" type="text/javascript"></script>';
		$HeaderHtml .= '<script type="text/javascript">twttr.anywhere(function (T) {';
		$HeaderHtml .= ($LinkifyUsernames == TRUE) ? 'T(".Tweet").linkifyUsers();' : '';
		$HeaderHtml .= ($UseHovercards == TRUE) ? 'T(".Tweet").hovercards();' : '';
		$HeaderHtml .= '});</script>';
		
		return $HeaderHtml;
	}	
	
	
	/**
	 * Build the Twitter Feed
	 * 
	 * @version 1.0
	 * @since 1.2
	 * @author Oliver Raduner <vanilla@raduner.ch>
	 * @link https://dev.twitter.com/docs/api/1/get/statuses/user_timeline
	 */
	private function GetTweets($TwitterName='')
	{
		// Get Settings
		$NumberOfTweets		= C('Plugins.TwitterFeeds.MaxTweets');
		$AddFollowButton	= C('Plugins.TwitterFeeds.FollowbuttonEnabled');
		$ExcludeReplies		= C('Plugins.TwitterFeeds.ExcludeReplies');
		$IncludeRetweets	= C('Plugins.TwitterFeeds.IncludeRetweets');
		
		// Initialize Variables
		$TweetsHtmlOut = '';
		
		// Build the Panel header
		$TweetsHtmlOut .= '<div id="TwitterFeeds" class="Box">';
		$TweetsHtmlOut .= '<img id="TwitterIcon" />';
		
		if ($TwitterName <> '')
		{	
		
		/**
		 * For a single User
		 */
			$TwitterApiCallJson  = '';
			$TwitterApiCallJson .= 'http://api.twitter.com/1.1/statuses/user_timeline.json?';
			$TwitterApiCallJson .= 'screen_name='.$TwitterName;
			$TwitterApiCallJson	.= '&count='.$NumberOfTweets.'&exclude_replies='.$ExcludeReplies.'&include_rts='.$IncludeRetweets.'&trim_user=true';
			
			$Tweets = json_decode(file_get_contents($TwitterApiCallJson), TRUE);
			
			// Add Panel header information
			$TweetsHtmlOut .= '<h4><a href="http://twitter.com/'.$TwitterName.'" title="'.$TwitterName.T(' on Twitter').'">'.$TwitterName.'</a></h4>';
			$TweetsHtmlOut .= '<ul class="PanelInfo">';
			
			foreach ($Tweets as $Tweet)
			{
				// Make sure that we really have something to output...
				if (!empty($Tweet['text']))
				{
					$CreatedAt = strtotime($Tweet['created_at']);
					
					$TweetsHtmlOut .= '<li class="Tweet" style="text-align:left;">';
					$TweetsHtmlOut .= Gdn_Format::Links($Tweet['text']);
					$TweetsHtmlOut .= ' | <a href="http://twitter.com/'.$TwitterName.'/statuses/'.$Tweet['id'].'">'.Gdn_Format::Date($CreatedAt).'</a>';
					$TweetsHtmlOut .= '</li>';
				} else {
					$TweetsHtmlOut .= '<font color="red">'.T('No Tweets found! Please check your Twittername.').'</font>';
				}
			}
			$TweetsHtmlOut .= '</ul>';
			
			// If enabled, add also a Follow Button to the Page
			if ($AddFollowButton == TRUE)
			{
				$TweetsHtmlOut .= '<span id="follow-'.$TwitterName.'"></span>';
				$TweetsHtmlOut .= '<script type="text/javascript">';
				$TweetsHtmlOut .= 'twttr.anywhere(function (T) {';
				$TweetsHtmlOut .= 'T("#follow-'.$TwitterName.'").followButton("'.$TwitterName.'");';
				$TweetsHtmlOut .= '});</script>';
			}
			
		} else {
		
		/**
		 * When no User was specified
		 */
			$TwitterApiCallJson  = array();
			$TwitterApiCallsJson = '';
			$TwitternamesArray = array();
			$Tweets = '';
			
			// Add Panel header information
			$TweetsHtmlOut .= '<h4>Twitter Feeds</h4>';
			$TweetsHtmlOut .= '<ul class="PanelInfo" style="text-align:left;">';
			
			$SqlSelect = Gdn::SQL();
			$SqlTwitternames = $SqlSelect->Select('TwitterName')
		       ->From('User')
		       ->Where('TwitterName <>', '')
		       ->Get();
			while($Twitternames = $SqlTwitternames->NextRow(DATASET_TYPE_ARRAY))
			{
				// Put all Twitter Names into an Array
				$TwitternamesArray[] = $Twitternames['TwitterName'];
			}
			
			// Cycle through all Twitter Names to output their Tweets
			foreach ($TwitternamesArray as $TwitterName)
			{	
				// Build & Retrieve the JSON API-Call
				//$TwitterApiCallsJson  = 'http://api.twitter.com/1.1/statuses/user_timeline.json?screen_name='.$TwitterName.'&count=1&exclude_replies='.$ExcludeReplies.'&include_rts='.$IncludeRetweets.'&trim_user=true';
				$TwitterApiCallsJson  = 'http://api.twitter.com/1.1/statuses/user_timeline.json?screen_name='.$TwitterName.'&count=1&exclude_replies=true&include_rts=false&trim_user=true';
				$Tweets = json_decode(file_get_contents($TwitterApiCallsJson), TRUE);
				
				// Cycle through all Tweets for output
				foreach ($Tweets as $Tweet)
				{
					// Error Output from Twitter @Anywhere
					if (!empty($Tweet['error']))
					{
						$TweetsHtmlOut .= '<font color="red">'.$Tweet['error'].'</font>';
					}
					
					// Make sure that we really have something to output...
					if (!empty($Tweet['text']))
					{
						$CreatedAt = strtotime($Tweet['created_at']);
						
						$TweetsHtmlOut .= '<li class="Tweet" style="text-align:left;">';
						$TweetsHtmlOut .= '<a href="http://twitter.com/'.$TwitterName.'/statuses/'.$Tweet['id'].'">'.$TwitterName.' '.T('on').' '.Gdn_Format::Date($CreatedAt).':</a><br />';
						//$TweetsHtmlOut .= Gdn_Format::Links($Tweet['text']);
						$TweetsHtmlOut .= $Tweet['text'];
						$TweetsHtmlOut .= '</li>';
					} else {
						$TweetsHtmlOut .= '<font color="red">'.T('Tweet not found! Please check your Twittername.').'</font>';
					}
				}
			}
			$TweetsHtmlOut .= '</ul>';
			$TweetsHtmlOut .= '</div>';
		}
		
		return $TweetsHtmlOut;
	}
	
	
	/**
	 * Initialize required data
	 *
	 * @version 1.1
	 * @since 1.0
	 * 
	 * @todo Use UserMeta db-table to store information
	 */
	public function Setup()
	{
		$Structure = Gdn::Structure();
		
		// Create the database table & columns for the Plugin
		$Structure->Table('User')
	        ->Column('TwitterName', 'varchar(15)', TRUE)
	        ->Set(FALSE, FALSE);
		
		// Add Config Items
		SaveToConfig('Plugins.TwitterFeeds.TwitterApiKey', '0YeyyhFafvSMoGTam5OjZQ');
		SaveToConfig('Plugins.TwitterFeeds.MaxTweets', '6');
		SaveToConfig('Plugins.TwitterFeeds.ShowSummaryPanel', 'TRUE');
		SaveToConfig('Plugins.TwitterFeeds.LinkifyUsernames', 'TRUE');
		SaveToConfig('Plugins.TwitterFeeds.HovercardsEnabled', 'TRUE');
		SaveToConfig('Plugins.TwitterFeeds.FollowbuttonEnabled', 'FALSE');
		SaveToConfig('Plugins.TwitterFeeds.ExcludeReplies', 'FALSE');
		SaveToConfig('Plugins.TwitterFeeds.IncludeRetweets', 'FALSE');
	}
	
	
	/**
	 * On Plugin deactivation, remove the Plugin's Settings from the Config file
	 *
	 * @version 1.0
	 * @since 1.2
	 */
	public function OnDisable()
	{
		RemoveFromConfig('Plugins.TwitterFeeds.TwitterApiKey');
		RemoveFromConfig('Plugins.TwitterFeeds.MaxTweets');
		RemoveFromConfig('Plugins.TwitterFeeds.ShowSummaryPanel');
		RemoveFromConfig('Plugins.TwitterFeeds.LinkifyUsernames');
		RemoveFromConfig('Plugins.TwitterFeeds.HovercardsEnabled');
		RemoveFromConfig('Plugins.TwitterFeeds.FollowbuttonEnabled');
		RemoveFromConfig('Plugins.TwitterFeeds.ExcludeReplies');
		RemoveFromConfig('Plugins.TwitterFeeds.IncludeRetweets');
	}
			
}
