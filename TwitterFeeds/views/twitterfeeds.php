<?php if (!defined('APPLICATION')) exit();

echo Wrap($this->Data('Title'), 'h1');

echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="Info">
   <?php echo T('Define here the global settings for the Twitter Feeds Plugin.'); ?>
</div>
<div>
	<ul><li><?php
	echo $this->Form->Label(T('Number of Tweets to show (max. 200)'), 'Plugins.TwitterFeeds.MaxTweets');
	echo $this->Form->TextBox('Plugins.TwitterFeeds.MaxTweets', array('placeholder' => '5'));
	?></li>
	<li style="border-bottom:1px solid;"><?php
	echo $this->Form->CheckBox('Plugins.TwitterFeeds.ShowSummaryPanel', T('Show Tweet Summary Panel on all Pages?'));
	?></li>
	<li><?php
	echo $this->Form->CheckBox('Plugins.TwitterFeeds.LinkifyUsernames', T('Link Twitter-Users in Tweets?'));
	?></li>
	<li><?php
	echo $this->Form->CheckBox('Plugins.TwitterFeeds.HovercardsEnabled', T('Show Details when hovering a Twittername?'));
	?></li>
	<li><?php
	echo $this->Form->CheckBox('Plugins.TwitterFeeds.FollowbuttonEnabled', T('Show a Follow-Button in the Twitter Feeds-Box?'));
	?></li>
	<li><?php
	echo $this->Form->CheckBox('Plugins.TwitterFeeds.ExcludeReplies', T('Exclude Tweets that are Replies?'));
	?></li>
	<li><?php
	echo $this->Form->CheckBox('Plugins.TwitterFeeds.IncludeRetweets', T('Include classic Retweets?'));
	?></li>
	<li><?php
	echo $this->Form->Close('Save');
	?></li></ul>
</div>