<?php
/**
 * Implementation of a term
 *
 * @category   DMS
 * @package    SeedDMS_SQLiteFTS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */


/**
 * Class for managing a term.
 *
 * @category   DMS
 * @package    SeedDMS_SQLiteFTS
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_SQLiteFTS_Term {

	/**
	 * @var string $text
	 * @access public
	 */
	public $text;

	/**
	 * @var string $field
	 * @access public
	 */
	public $field;

	/**
	 * @var integer $occurrence 
	 * @access public
	 */
	public $_occurrence;

	/**
	 *
	 */
	public function __construct($term, $col, $occurrence) { /* {{{ */
		$this->text = $term;
		$fields = array(
			0 => 'title',
			1 => 'comment',
			2 => 'keywords',
			3 => 'category',
			4 => 'owner',
			5 => 'content',
			6 => 'created'
		);
		$this->field = $fields[$col];
		$this->_occurrence = $occurrence;
	} /* }}} */

}
?>
