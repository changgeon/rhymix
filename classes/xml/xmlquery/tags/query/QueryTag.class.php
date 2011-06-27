<?php

class QueryTag {
	var $action;
	var $query_id;
	var $column_type;
	var $query;
	
	//xml tags
	var $columns;
	var $tables;
	var $conditions;
	var $groups;
	var $navigation;
	var $arguments;
	var $preBuff;
	var $buff;
	var $isSubQuery;

	function QueryTag($query, $isSubQuery = false){
		$this->action = $query->attrs->action;
		$this->query_id = $query->attrs->id;
		$this->query = $query;
		$this->isSubQuery = $isSubQuery;

		$this->getColumns();
		$tables = $this->getTables();
		$this->setTableColumnTypes($tables);
		$this->getConditions();		
		$this->getGroups();
		$this->getNavigation();
		$this->getPrebuff();
		$this->getBuff();
	}

	function getQueryId(){
		return $this->query->attrs->query_id ? $this->query->attrs->query_id : $this->query->attrs->id;
	}
	
	function getAction(){
		return $this->query->attrs->action;
	}
	
	function setTableColumnTypes($tables){
		$query_id = $this->getQueryId();
		if(!isset($this->column_type[$query_id])){
			$table_tags = $tables->getTables();
			$column_type = array();
			foreach($table_tags as $table_tag){
				$tag_column_type = QueryParser::getTableInfo($query_id, $table_tag->getTableName());
				$column_type = array_merge($column_type, $tag_column_type);
			}
			$this->column_type[$query_id] = $column_type;
		}
	}
	
	function getColumns(){
		if($this->action == 'select'){			
			return $this->columns =  new SelectColumnsTag($this->query->columns->column);
		}else if($this->action == 'insert'){
			return $this->columns =  new InsertColumnsTag($this->query->columns->column);
		}else if($this->action == 'update') {			
			return $this->columns =  new UpdateColumnsTag($this->query->columns->column);
		}else if($this->action == 'delete') {			
			return $this->columns =  null;
		}
	}
	
	function getPrebuff(){
		// TODO Check if this work with arguments in join clause
		$arguments = array();
		if($this->columns)
			$arguments = array_merge($arguments, $this->columns->getArguments());
		$arguments = array_merge($arguments, $this->conditions->getArguments());
		$arguments = array_merge($arguments, $this->navigation->getArguments());
		
		$prebuff = '';
		foreach($arguments as $argument){
			if(isset($argument) && $argument->getArgumentName()){
			$prebuff .= $argument->toString();
			$prebuff .= sprintf("$%s_argument->setColumnType('%s');\n"
				, $argument->getArgumentName()
				, $this->column_type[$this->getQueryId()][$argument->getColumnName()] );
			}
		}
		$prebuff .= "\n";
		
		return $this->preBuff = $prebuff;
	}
	
	function getBuff(){
		$buff = '';
		if($this->isSubQuery) $buff .= '$query = new Query();'.PHP_EOL;
		else $buff .= '$query = new Query();'.PHP_EOL;
		$buff .= sprintf('$query->setQueryId("%s");%s', $this->query_id, "\n");
		$buff .= sprintf('$query->setAction("%s");%s', $this->action, "\n");
		$buff .= $this->preBuff;
		if($this->columns)
			$buff .= '$query->setColumns(' . $this->columns->toString() . ');'.PHP_EOL;
			
        $buff .= '$query->setTables(' . $this->tables->toString() .');'.PHP_EOL;
        $buff .= '$query->setConditions('.$this->conditions->toString() .');'.PHP_EOL;
       	$buff .= '$query->setGroups(' . $this->groups->toString() . ');'.PHP_EOL; 	
       	$buff .= '$query->setOrder(' . $this->navigation->getOrderByString() .');'.PHP_EOL;
		$buff .= '$query->setLimit(' . $this->navigation->getLimitString() .');'.PHP_EOL;
		
		return $this->buff = $buff;
	}
	
	function getTables(){
		return $this->tables = new TablesTag($this->query->tables->table);
	}
	
	function getConditions(){
		return $this->conditions = new ConditionsTag($this->query->conditions);
	}
	
	function getGroups(){
		return $this->groups = new GroupsTag($this->query->groups->group);
	}
	
	function getNavigation(){
		return $this->navigation = new NavigationTag($this->query->navigation);
	}
	
	function toString(){
		return $this->buff;
	}
	
	function getTableString(){
		return $this->buff;
	}
	
	function getConditionString(){
		return $this->buff;
	}
	
	function getExpressionString(){
		return $this->buff;
	}
}
?>