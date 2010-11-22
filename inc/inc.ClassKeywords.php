<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

function getKeywordCategory($id) {
	return LetoDMS_KeywordCategory::getKeywordCategory($id);
}

function getKeywordCategoryByName($name, $owner) {
	return LetoDMS_KeywordCategory::getKeywordCategoryByName($name, $owner);
}

function getAllKeywordCategories($userID = -1) {
	return LetoDMS_KeywordCategory::getAllKeywordCategories($userID = -1);
}

function getAllUserKeywordCategories($userID) {
	return LetoDMS_KeywordCategory::getAllUserKeywordCategories($userID);
}

function addKeywordCategory($owner, $name) {
	return LetoDMS_KeywordCategory::addKeywordCategory($owner, $name);
}

//----------------------------------------------------------------------------------------------
class LetoDMS_KeywordCategory
{
	var $_id;
	var $_ownerID;
	var $_name;

	function LetoDMS_KeywordCategory($id, $ownerID, $name)
	{
		$this->_id = $id;
		$this->_name = $name;
		$this->_ownerID = $ownerID;
	}

	function getKeywordCategory($id) {
		GLOBAL $db;
		
		if (!is_numeric($id))
			die ("invalid id");
		
		$queryStr = "SELECT * FROM tblKeywordCategories WHERE id = " . $id;
		$resArr = $db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || (count($resArr) != 1))
			return false;
		
		$resArr = $resArr[0];
		return new LetoDMS_Keywordcategory($resArr["id"], $resArr["owner"], $resArr["name"]);
	}

	function getKeywordCategoryByName($name, $owner) {
		GLOBAL $db;
		
		$queryStr = "SELECT * FROM tblKeywordCategories WHERE name = '" . $name . "' AND owner = '" . $owner. "'";
		$resArr = $db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || (count($resArr) != 1))
			return false;

		$resArr = $resArr[0];
		return new LetoDMS_Keywordcategory($resArr["id"], $resArr["owner"], $resArr["name"]);
	}

	function getAllKeywordCategories($userIDs = array()) {
		GLOBAL $db, $settings;
		
		$queryStr = "SELECT * FROM tblKeywordCategories";
		if ($userIDs)
			$queryStr .= " WHERE owner in (".implode(',', $userIDs).")";
		
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;
		
		$categories = array();
		foreach ($resArr as $row)
			array_push($categories, new LetoDMS_KeywordCategory($row["id"], $row["owner"], $row["name"]));
		
		return $categories;
	}

	function getAllUserKeywordCategories($userID) {
		GLOBAL $db, $settings;
		
		$queryStr = "SELECT * FROM tblKeywordCategories";
		if ($userID != -1)
			$queryStr .= " WHERE owner = " . $userID;
		
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;
		
		$categories = array();
		foreach ($resArr as $row)
			array_push($categories, new LetoDMS_KeywordCategory($row["id"], $row["owner"], $row["name"]));
		
		return $categories;
	}

	function addKeywordCategory($owner, $name) {
		global $db;

		if (is_object(self::getKeywordCategoryByName($name, owner))) {
			return false;
		}
		$queryStr = "INSERT INTO tblKeywordCategories (owner, name) VALUES ($owner, '$name')";
		if (!$db->getResult($queryStr))
			return false;
		
		return self::getKeywordCategory($db->getInsertID());
	}

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function getOwner() {
		if (!isset($this->_owner))
			$this->_owner = getUser($this->_ownerID);
		return $this->_owner;
	}

	function setName($newName)
	{
		GLOBAL $db;
		
		$queryStr = "UPDATE tblKeywordCategories SET name = '$newName' WHERE id = ". $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		$this->_name = $newName;
		return true;
	}

	function setOwner($user) {
		GLOBAL $db;
		
		$queryStr = "UPDATE tblKeywordCategories SET owner = " . $user->getID() . " WHERE id " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		$this->_ownerID = $user->getID();
		$this->_owner = $user;
		return true;
	}

	function getKeywordLists() {
		GLOBAL $db;
		
		$queryStr = "SELECT * FROM tblKeywords WHERE category = " . $this->_id;
		return $db->getResultArray($queryStr);
	}

	function editKeywordList($listID, $keywords) {
		GLOBAL $db;
		
		$queryStr = "UPDATE tblKeywords SET keywords = '$keywords' WHERE id = $listID";
		return $db->getResult($queryStr);
	}

	function addKeywordList($keywords) {
		GLOBAL $db;
		
		$queryStr = "INSERT INTO tblKeywords (category, keywords) VALUES (" . $this->_id . ", '$keywords')";
		return $db->getResult($queryStr);
	}

	function removeKeywordList($listID) {
		GLOBAL $db;
		
		$queryStr = "DELETE FROM tblKeywords WHERE id = $listID";
		return $db->getResult($queryStr);
	}

	function remove()
	{
		GLOBAL $db;
		
		$queryStr = "DELETE FROM tblKeywords WHERE category = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		$queryStr = "DELETE FROM tblKeywordCategories WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr))
			return false;
		
		return true;
	}
}

?>
