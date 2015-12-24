<?php


/**
 * @package plugins.facebookDistribution
 * @subpackage model
 */
class FacebookDistributionProfile extends ConfigurableDistributionProfile
{
	const CUSTOM_DATA_PAGE_ID = 'pageId';
	const CUSTOM_DATA_PAGE_ACCESS_TOKEN = 'pageAccessToken';
	const CUSTOM_DATA_USER_ACCESS_TOKEN = 'userAccessToken';
	const CUSTOM_DATA_FACEBOOK_PERMISSIONS = 'facebookPermissions';
	const CUSTOM_DATA_RE_REQUEST_PERMISSIONS = 'reRequestPermissions';
	const CUSTOM_DATA_CALL_TO_ACTION_TYPE = 'callToActionType';
	const CUSTOM_DATA_CALL_TO_ACTION_LINK = 'callToActionLink';
	const CUSTOM_DATA_CALL_TO_ACTION_LINK_CAPTION = 'callToActionLinkCaption';
	const CUSTOM_DATA_PLACE= 'place';
	const CUSTOM_DATA_TAGS= 'tags';
	const CUSTOM_DATA_TARGETING= 'targeting';
	const CUSTOM_DATA_FEED_TARGETING= 'feedTargeting';
	// this list is the available one when uploading a video to a page
	const CALL_TO_ACTION_TYPE_VALID_VALUES = 'SHOP_NOW,BOOK_TRAVEL,LEARN_MORE,SIGN_UP,DOWNLOAD,WATCH_MORE';
	const DEFAULT_RE_REQUEST_PERMISSIONS = 'false';
	// needed permission in order to be able to publish the video to a facebook page
	const DEFAULT_FACEBOOK_PERMISSIONS = 'manage_pages,publish_actions,user_videos,publish_pages,user_actions.video';



	/* (non-PHPdoc)
	 * @see DistributionProfile::getProvider()
	 */
	public function getProvider()
	{
		return FacebookDistributionPlugin::getProvider();
	}
			
	/* (non-PHPdoc)
	 * @see DistributionProfile::validateForSubmission()
	 */
	public function validateForSubmission(EntryDistribution $entryDistribution, $action)
	{
		$validationErrors = parent::validateForSubmission($entryDistribution, $action);

		$inListOrNullFields = array (
		    FacebookDistributionField::CALL_TO_ACTION_TYPE_VALID_VALUES => explode(',', self::CALL_TO_ACTION_TYPE_VALID_VALUES),
		);

		if(count($entryDistribution->getFlavorAssetIds()))
			$flavorAssets = assetPeer::retrieveByIds(explode(',', $entryDistribution->getFlavorAssetIds()));
		else
			$flavorAssets = assetPeer::retrieveReadyFlavorsByEntryId($entryDistribution->getEntryId());

		$validVideo = false;
		foreach ($flavorAssets as $flavorAsset)
		{
			$validVideo = $this->validateVideo($flavorAsset);
			if($validVideo) {
				// even one valid video is enough
				break;
			}
		}

		if(!$validVideo)
		{
			KalturaLog::err("No valid video found for entry [" . $entryDistribution->getEntryId() . "]");
			$validationErrors[] = $this->createCustomValidationError($action, DistributionErrorType::INVALID_DATA, 'flavorAsset', ' No valid flavor found');
		}
		
		$allFieldValues = $this->getAllFieldValues($entryDistribution);
		if (!$allFieldValues || !is_array($allFieldValues)) {
		    KalturaLog::err('Error getting field values from entry distribution id ['.$entryDistribution->getId().'] profile id ['.$this->getId().']');
		    return $validationErrors;
		}
		if ($allFieldValues[FacebookDistributionField::SCHEDULE_PUBLISHING_TIME] &&
			$allFieldValues[FacebookDistributionField::SCHEDULE_PUBLISHING_TIME] > time() &&
			!dateUtils::isWithinTimeFrame($allFieldValues[FacebookDistributionField::SCHEDULE_PUBLISHING_TIME],
				FacebookConstants::FACEBOOK_MIN_POSTPONE_POST_IN_SECONDS,
				FacebookConstants::FACEBOOK_MAX_POSTPONE_POST_IN_SECONDS))
		{
			KalturaLog::err("Scheduled time to publish defies the facebook restriction of six minute to six months from now got".$allFieldValues[FacebookDistributionField::SCHEDULE_PUBLISHING_TIME]);
			$validationErrors[] = $this->createCustomValidationError($action, DistributionErrorType::INVALID_DATA, 'sunrise', 'Distribution sunrise is invalid (should be 6 minutes to 6 months from now)');
		}
	    $validationErrors = array_merge($validationErrors, $this->validateInListOrNull($inListOrNullFields, $allFieldValues, $action));
		return $validationErrors;
	}

	public function getPageId()				    	{return $this->getFromCustomData(self::CUSTOM_DATA_PAGE_ID);}
	public function getPageAccessToken()	    	{return $this->getFromCustomData(self::CUSTOM_DATA_PAGE_ACCESS_TOKEN);}
	public function getUserAccessToken()	    	{return $this->getFromCustomData(self::CUSTOM_DATA_USER_ACCESS_TOKEN);}
	public function getFacebookPermissions()		{return $this->getFromCustomData(self::CUSTOM_DATA_FACEBOOK_PERMISSIONS, null, self::DEFAULT_FACEBOOK_PERMISSIONS);}
	public function getReRequestPermissions()		{return $this->getFromCustomData(self::CUSTOM_DATA_RE_REQUEST_PERMISSIONS, null , self::DEFAULT_RE_REQUEST_PERMISSIONS);}
	public function getCallToActionType()	    	{return $this->getFromCustomData(self::CUSTOM_DATA_CALL_TO_ACTION_TYPE);}
	public function getCallToActionLink()	    	{return $this->getFromCustomData(self::CUSTOM_DATA_CALL_TO_ACTION_LINK);}
	public function getCallToActionLinkCaption()	{return $this->getFromCustomData(self::CUSTOM_DATA_CALL_TO_ACTION_LINK_CAPTION);}
	public function getPlace()				   		{return $this->getFromCustomData(self::CUSTOM_DATA_PLACE);}
	public function getTags()				   		{return $this->getFromCustomData(self::CUSTOM_DATA_TAGS);}
	public function getTargeting()					{return $this->getFromCustomData(self::CUSTOM_DATA_TARGETING);}
	public function getFeedTargeting()				{return $this->getFromCustomData(self::CUSTOM_DATA_FEED_TARGETING);}

	public function setPageId($v)			    	{$this->putInCustomData(self::CUSTOM_DATA_PAGE_ID, $v);}
	public function setPageAccessToken($v)	    	{$this->putInCustomData(self::CUSTOM_DATA_PAGE_ACCESS_TOKEN, $v);}
	public function setUserAccessToken($v)	    	{$this->putInCustomData(self::CUSTOM_DATA_USER_ACCESS_TOKEN, $v);}
	public function setFacebookPermissions($v)		{$this->putInCustomData(self::CUSTOM_DATA_FACEBOOK_PERMISSIONS, $v);}
	public function setReRequestPermissions($v)		{$this->putInCustomData(self::CUSTOM_DATA_RE_REQUEST_PERMISSIONS, $v);}
	public function setCallToActionType($v)	    	{$this->putInCustomData(self::CUSTOM_DATA_CALL_TO_ACTION_TYPE, $v);}
	public function setCallToActionLink($v)			{$this->putInCustomData(self::CUSTOM_DATA_CALL_TO_ACTION_LINK, $v);}
	public function setCallToActionLinkCaption($v)	{$this->putInCustomData(self::CUSTOM_DATA_CALL_TO_ACTION_LINK_CAPTION, $v);}
	public function setPlace($v)					{$this->putInCustomData(self::CUSTOM_DATA_PLACE, $v);}
	public function setTags($v)				    	{$this->putInCustomData(self::CUSTOM_DATA_TAGS, $v);}
	public function setTargeting($v)				{$this->putInCustomData(self::CUSTOM_DATA_TARGETING, $v);}
	public function setFeedTargeting($v)			{$this->putInCustomData(self::CUSTOM_DATA_FEED_TARGETING, $v);}


	protected function getDefaultFieldConfigArray()
	{	    
	    $fieldConfigArray = parent::getDefaultFieldConfigArray();
	    
	    $fieldConfig = new DistributionFieldConfig();

		$fieldConfig = new DistributionFieldConfig();
		$fieldConfig->setFieldName(FacebookDistributionField::TITLE);
		$fieldConfig->setUserFriendlyFieldName('Video title');
		$fieldConfig->setEntryMrssXslt('<xsl:value-of select="string(title)" />');
		$fieldConfig->setUpdateOnChange(true);
		$fieldConfig->setUpdateParams(array(entryPeer::NAME));
		$fieldConfig->setIsRequired(DistributionFieldRequiredStatus::REQUIRED_BY_PROVIDER);
		$fieldConfigArray[$fieldConfig->getFieldName()] = $fieldConfig;

		$fieldConfig = new DistributionFieldConfig();
		$fieldConfig->setFieldName(FacebookDistributionField::DESCRIPTION);
		$fieldConfig->setUserFriendlyFieldName('Video description');
		$fieldConfig->setEntryMrssXslt('<xsl:value-of select="string(description)" />');
		$fieldConfig->setUpdateOnChange(true);
		$fieldConfig->setUpdateParams(array(entryPeer::DESCRIPTION));
		$fieldConfig->setIsRequired(DistributionFieldRequiredStatus::REQUIRED_BY_PROVIDER);
		$fieldConfigArray[$fieldConfig->getFieldName()] = $fieldConfig;

	    $fieldConfig = new DistributionFieldConfig();
	    $fieldConfig->setFieldName(FacebookDistributionField::SCHEDULE_PUBLISHING_TIME);
	    $fieldConfig->setUserFriendlyFieldName('Schedule Sunrise Time');
	    $fieldConfig->setEntryMrssXslt('<xsl:value-of select="distribution[@entryDistributionId=$entryDistributionId]/sunrise" />');
	    $fieldConfigArray[$fieldConfig->getFieldName()] = $fieldConfig;	   

	    $fieldConfig = new DistributionFieldConfig();
	    $fieldConfig->setFieldName(FacebookDistributionField::CALL_TO_ACTION_TYPE);
	    $fieldConfig->setUserFriendlyFieldName('Call To Action Type');
	    $fieldConfig->setEntryMrssXslt('<xsl:value-of select="customData/metadata/CallToActionType" />');
	    $fieldConfigArray[$fieldConfig->getFieldName()] = $fieldConfig;
	    
	   	$fieldConfig = new DistributionFieldConfig();
	    $fieldConfig->setFieldName(FacebookDistributionField::CALL_TO_ACTION_LINK);
	    $fieldConfig->setUserFriendlyFieldName('Call To Action Link');
	    $fieldConfig->setEntryMrssXslt('<xsl:value-of select="customData/metadata/CallToActionLink" />');
	    $fieldConfigArray[$fieldConfig->getFieldName()] = $fieldConfig;

		$fieldConfig = new DistributionFieldConfig();
	    $fieldConfig->setFieldName(FacebookDistributionField::CALL_TO_ACTION_LINK_CAPTION);
	    $fieldConfig->setUserFriendlyFieldName('Call To Action Link Caption');
	    $fieldConfig->setEntryMrssXslt('<xsl:value-of select="customData/metadata/CallToActionLinkCaption" />');
	    $fieldConfigArray[$fieldConfig->getFieldName()] = $fieldConfig;

//	   	$fieldConfig = new DistributionFieldConfig();
//	    $fieldConfig->setFieldName(FacebookDistributionField::PLACE);
//	    $fieldConfig->setUserFriendlyFieldName('ID of location to tag in video');
//	    $fieldConfig->setEntryMrssXslt('<xsl:value-of select="customData/metadata/Place" />');
//	    $fieldConfigArray[] = $fieldConfig;
//
//	    $fieldConfig = new DistributionFieldConfig();
//	    $fieldConfig->setFieldName(FacebookDistributionField::TAGS);
//	    $fieldConfig->setUserFriendlyFieldName('IDs (comma separated) of persons to tag in video');
//	    $fieldConfig->setEntryMrssXslt('<xsl:value-of select="customData/metadata/Tags" />');
//	    $fieldConfigArray[$fieldConfig->getFieldName()] = $fieldConfig;
//
//	   	$fieldConfig = new DistributionFieldConfig();
//	    $fieldConfig->setFieldName(FacebookDistributionField::TARGETING);
//	    $fieldConfig->setUserFriendlyFieldName('Key IDs for ad targeting objects used to limit the audience of the video');
//	    $fieldConfig->setEntryMrssXslt('<xsl:value-of select="distribution[@entryDistributionId=$entryDistributionId]/Targeting" />');
//	    $fieldConfigArray[$fieldConfig->getFieldName()] = $fieldConfig;
//
//	   	$fieldConfig = new DistributionFieldConfig();
//	    $fieldConfig->setFieldName(FacebookDistributionField::FEED_TARGETING);
//	    $fieldConfig->setUserFriendlyFieldName('Key IDs for ad targeting objects used to promote the video in specific audience feeds');
//	    $fieldConfig->setEntryMrssXslt('<xsl:value-of select="distribution[@entryDistributionId=$entryDistributionId]/FeedTargeting" />');
//	    $fieldConfigArray[$fieldConfig->getFieldName()] = $fieldConfig;
	      
	    return $fieldConfigArray;
	}

	public function getApiAuthorizeUrl()
	{
		$permissions = $this->getFacebookPermissions();
		$url = kConf::get('apphome_url');
		$url .= "/index.php/extservices/facebookoauth2".
            "/".FacebookRequestParameters::FACEBOOK_PROVIDER_ID_REQUEST_PARAM."/".base64_encode($this->getId()).
            "/".FacebookRequestParameters::FACEBOOK_PAGE_ID_REQUEST_PARAM."/".base64_encode($this->getPageId()).
            "/".FacebookRequestParameters::FACEBOOK_PERMISSIONS_REQUEST_PARAM."/".base64_encode($permissions).
            "/".FacebookRequestParameters::FACEBOOK_RE_REQUEST_PERMISSIONS_REQUEST_PARAM."/".base64_encode($this->getReRequestPermissions())
        ;

		return $url;
	}
	
	private function validateVideo(flavorAsset $flavorAsset)
	{
		$syncKey = $flavorAsset->getSyncKey(flavorAsset::FILE_SYNC_FLAVOR_ASSET_SUB_TYPE_ASSET);
		if(kFileSyncUtils::fileSync_exists($syncKey))
		{
			$videoAssetFilePath = kFileSyncUtils::getLocalFilePathForKey($syncKey, false);
			$mediaInfo = mediaInfoPeer::retrieveByFlavorAssetId($flavorAsset->getId());
			if(!$mediaInfo)
				return false;
			try
			{
				FacebookGraphSdkUtils::validateVideoAttributes($videoAssetFilePath, $mediaInfo->getFileSize(), $mediaInfo->getVideoDuration(), $mediaInfo->getVideoWidth(), $mediaInfo->getVideoHeight());
				return true;
			}
			catch(Exception $e)
			{
				KalturaLog::debug('Asset ['.$flavorAsset->getId().'] not valid for distribution: '.$e->getMessage());
			}			
		}				
		return false;
	}

}