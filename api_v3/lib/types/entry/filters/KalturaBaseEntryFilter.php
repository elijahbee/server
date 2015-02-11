<?php
/**
 * @package api
 * @subpackage filters
 */
class KalturaBaseEntryFilter extends KalturaBaseEntryBaseFilter
{
	static private $map_between_objects = array
	(
		"freeText" => "_free_text",
		"isRoot" => "_is_root",
		"categoriesFullNameIn" => "_in_categories_full_name", 
		"categoryAncestorIdIn" => "_in_category_ancestor_id",
		"redirectFromEntryId" => "_eq_redirect_from_entry_id",
		"entitledUsersEditMatchAnd" => "_matchand_entitled_kusers_edit",
		"entitledUsersPublishMatchAnd" => "_matchand_entitled_kusers_publish",
	);
	
	static private $order_by_map = array
	(
		"recent" => "recent", // needed for backward compatibility
	);

	public function getMapBetweenObjects()
	{
		return array_merge(parent::getMapBetweenObjects(), self::$map_between_objects);
	}

	public function getOrderByMap()
	{
		return array_merge(parent::getOrderByMap(), self::$order_by_map);
	}

	/**
	 * @var string
	 */
	public $freeText;

	/**
	 * @var KalturaNullableBoolean
	 */
	public $isRoot;
	
	/**
	 * @var string
	 */
	public $categoriesFullNameIn;
	
	/**
	 * All entries within this categoy or in child categories  
	 * @var string
	 */
	public $categoryAncestorIdIn;

	/**
	 * The id of the original entry
	 * @var string
	 */
	public $redirectFromEntryId;

	/* (non-PHPdoc)
	 * @see KalturaFilter::getCoreFilter()
	 */
	protected function getCoreFilter()
	{
		return new entryFilter();
	}
}
