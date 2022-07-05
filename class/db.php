<?php
//Name of database, password etc.
include('config/config.php');
class db
{

	//Not by me : https://codeshack.io/super-fast-php-mysql-database-class/
	protected $connection;
	protected $query;
	protected $show_errors = TRUE;
	protected $query_closed = TRUE;
	public $query_count = 0;

	public function __construct($charset = 'utf8')
	{
		$this->connection = new mysqli(DBHOST, DBUSER, DBPWD, DBNAME);
		if ($this->connection->connect_error) {
			$this->error('Failed to connect to MySQL - ' . $this->connection->connect_error);
		}
		$this->connection->set_charset($charset);
	}

	public function query($query)
	{
		if (!$this->query_closed) {
			$this->query->close();
		}
		if ($this->query = $this->connection->prepare($query)) {
			if (func_num_args() > 1) {
				$x = func_get_args();
				$args = array_slice($x, 1);
				$types = '';
				$args_ref = array();
				foreach ($args as $k => &$arg) {
					if (is_array($args[$k])) {
						foreach ($args[$k] as $j => &$a) {
							$types .= $this->_gettype($args[$k][$j]);
							$args_ref[] = &$a;
						}
					} else {
						$types .= $this->_gettype($args[$k]);
						$args_ref[] = &$arg;
					}
				}
				array_unshift($args_ref, $types);
				call_user_func_array(array($this->query, 'bind_param'), $args_ref);
			}
			$this->query->execute();
			if ($this->query->errno) {
				$this->error('Unable to process MySQL query (check your params) - ' . $this->query->error);
			}
			$this->query_closed = FALSE;
			$this->query_count++;
		} else {
			$this->error('Unable to prepare MySQL statement (check your syntax) - ' . $this->connection->error);
		}
		return $this;
	}


	public function fetchAll($callback = null)
	{
		$params = array();
		$row = array();
		$meta = $this->query->result_metadata();
		while ($field = $meta->fetch_field()) {
			$params[] = &$row[$field->name];
		}
		call_user_func_array(array($this->query, 'bind_result'), $params);
		$result = array();
		while ($this->query->fetch()) {
			$r = array();
			foreach ($row as $key => $val) {
				$r[$key] = $val;
			}
			if ($callback != null && is_callable($callback)) {
				$value = call_user_func($callback, $r);
				if ($value == 'break') break;
			} else {
				$result[] = $r;
			}
		}
		$this->query->close();
		$this->query_closed = TRUE;
		return $result;
	}

	public function fetchArray()
	{
		$params = array();
		$row = array();
		$meta = $this->query->result_metadata();
		while ($field = $meta->fetch_field()) {
			$params[] = &$row[$field->name];
		}
		call_user_func_array(array($this->query, 'bind_result'), $params);
		$result = array();
		while ($this->query->fetch()) {
			foreach ($row as $key => $val) {
				$result[$key] = $val;
			}
		}
		$this->query->close();
		$this->query_closed = TRUE;
		return $result;
	}

	public function close()
	{
		return $this->connection->close();
	}

	public function numRows()
	{
		$this->query->store_result();
		return $this->query->num_rows;
	}

	public function affectedRows()
	{
		return $this->query->affected_rows;
	}

	public function lastInsertID()
	{
		return $this->connection->insert_id;
	}

	public function error($error)
	{
		if ($this->show_errors) {
			exit($error);
		}
	}


	private function _gettype($var)
	{
		if (is_string($var)) return 's';
		if (is_float($var)) return 'd';
		if (is_int($var)) return 'i';
		return 'b';
	}


	/*By me 
Function to get all wordinfos from a correlationID
*/
	public function worldInfos($ID)
	{
		$querryWI = "SELECT Distinct * FROM worldinfos where PromptID = ? Order by CorrelationId";
		$ResultWI = $this->query($querryWI, array($ID));
		if ($ResultWI->numRows() == 0)
			return 0;
		else
			return $ResultWI->fetchAll();
	}

	// Function to get the number of wordinfos of a correlationID
	public function NbworldInfos($ID)
	{
		$querryWI = "SELECT Distinct Id FROM worldinfos where PromptID = ?";
		$ResultWI = $this->query($querryWI, array($ID));
		$nb = $ResultWI->numRows();
		return $nb;
	}

	// Function to get all subs of a correlationID
	public function subScenarios($ID)
	{
		$querrySub = "SELECT Distinct * FROM prompts where ParentID =?";
		$ResultSub = $this->query($querrySub, array($ID));
		if ($ResultSub->numRows() == 0)
			return 0;
		else
			return $ResultSub->fetchAll();
	}

	// Function to get editCode from an ID
	public function EditCode($ID)
	{
		$querryEditCode = "SELECT Distinct CodeEdit FROM editcode where PromptID = ?";
		$EditCode = $this->query($querryEditCode, array($ID));
		if ($EditCode->numRows() == 0)
			return "";
		else
			return $EditCode->fetchArray()['CodeEdit'];
	}

	// Function to get the first parent (older one) from a sub
	public function firstParent($ParentId)
	{
		$firstParentID = 0;
		while (!is_null($ParentId)) {
			$querryPrompt =  "SELECT Distinct Id, ParentID FROM prompts where CorrelationID = ?";
			$ParentId = $this->query($querryPrompt, array($ParentId))->fetchArray();
			$firstParentID  = $ParentId['Id'];
			$ParentId = $ParentId['ParentID'];
		}
		return $firstParentID;
	}

	// Function to get the EditCode of the first parent
	public function firstParentEditCode($ParentId)
	{
		$firstParentID = $this->firstParent($ParentId);
		if ($firstParentID == 0)
			return "";
		return $this->EditCode($firstParentID);
	}

	// Function to get the PublishDate of the first parent
	public function firstParentPublishDate($ParentId)
	{
		$firstParentID = $this->firstParent($ParentId);
		if ($firstParentID == 0)
			return "";
		$querryPrompt =  "SELECT Distinct PublishDate FROM prompts where CorrelationID = ?";
		return 	$this->query($querryPrompt, array($ParentId))->fetchArray()['PublishDate'];
	}

	// Function to delete a subScenario and his own subscenarios.
	public function deleteSub($ID, $CID)
	{
		$querrySub =  "SELECT Distinct Id, CorrelationID FROM prompts where ParentId = ?";
		$subs = $this->query($querrySub, array($CID));
		$subsNb = $subs->numRows();
		if ($subsNb > 0) {
			$subs = $subs->fetchAll();
			foreach ($subs as $Sub) {
				$this->deleteSub($Sub['Id'], $Sub['CorrelationID']);
			}
		}
		$querryDelete =  "DELETE FROM prompts WHERE Id = ?";
		$delete = $this->query($querryDelete, array($ID));
		$querryDelete =  "DELETE FROM worldinfos WHERE PromptId = ?";
		$delete = $this->query($querryDelete, array($ID));
	}

	// Function to return a random CorrelationID
	public function promptRandom()
	{
		$querryRandom = "SELECT CorrelationID from prompts where PublishDate is not null order by rand() limit 1";
		return $this->query($querryRandom)->fetchArray()['CorrelationID'];
	}
}
