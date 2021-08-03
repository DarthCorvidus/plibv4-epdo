<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AssertTest
 *
 * @author hm
 */
class EPDOTest extends TestCase {
	function setUp() {
		copy(__DIR__."/epdo.sqlite", __DIR__."/testing.sqlite");
	}
	
	function tearDown() {
		unlink(__DIR__."/testing.sqlite");
	}
	
	function getEPDO(): EPDO {
		$pdo = new EPDO("sqlite:".__DIR__."/testing.sqlite", "", "");
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $pdo;
	}

	function getValues(): array {
		$pdo = new EPDO("sqlite:".__DIR__."/testing.sqlite", "", "");
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $pdo->prepare("select * from politician order by id");
		$stmt->setFetchMode(EPDO::FETCH_ASSOC);
		$stmt->execute();
		$results = array();
		foreach($stmt as $value) {
			$results[] = $value;
		}
	return $results;
	}
	
	
	function getExpected() {
		$values[0]["id"] = "1";
		$values[0]["name"] = "Angela";
		$values[0]["surname"] = "Merkel";
		$values[0]["sex"] = "f";
		$values[0]["birthday"] = "1954-07-17";

		$values[1]["id"] = "2";
		$values[1]["name"] = "Emmanuel Jean-Michael Frédéric";
		$values[1]["surname"] = "Macron";
		$values[1]["sex"] = "f";
		$values[1]["birthday"] = "1977-12-21";

		$values[2]["id"] = "3";
		$values[2]["name"] = "Alexander Boris";
		$values[2]["surname"] = "de Pfeffel Johnson";
		$values[2]["sex"] = "m";
		$values[2]["birthday"] = "1964-06-19";

		$values[3]["id"] = "4";
		$values[3]["name"] = "Joseph Robinette";
		$values[3]["surname"] = "Biden";
		$values[3]["sex"] = "m";
		$values[3]["birthday"] = "1942-11-20";

	return $values;
	}
	
	function testSelect() {
		$this->assertEquals($this->getExpected(), $this->getValues());
	}
	
	function testInsert() {
		$insert["name"] = "Vladimir Vladimirovich";
		$insert["surname"] = "Putin";
		$insert["sex"] = 'm';
		$insert["birthday"] = '1952-10-07';
		$pdo = $this->getEPDO();
		$insertId = $pdo->create("politician", $insert);
		$insert["id"] = 5;
		$expected = $this->getExpected();
		$expected[] = $insert;
		$this->assertEquals($expected, $this->getValues());
		$this->assertEquals(5, $insertId);
	}
	
	function testUpdate() {
		$expected = $this->getExpected();
		$expected[1]['sex'] = 'm';
		$this->getEPDO()->update("politician", array('sex' => 'm'), array('id' => 2, 'sex' => 'f'));
		$values = $this->getValues();
		$this->assertEquals($expected, $values);
	}
	
	function testUpdateValueNULL() {
		$expected = $this->getExpected();
		$expected[1]['sex'] = NULL;
		$this->getEPDO()->update("politician", array('sex' => NULL), array('id' => 2, 'sex' => 'f'));
		$values = $this->getValues();
		$this->assertEquals($expected, $values);
		$this->assertEquals(true, ($expected[1]["sex"]===NULL));
	}

	function testUpdateValueEmpty() {
		$expected = $this->getExpected();
		$expected[1]['sex'] = "";
		$this->getEPDO()->update("politician", array('sex' => ""), array('id' => 2, 'sex' => 'f'));
		$values = $this->getValues();
		$this->assertEquals($expected, $values);
		$this->assertEquals(true, ($expected[1]["sex"]===""));
	}
	
	function testUpdateConditionNULL() {
		$insert["name"] = "Vladimir Vladimirovich";
		$insert["surname"] = "Putin";
		$insert["sex"] = 'm';
		
		$pdo = $this->getEPDO();
		$pdo->create("politician", $insert);
		
		$insert["id"] = "5";
		$insert["birthday"] = NULL;
		
		$expected = $this->getExpected();
		$expected[] = $insert;
		
		$this->assertEquals($expected, $this->getValues());
		
		$this->getEPDO()->update("politician", array('birthday' => "1952-10-07"), array('id' => 5, 'birthday' => NULL));
		$expected[4]["birthday"] = "1952-10-07";
		
		$this->assertEquals($expected, $this->getValues());
	}
	
	function testUpdateConditionEmpty() {
		$insert["name"] = "Vladimir Vladimirovich";
		$insert["surname"] = "Putin";
		$insert["sex"] = 'm';
		$insert["birthday"] = "";
		
		$pdo = $this->getEPDO();
		$pdo->create("politician", $insert);
		$insert["id"] = "5";
		$insert["birthday"] = "";
		
		$expected = $this->getExpected();
		$expected[] = $insert;
		
		$this->assertEquals($expected, $this->getValues());
		
		$this->getEPDO()->update("politician", array('birthday' => "1952-10-07"), array('id' => 5, 'birthday' => ""));
		$expected[4]["birthday"] = "1952-10-07";
		
		$this->assertEquals($expected, $this->getValues());
	}
	
	function testDelete() {
		$expected = $this->getExpected();
		unset($expected[3]);
		$this->getEPDO()->delete("politician", array("id"=>4));
		$this->assertEquals($expected, $this->getValues());
	}
	
	function testRow() {
		$expected = $this->getExpected();
		$read = $this->getEPDO()->row("select * from politician where id = ?", array(1));
		$this->assertEquals($expected[0], $read);
	}
	
	function testRowAmbiguous() {
		$expected = $this->getExpected();
		$this->expectException(PDOException::class);
		$this->expectExceptionMessage("Result is ambiguous (2)");
		$read = $this->getEPDO()->row("select * from politician where sex = ?", array("m"));
	}

	function testRowEmpty() {
		$read = $this->getEPDO()->row("select * from politician where id = ?", array(10));
		$this->assertEquals(array(), $read);
	}

	function testResult() {
		$result = $this->getEPDO()->result("select count(*) from politician where sex = ?", array('f'));
		$this->assertEquals(2, $result);
	}
	
	function testResultNoValue() {
		$result = $this->getEPDO()->result("select name from politician where id = ?", array('15'));
		$this->assertEquals(0, $result);
	}

	function testResultAmbiguous() {
		$this->expectException(PDOException::class);
		$this->expectExceptionMessage("Result is ambiguous (2)");
		$result = $this->getEPDO()->result("select name from politician where sex = ?", array('f'));
	}
}
