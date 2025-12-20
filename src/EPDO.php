<?php
/**
 * @copyright (c) 2021, Claus-Christoph Küthe
 * @author Claus-Christoph Küthe <floss@vm01.telton.de>
 * @license LGPL
 */

/**
 * EPDO
 * 
 * Enhancement for EPDO with functions which will save a lot of time when
 * writing CRUD applications by providing methods to Create, Read, Update and
 * Delete rows.
 * Values/Conditions are to be provided as associative arrays with the array
 * keys corresponding to column names. Please not NOT TO USE KEYS FROM UNTRUSTED
 * SOURCES, e.g. $values[$_GET["column1"]]. Although methods use bound values,
 * column names could be used for SQL injection. 
 */
class EPDO extends PDO {
	/**
	 * create
	 * 
	 * Creates a new entry from an associative array, where key names are
	 * columns. Do not use key names from untrusted sources.
	 * 
	 * @param string $table
	 * @param array $array Associative array containing columns as keys and values. DO NOT USE KEYS FROM UNTRUSTED SOURCES!
	 * @param string $sequence Sequence name where needed (eg. PostgreSQL)
	 * @return string Insert Id (always string in PDO)
	 */
	function create(string $table, array $array, string $sequence = NULL): string {
		$names = array();
		$params = array();
		$bind = array();
		$insert = "INSERT INTO ".$this->quote($table)." ";
		foreach($array as $key => $value) {
			if($value===NULL) {
				continue;
			}
			$names[] = $key;
			$params[] = $value;
			$bind[] = "?";
		}
		$insert .= "(".implode(", ", $names).") VALUES (".implode(", ", $bind).")";
		$stmt = $this->prepare($insert);
		$stmt->execute($params);
	return $this->lastInsertId($sequence);
	}
	
	/**
	 * update
	 * 
	 * Updates a value. Use it for simple updates like changing one value.
	 * Both colums to be changed and the conditions are expected as associative
	 * arrays, where the array keys are columns. Conditions are joined by AND.
	 * 
	 * @param string $table Table name
	 * @param array $values Associative array. DO NOT USE KEY FROM UNTRUSTED SOURCES!
	 * @param array $conditions Associative array. DO NOT USE KEY FROM UNTRUSTED SOURCES!
	 */
	function update(string $table, array $values, array $conditions): void {
		$update = "UPDATE ".$this->quote($table)." SET ";
		$set = array();
		$param = array();
		$where = array();
		foreach($values as $key => $value) {
			$set[] = $key." = ?";
			$param[] = $value;
		}
		$update .= implode(", ", $set)." WHERE ";
		foreach($conditions as $key => $value) {
			if($value===NULL) {
				$where[] = $key." IS NULL";
			continue;
			}
			$where[] = $key." = ?";
			$param[] = $value;
		}
		$update .= implode(" AND ", $where);
		$stmt = $this->prepare($update);
		$stmt->execute($param);
	}
	
	/**
	 * delete
	 * 
	 * Delete values using associative array as condition with column names as
	 * keys. Multiple conditions are linked via AND. Be careful, and no don use
	 * keys from untrusted sources.
	 * @param string $table Table name
	 * @param array $conditions Condition as associative array. DO NOT USE KEYS FROM UNTRUSTED SOURCES!
	 */
	function delete(string $table, array $conditions): void {
		$param = array();
		$where = array();
		$delete = "DELETE FROM ".$this->quote($table)." WHERE ";
		foreach($conditions as $key => $value) {
			if($value===NULL) {
				$where[] = $key." IS NULL";
			continue;
			}
			$where[] = $key." = ?";
			$param[] = $value;
		}
		$delete .= implode(" AND ", $where);
		$stmt = $this->prepare($delete);
		$stmt->execute($param);
	}
	
	/**
	 * row
	 * 
	 * Shorthand to select a single row. The intention for row is to retrieve
	 * one single dataset, so if you're getting more than one result, an 
	 * Exception is thrown, as I'd assume something is wrong with my query or
	 * database design or data. Can be mitigated with LIMIT 1.
	 * 
	 * @param string $sql
	 * @param array $params
	 * @return array
	 * @throws PDOException
	 */
	function row(string $sql, array $params): array {
		$stmt = $this->prepare($sql);
		$stmt->execute($params);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$fetch = $stmt->fetchAll();
		if(count($fetch)===0) {
			return array();
		}
		if(count($fetch)!==1) {
			throw new PDOException("Result is ambiguous (".count($fetch).")");
		}
	return $fetch[0];
	}
	
	/**
	 * result
	 * 
	 * Shorthand to return a single value; throws an exception if receiving more
	 * than one row.
	 * @param string $sql
	 * @param array $params
	 * @return mixed
	 * @throws PDOException
	 */
	function result(string $sql, array $params): mixed {
		$stmt = $this->prepare($sql);
		$stmt->execute($params);
		$stmt->setFetchMode(PDO::FETCH_NUM);
		$fetch = $stmt->fetchAll();
		if(empty($fetch[0])) {
			return NULL;
		}
		if(count($fetch)!==1) {
			throw new PDOException("Result is ambiguous (".count($fetch).")");
		}
	return $fetch[0][0];
	}
}
