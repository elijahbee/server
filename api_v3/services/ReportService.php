<?php
/**
 * api for getting reports data by the report type and some inputFilter
 * @service report
 * @package api
 * @subpackage services
 */
class ReportService extends KalturaBaseService
{
	public function initService($serviceId, $serviceName, $actionName)
	{
		parent::initService($serviceId, $serviceName, $actionName);
		
		if (in_array(strtolower($actionName), array('execute', 'getcsv'), true))
		{
			$this->applyPartnerFilterForClass('Report');
		}
	}
	
	/* (non-PHPdoc)
	 * @see KalturaBaseService::partnerGroup()
	 */
	protected function partnerGroup($peer = null)
	{
		if (in_array(strtolower($this->actionName), array('execute', 'getcsv'), true))
			return $this->partnerGroup . ',0';
			
		return $this->partnerGroup;
	}
		
	/**
	 * Validates that all object ids are allowed partner ids
	 * 
	 * @param string $objectIds comma seperated ids
	 * @return string comma seperated ids
	 */
	protected function validateObjectsAreAllowedPartners($objectIds = null)
	{
		if(!$objectIds)
			return $this->getPartnerId();
			
		$c = new Criteria();
		$c->addSelectColumn(PartnerPeer::ID);
		$subCriterion1 = $c->getNewCriterion(PartnerPeer::PARTNER_PARENT_ID, $this->getPartnerId());
		$subCriterion2 = $c->getNewCriterion(PartnerPeer::ID, $this->getPartnerId());
		$subCriterion1->addOr($subCriterion2);
		$c->add($subCriterion1);
		$c->add(PartnerPeer::ID, explode(',', $objectIds), Criteria::IN);
		
		$stmt = PartnerPeer::doSelectStmt($c);
		$partnerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
		if (!$partnerIds)
			return Partner::PARTNER_THAT_DOWS_NOT_EXIST;

		return implode(',', $partnerIds); 
	}
		
	/**
	 * report getGraphs action allows to get a graph data for a specific report. 
	 * 
	 * @action getGraphs
	 * @param KalturaReportType $reportType  
	 * @param KalturaReportInputFilter $reportInputFilter
	 * @param string $dimension
	 * @param string $objectIds - one ID or more (separated by ',') of specific objects to query
	 * @return KalturaReportGraphArray 
	 */
	public function getGraphsAction( $reportType , KalturaReportInputFilter $reportInputFilter , $dimension = null , $objectIds = null  )
	{
		if($reportType == KalturaReportType::PARTNER_USAGE || $reportType == KalturaReportType::VAR_USAGE)
			$objectIds = $this->validateObjectsAreAllowedPartners($objectIds);
	
		$reportGraphs =  KalturaReportGraphArray::fromReportDataArray(kKavaReportsMgr::getGraph(
		    $this->getPartnerId(),
		    $reportType,
		    $reportInputFilter->toReportsInputFilter(),
		    $dimension,
		    $objectIds));

		return $reportGraphs;
	}

	/**
	 * report getTotal action allows to get a graph data for a specific report. 
	 * 
	 * @action getTotal
	 * @param KalturaReportType $reportType  
	 * @param KalturaReportInputFilter $reportInputFilter
	 * @param string $objectIds - one ID or more (separated by ',') of specific objects to query
	 * @return KalturaReportTotal 
	 */
	public function getTotalAction( $reportType , KalturaReportInputFilter $reportInputFilter , $objectIds = null )
	{
		if($reportType == KalturaReportType::PARTNER_USAGE || $reportType == KalturaReportType::VAR_USAGE)
			$objectIds = $this->validateObjectsAreAllowedPartners($objectIds);
		
		$reportTotal = new KalturaReportTotal();
		
		list ( $header , $data ) = kKavaReportsMgr::getTotal(
		    $this->getPartnerId() ,
		    $reportType ,
		    $reportInputFilter->toReportsInputFilter() , $objectIds);
		
		$reportTotal->fromReportTotal ( $header , $data );
			
		return $reportTotal;
	}
	
	/**
	 * report getBaseTotal action allows to get a the total base for storage reports  
	 * 
	 * @action getBaseTotal
	 * @param KalturaReportType $reportType  
	 * @param KalturaReportInputFilter $reportInputFilter
	 * @param string $objectIds - one ID or more (separated by ',') of specific objects to query
	 * @return KalturaReportBaseTotalArray 
	 */
	public function getBaseTotalAction( $reportType , KalturaReportInputFilter $reportInputFilter , $objectIds = null )
	{
		$reportSubTotals =  KalturaReportBaseTotalArray::fromReportDataArray(  
			kKavaReportsMgr::getBaseTotal( 
				$this->getPartnerId() , 
				$reportType , 
				$reportInputFilter->toReportsInputFilter() ,
				$objectIds));

		return $reportSubTotals;
	}
	
	/**
	 * report getTable action allows to get a graph data for a specific report. 
	 * 
	 * @action getTable
	 * @param KalturaReportType $reportType  
	 * @param KalturaReportInputFilter $reportInputFilter
	 * @param KalturaFilterPager $pager
	 * @param KalturaReportType $reportType 
	 * @param string $order
	 * @param string $objectIds - one ID or more (separated by ',') of specific objects to query
	 * @return KalturaReportTable 
	 */
	public function getTableAction($reportType, KalturaReportInputFilter $reportInputFilter, KalturaFilterPager $pager, $order = null, $objectIds = null)
	{
		if($reportType == KalturaReportType::PARTNER_USAGE || $reportType == KalturaReportType::VAR_USAGE)
			$objectIds = $this->validateObjectsAreAllowedPartners($objectIds);
		
		$reportTable = new KalturaReportTable();

		// Temporary hack to allow admin console to request a report for any partner
		//	can remove once moving to Kava
		$partnerId = $this->getPartnerId();
		if ($partnerId == Partner::ADMIN_CONSOLE_PARTNER_ID && $objectIds && ctype_digit($objectIds))
		{
			$partnerReports = array(
				KalturaReportType::VAR_USAGE,
				KalturaReportType::VPAAS_USAGE,
				KalturaReportType::ENTRY_USAGE,
				KalturaReportType::PARTNER_USAGE,
			);

			if (in_array($reportType, $partnerReports))
			{
				$partnerId = $objectIds;
			}
		}
		
		list ( $header , $data , $totalCount ) = kKavaReportsMgr::getTable(
		    $partnerId ,
		    $reportType ,
		    $reportInputFilter->toReportsInputFilter() ,
		    $pager->pageSize , $pager->pageIndex ,
		    $order ,  $objectIds);

		$reportTable->fromReportTable ( $header , $data , $totalCount );
			
		return $reportTable;
	}	
	
	/**
	 * 
	 * will create a Csv file for the given report and return the URL to access it
	 * @action getUrlForReportAsCsv
	 * 
	 * @param string $reportTitle The title of the report to display at top of CSV 
	 * @param string $reportText The text of the filter of the report
	 * @param string $headers The headers of the columns - a map between the enumerations on the server side and the their display text  
	 * @param KalturaReportType $reportType  
	 * @param KalturaReportInputFilter $reportInputFilter
	 * @param string $dimension	  
	 * @param KalturaFilterPager $pager
	 * @param KalturaReportType $reportType 
	 * @param string $order
	 * @param string $objectIds - one ID or more (separated by ',') of specific objects to query
	 * @return string 
	 */
	public function getUrlForReportAsCsvAction ( $reportTitle , $reportText , $headers , $reportType , KalturaReportInputFilter $reportInputFilter , 
		$dimension = null , 
		KalturaFilterPager $pager = null , 
		$order = null , $objectIds = null )
	{
		ini_set( "memory_limit","512M" );
		
		if(!$pager)
			$pager = new KalturaFilterPager();
		
		if($reportType == KalturaReportType::PARTNER_USAGE || $reportType == KalturaReportType::VAR_USAGE)
			$objectIds = $this->validateObjectsAreAllowedPartners($objectIds);
		
		try {
			$report = kKavaReportsMgr::getUrlForReportAsCsv(
				$this->getPartnerId(),
				$reportTitle,
				$reportText,
				$headers,
				$reportType,
				$reportInputFilter->toReportsInputFilter(),
				$dimension,
				$objectIds,
				$pager->pageSize,
				$pager->pageIndex,
				$order);
		}
		catch(Exception $e){
			$code = $e->getCode();
			if ($code == kCoreException::SEARCH_TOO_GENERAL)
					throw new KalturaAPIException(KalturaErrors::SEARCH_TOO_GENERAL);
		}

		if ((infraRequestUtils::getProtocol() == infraRequestUtils::PROTOCOL_HTTPS))
			$report = str_replace("http://","https://",$report);

		return $report;
	}
	
	/**
	 *
	 * Will serve a requested report
	 * @action serve
	 * 
	 * @param string $id - the requested id
	 * @return string
	 * @ksOptional 
	 */
	public function serveAction($id) {
		
		// KS verification - we accept either admin session or download privilege of the file 
		$ks = $this->getKs();
		if(!$ks || !($ks->isAdmin() || $ks->verifyPrivileges(ks::PRIVILEGE_DOWNLOAD, $id)))
			KExternalErrors::dieError(KExternalErrors::ACCESS_CONTROL_RESTRICTED);
		
		if(!preg_match('/^[\w-_]*$/', $id))
			throw new KalturaAPIException(KalturaErrors::REPORT_NOT_FOUND, $id);
		
		$partner_id = $this->getPartnerId();
		$folderPath = "/content/reports/$partner_id";
		$fullPath = myContentStorage::getFSContentRootPath() . $folderPath;
		$file_path = "$fullPath/$id";
		
		return $this->dumpFile($file_path, 'text/csv');
	}
	
	/**
	 * @action execute
	 * @param int $id
	 * @param KalturaKeyValueArray $params
	 * @return KalturaReportResponse
	 */
	public function executeAction($id, KalturaKeyValueArray $params = null)
	{
		$dbReport = ReportPeer::retrieveByPK($id);
		if (is_null($dbReport))
			throw new KalturaAPIException(KalturaErrors::REPORT_NOT_FOUND, $id);
			
		$this->addPartnerIdToParams($params);
		
		$execParams = KalturaReportHelper::getValidateExecutionParameters($dbReport, $params);
		
		$kReportsManager = new kReportManager($dbReport);
		list($columns, $rows) = $kReportsManager->execute($execParams);
		
		$reportResponse = KalturaReportResponse::fromColumnsAndRows($columns, $rows);
		
		return $reportResponse;
	}
	
	/**
	 * @action getCsv
	 * @param int $id
	 * @param KalturaKeyValueArray $params
	 * @return file
	 */
	public function getCsvAction($id, KalturaKeyValueArray $params = null)
	{
		$this->addPartnerIdToParams($params);
		
		ini_set( "memory_limit","512M" );
		
		if (kKavaBase::isPartnerAllowed($this->getPartnerId(), kKavaBase::VOD_DISABLED_PARTNERS))
		{
			$customReports = kConf::getMap('custom_reports');
			if (!isset($customReports[$id]))
				throw new KalturaAPIException(KalturaErrors::REPORT_NOT_FOUND, $id);
			
			list($columns, $rows) = kKavaReportsMgr::customReport($id, $params->toObjectsArray());
		}
		else 
		{
			$dbReport = ReportPeer::retrieveByPK($id);
			if (is_null($dbReport))
				throw new KalturaAPIException(KalturaErrors::REPORT_NOT_FOUND, $id);
				
			$execParams = KalturaReportHelper::getValidateExecutionParameters($dbReport, $params);
			
			$kReportsManager = new kReportManager($dbReport);
			list($columns, $rows) = $kReportsManager->execute($execParams);
		}
		
		$fileName = array('Report', $id, $this->getPartnerId());
		foreach($params as $param)
		{
			$fileName[] = $param->key;
			$fileName[] = $param->value;
		}
		$fileName = implode('_', $fileName) . '.csv';
		header('Content-Type: text/csv');
		header("Content-Disposition: attachment; filename=\"$fileName\"");
		echo "\xEF\xBB\xBF"; // a fix for excel, copied from myReportsMgr
		echo implode(',', $columns) . "\n";
		foreach($rows as $row) 
		{
			echo implode(',', $row) . "\n";
		}
		die;
	}
	
	/**
	 * Returns report CSV file executed by string params with the following convention: param1=value1;param2=value2 
	 * 
	 * @action getCsvFromStringParams
	 * @param int $id
	 * @param string $params
	 * @return file
	 */
	public function getCsvFromStringParamsAction($id, $params = null)
	{
		$paramsArray = $this->parseParamsStr($params);
		return $this->getCsvAction($id, $paramsArray);
	}
	
	protected function parseParamsStr($paramsStr)
	{
		$paramsStrArray = explode(';', $paramsStr);
		$paramsKeyValueArray = new KalturaKeyValueArray();
		foreach($paramsStrArray as $paramStr)
		{
			$paramStr = trim($paramStr);
			$paramArray = explode('=', $paramStr);
			$paramKeyValue = new KalturaKeyValue();
			$paramKeyValue->key = isset($paramArray[0]) ? $paramArray[0] : null;
			$paramKeyValue->value = isset($paramArray[1]) ? $paramArray[1] : null;
			$paramsKeyValueArray[] = $paramKeyValue;
		}
		return $paramsKeyValueArray;
	}
	
	protected function addPartnerIdToParams($params)
	{
		// remove partner id parameter
		foreach($params as $param)
		{
			if (strtolower($param->key) == 'partner_id')
			{
				$param->key = '';
				$param->value = '';
			}
		}
		// force partner id parameter
		$partnerIdParam = new KalturaKeyValue();
		$partnerIdParam->key = 'partner_id';
		$partnerIdParam->value = $this->getPartnerId();
		$params[] = $partnerIdParam;
	}
}
