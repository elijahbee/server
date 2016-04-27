<?php
class kCategoryEventHandler implements kObjectDeletedEventConsumer, kObjectCreatedEventConsumer, kObjectChangedEventConsumer
{
	/* (non-PHPdoc)
	 * @see kObjectChangedEventConsumer::objectChanged()
	 */
	public function objectChanged(BaseObject $object, array $modifiedColumns) {
		if ($object instanceof categoryEntry)
		{
			$this->handleCategoryEntryCreated($object);
		}
		
		if ($object instanceof category)
		{
			
		}
		
		return true;
	}

	/* (non-PHPdoc)
	 * @see kObjectChangedEventConsumer::shouldConsumeChangedEvent()
	 */
	public function shouldConsumeChangedEvent(BaseObject $object, array $modifiedColumns) {
		if ($object instanceof categoryEntry && in_array(categoryEntryPeer::STATUS, $modifiedColumns)
			&& $object->getStatus() == CategoryEntryStatus::ACTIVE
			&& $object->getColumnsOldValue(categoryEntryPeer::STATUS) == CategoryEntryStatus::PENDING)
		{
			return true;
		}
		
		if ($object instanceof category)
		{
			$oldCustomDataValues = $object->getCustomDataOldValues();
			if ($oldCustomDataValues[category::AGGREGATION_CATEGORIES] != $object->getAggregationCategories())
			{
				return true;
			}
		}
		
		return false;
	}

	/* (non-PHPdoc)
	 * @see kObjectCreatedEventConsumer::objectCreated()
	 */
	public function objectCreated(BaseObject $object) {
		if ($object instanceof categoryEntry)
		{
			$this->handleCategoryEntryCreated($object);
		}
		
		if ($object instanceof category)
		{
			$this->handleCategoryCreated ($object);
		}
		
		return true;
		
	}
	
	protected function handleCategoryCreated (category $object)
	{
		//Start Job
	}
	
	protected function handleCategoryEntryCreated (categoryEntry $object)
	{
			$category = categoryPeer::retrieveByPK($object->getCategoryId());
			if (!$category)
			{
				KalturaLog::info("category [" . $object->getCategoryId() . "] does not exist in the system.");
				return true;
			}
			
			if (!$category->getAggregationCategories())
			{
				KalturaLog::info("No aggregation categories found for category [" . $category->getId() . "]");
				return true;
			}
			
			$aggregationCategories = explode(',', $category->getAggregationCategories());
			$aggregationCategoryEntries = categoryEntryPeer::retrieveActiveByEntryIdAndCategoryIds($object->getEntryId(), $aggregationCategories); 
			
			foreach ($aggregationCategoryEntries as $aggregationCategoryEntry)
			{
				$aggregationCategories = array_diff($aggregationCategories, array($aggregationCategoryEntry->getCategoryId()));
			}
			
			foreach ($aggregationCategories as $categoryIdToAdd)
			{
				$aggregationCategory = categoryPeer::retrieveByPK($categoryIdToAdd);
				if (!$aggregationCategory)
					continue;
				
				$categoryEntry = $object->copy();
				$categoryEntry->setCategoryId($categoryIdToAdd);
				$categoryEntry->setCategoryFullIds($aggregationCategory->getFullIds());
				$categoryEntry->save();
			}
	}

	/* (non-PHPdoc)
	 * @see kObjectCreatedEventConsumer::shouldConsumeCreatedEvent()
	 */
	public function shouldConsumeCreatedEvent(BaseObject $object) {
		if ($object instanceof categoryEntry && $object->getStatus() == CategoryEntryStatus::ACTIVE)
		{
			return true;
		}
		
		if ($object instanceof category && $object->getAggregationCategories())
		{
			return true;
		}
		
		return false;
	}

	/* (non-PHPdoc)
	 * @see kObjectDeletedEventConsumer::objectDeleted()
	 */
	public function objectDeleted(BaseObject $object, BatchJob $raisedJob = null) {
		if ($object instanceof categoryEntry)
		{
			$this->handleCategoryEntryDeleted ($object);
		}
		
		return true;
	}
	
	protected function handleCategoryEntryDeleted (categoryEntry $object)
	{
		$category = categoryPeer::retrieveByPK($object->getCategoryId());
		if (!$category)
		{
			KalturaLog::info("category [" . $object->getCategoryId() . "] does not exist in the system.");
			return true;
		}
		
		if (!$category->getAggregationCategories())
		{
			KalturaLog::info("No aggregation categories found for category [" . $category->getId() . "]");
			return true;
		}
		
		$aggregationCategories = explode (',', $category->getAggregationCategories());
		
		//List all entry's ACTIVE categoryEntry objects
		$activeCategoryEntries = categoryEntryPeer::retrieveActiveByEntryId($object->getEntryId());
		$activeCategoryIds = array();
		foreach ($activeCategoryEntries as $activeCategoryEntry)
		{
			/* @var $activeCategoryEntry categoryEntry */
			$activeCategoryIds[] = $activeCategoryEntry->getCategoryId();
		}
		
		$activeCategories = categoryPeer::retrieveByPKs($activeCategoryIds);
		foreach ($activeCategories as $activeCat)
		{
			/* @var $activeCat category */
			$activeCatAggregationCats = explode(',', $activeCat->getAggregationCategories());
			$aggregationCategories = array_diff($aggregationCategories, $activeCatAggregationCats);
			
			if (!count ($aggregationCategories))
			{
				KalturaLog::info("No need to delete any aggregation category associations.");
				return true;
			}
		}
		
		if (count ($aggregationCategories))
		{
			$aggregationCategoryEntries = categoryEntryPeer::retrieveActiveByEntryIdAndCategoryIds($object->getEntryId(), $aggregationCategories);
			foreach ($aggregationCategoryEntries as $aggregationCategoryEntry)
			{
				/* @var $aggregationCategoryEntry categoryEntry */
				KalturaLog::info("Delete aggregation category entry- entry ID [" . $aggregationCategoryEntry->getEntryId() . "], category ID [" . $aggregationCategoryEntry->getCategoryId() . "]");
				$aggregationCategoryEntry->setStatus(CategoryEntryStatus::DELETED);
				$aggregationCategoryEntry->save();
			}
		}
	}

	/* (non-PHPdoc)
	 * @see kObjectDeletedEventConsumer::shouldConsumeDeletedEvent()
	 */
	public function shouldConsumeDeletedEvent(BaseObject $object) {
		if ($object instanceof categoryEntry && $object->getStatus() == CategoryEntryStatus::DELETED)
		{
			return true;
		}
		
		if ($object instanceof category && $object->getStatus() == CategoryStatus::DELETED)
		{
			return true;
		}
		
		return false;
	}

	
}