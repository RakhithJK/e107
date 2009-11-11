<?php
/*
 * e107 website system
 *
 * Copyright (C) 2001-2009 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Release Plugin Administration UI
 *
 * $Source: /cvs_backup/e107_0.8/e107_plugins/release/includes/admin.php,v $
 * $Revision: 1.9 $
 * $Date: 2009-11-11 20:57:33 $
 * $Author: secretr $
*/

//require_once(e_HANDLER.'admin_handler.php'); - autoloaded - see class2.php __autoload()
class plugin_release_admin extends e_admin_dispatcher
{
	/**
	 * Format: 'MODE' => array('controller' =>'CONTROLLER_CLASS'[, 'path' => 'CONTROLLER SCRIPT PATH', 'ui' => 'UI CLASS NAME child of e_admin_ui', 'uipath' => 'UI SCRIPT PATH']);
	 * @var array
	 */
	protected $modes = array(
		'main'		=> array('controller' => 'plugin_release_admin_ui', 'path' => null, 'ui' => 'plugin_release_admin_form_ui', 'uipath' => null)				
	);	

	/**
	 * Format: 'MODE/ACTION' => array('caption' => 'Menu link title'[, 'url' => '{e_PLUGIN}release/admin_config.php', 'perm' => '0']);
	 * Additionally, any valid e_admin_menu() key-value pair could be added to the above array
	 * @var array
	 */
	protected $adminMenu = array(
		'main/list'			=> array('caption'=> 'Manage', 'perm' => '0'),
		'main/create' 		=> array('caption'=> LAN_CREATE, 'perm' => '0'),
		'main/prefs' 		=> array('caption'=> 'Settings', 'perm' => '0'),
		'main/custom'		=> array('caption'=> 'Custom Page', 'perm' => '0')		
	);

	/**
	 * Optional, mode/action aliases, related with 'selected' menu CSS class 
	 * Format: 'MODE/ACTION' => 'MODE ALIAS/ACTION ALIAS';
	 * This will mark active main/list menu item, when current page is main/edit
	 * @var array
	 */
	protected $adminMenuAliases = array(
		'main/edit'	=> 'main/list'				
	);	
	
	/**
	 * Navigation menu title
	 * @var string
	 */
	protected $menuTitle = 'Release Menu';
}

class plugin_release_admin_ui extends e_admin_ui
{
		// required
		protected $pluginTitle = "e107 Release";
		
		/**
		 * plugin name or 'core'
		 * IMPORTANT: should be 'core' for non-plugin areas because this 
		 * value defines what CONFIG will be used. However, I think this should be changed 
		 * very soon (awaiting discussion with Cam) 
		 * Maybe we need something like $prefs['core'], $prefs['release'] ... multiple getConfig support?
		 * 
		 * @var string
		 */
		protected $pluginName = 'release';
		
		/**
		 * DB Table, table alias is supported
		 * Example: 'r.release'
		 * @var string
		 */
		protected $table = "release";
		
		/**
		 * If present this array will be used to build your list query
		 * You can link fileds from $field array with 'table' parameter, which should equal to a key (table) from this array
		 * 'leftField', 'rightField' and 'fields' attributes here are required, the rest is optional
		 * Table alias is supported
		 * Note: 
		 * - 'leftTable' could contain only table alias
		 * - 'leftField' and 'rightField' shouldn't contain table aliases, they will be auto-added
		 * - 'whereJoin' and 'where' should contain table aliases e.g. 'whereJoin' => 'AND u.user_ban=0'
		 * 
		 * @var array [optional] table_name => array join parameters
		 */
		protected $tableJoin = array(
			//'u.user' => array('leftField' => 'comment_author_id', 'rightField' => 'user_id', 'fields' => '*'/*, 'leftTable' => '', 'joinType' => 'LEFT JOIN', 'whereJoin' => '', 'where' => ''*/)
		);
		
		/**
		 * This is only needed if you need to JOIN tables AND don't wanna use $tableJoin
		 * Write your list query without any Order or Limit. 
		 * NOTE: $tableJoin array is recommended join method 
		 * 
		 * @var string [optional]
		 */
		protected $listQry = ""; 
		// 
		
		// optional - required only in case of e.g. tables JOIN. This also could be done with custom model (set it in init())
		// NOT NEEDED ANYMORE!!!
		//protected $editQry = "SELECT * FROM #release WHERE release_id = {ID}";
		
		// required - if no custom model is set in init() (primary id)
		protected $pid = "release_id";
		
		// optional 
		protected $perPage = 20;
		
		// default - true - TODO - move to displaySettings
		protected $batchDelete = true;
		
		// UNDER CONSTRUCTION
		protected $displaySettings = array();
		
		// UNDER CONSTRUCTION
		protected $disallowPages = array('main/create', 'main/prefs');
	    
		//TODO change the release_url type back to URL before release. 
		// required
		/**
		 * (use this as starting point for wiki documentation)
		 * $fields format  (string) $field_name => (array) $attributes
		 * 
		 * $field_name format:
		 * 	'table_alias.field_name' (if JOIN support is needed) OR just 'field_name'
		 * 
		 * $attributes format:
		 * 	- title (string) Human readable field title, constant name will be accpeted as well (multi-language support
		 * 
		 *  - type (string) null (means system), number, text, dropdown, url, image, icon, datestamp, userclass, userclasses, user[_name|_loginname|_login|_customtitle|_email],
		 *    boolean, method, ip
		 *  	full/most recent reference list - e_form::renderTableRow(), e_form::renderElement(), e_admin_form_ui::renderBatchFilter()
		 *  	for list of possible read/writeParms per type see below
		 *  
		 *  - data (string) Data type, one of the following: int, integer, string, str, float, bool, boolean, model, null
		 *    Default is 'str'
		 *    Used only if $dataFields is not set
		 *  	full/most recent reference list - e_admin_model::sanitize(), db::_getFieldValue()
		 *  - primary (boolean) primary field (obsolete, $pid is now used)
		 *  
		 *  - help (string) edit/create table - inline help, constant name will be accpeted as well, optional
		 *  - note (string) edit/create table - text shown below the field title (left column), constant name will be accpeted as well, optional
		 *  
		 *  - validate (boolean|string) any of accepted validation types (see e_validator::$_required_rules), true == 'required'
		 *  - rule (string) condition for chosen above validation type (see e_validator::$_required_rules), not required for all types
		 *  - error (string) Human readable error message (validation failure), constant name will be accpeted as well, optional
		 *  
		 *  - batch (boolean) list table - add current field to batch actions, in use only for boolean, dropdown, datestamp, userclass, method field types
		 *    NOTE: batch may accept string values in the future...
		 *  	full/most recent reference type list - e_admin_form_ui::renderBatchFilter()
		 *  
		 *  - filter (boolean) list table - add current field to filter actions, rest is same as batch
		 *  
		 *  - forced (boolean) list table - forced fields are always shown in list table
		 *  - nolist (boolean) list table - don't show in column choice list
		 *  - noedit (boolean) edit table - don't show in edit mode
		 *  
		 *  - width (string) list table - width e.g '10%', 'auto'
		 *  - thclass (string) list table header - th element class
		 *  - class (string) list table body - td element additional class
		 *  
		 *  - readParms (mixed) parameters used by core routine for showing values of current field. Structure on this attribute
		 *    depends on the current field type (see below). readParams are used mainly by list page
		 *    
		 *  - writeParms (mixed) parameters used by core routine for showing control element(s) of current field. 
		 *    Structure on this attribute depends on the current field type (see below). 
		 *    writeParams are used mainly by edit page, filter (list page), batch (list page)
		 *    
		 * $attributes['type']->$attributes['read/writeParams'] pairs:
		 * - null -> read: n/a
		 * 		  -> write: n/a
		 * 
		 * - user -> read: [optional] 'link' => true - create link to user profile, 'idField' => 'author_id' - tells to renderValue() where to search for user id (used when 'link' is true)
		 * 		  -> write: [optional] 'nameField' => 'comment_author_name' the name of a 'user_name' field; 'currentInit' - use currrent user if no data provided; 'current' - use always current user(editor); '__options' e_form::userpickup() options
		 * 
		 * - number -> read: (array) [optional] 'point' => '.', [optional] 'sep' => ' ', [optional] 'decimals' => 2, [optional] 'pre' => '&euro; ', [optional] 'post' => 'LAN_CURRENCY'
		 * 			-> write: (array) [optional] 'pre' => '&euro; ', [optional] 'post' => 'LAN_CURRENCY', [optional] 'maxlength' => 50, [optional] '__options' => array(...) see e_form class description for __options format
		 * 
		 * - ip		-> read: n/a
		 * 			-> write: [optional] element options array (see e_form class description for __options format)
		 * 
		 * - text -> read: (array) [optional] 'htmltruncate' => 100, [optional] 'truncate' => 100, [optional] 'pre' => '', [optional] 'post' => ' px'
		 * 		  -> write: (array) [optional] 'pre' => '', [optional] 'post' => ' px', [optional] 'maxlength' => 50 (default - 255), [optional] '__options' => array(...) see e_form class description for __options format
		 * 
		 * - textarea 	-> read: (array) 'noparse' => '1' default 0 (disable toHTML text parsing), [optional] 'bb' => '1' (parse bbcode) default 0, [optional] 'parse' => '' modifiers passed to e_parse::toHTML() e.g. 'BODY', [optional] 'htmltruncate' => 100, [optional] 'truncate' => 100, [optional] 'expand' => '[more]' title for expand link, empty - no expand
		 * 		  		-> write: (array) [optional] 'rows' => '' default 15, [optional] 'cols' => '' default 40, [optional] '__options' => array(...) see e_form class description for __options format
		 * 
		 * - bbarea -> read: same as textarea type
		 * 		  	-> write: (array) [optional] 'pre' => '', [optional] 'post' => ' px', [optional] 'maxlength' => 50 (default - 0), [optional] 'size' => [optional] - medium, small, large - default is medium
		 * 
		 * - image -> read: [optional] 'title' => 'SOME_LAN' (default - LAN_PREVIEW), [optional] 'pre' => '{e_PLUGIN}myplug/images/'
		 * 		   -> write: (array) [optional] 'label' => '', [optional] '__options' => array(...) see e_form::imagepicker() for allowed options
		 * 
		 * - icon  -> read: [optional] 'class' => 'S16', [optional] 'pre' => '{e_PLUGIN}myplug/images/'
		 * 		   -> write: (array) [optional] 'label' => '', [optional] 'ajax' => true/false , [optional] '__options' => array(...) see e_form::iconpicker() for allowed options
		 * 
		 * - datestamp  -> read: [optional] 'mask' => 'long'|'short'|strftime() string, default is 'short'
		 * 		   		-> write: (array) [optional] 'label' => '', [optional] 'ajax' => true/false , [optional] '__options' => array(...) see e_form::iconpicker() for allowed options
		 * 
		 * - url	-> read: [optional] 'pre' => '{ePLUGIN}myplug/'|'http://somedomain.com/', 'truncate' => 50 default - no truncate, NOTE: 
		 * 			-> write: 
		 * 
		 * - method -> read: optional, passed to given method (the field name)
		 * 			-> write: optional, passed to given method (the field name)
		 * 
		 * Special attribute types:
		 * - method (string) field name should be method from the current e_admin_form_ui class (or its extension). 
		 * 		Example call: field_name($value, $render_action, $parms) where $value is current value, 
		 * 		$render_action is on of the following: read|write|batch|filter, parms are currently used paramateres ( value of read/writeParms attribute).
		 * 		Return type expected (by render action):
		 * 			- read: list table - formatted value only
		 * 			- write: edit table - form element (control)
		 * 			- batch: either array('title1' => 'value1', 'title2' => 'value2', ..) or array('singleOption' => '<option value="somethig">Title</option>') or rendered option group (string '<optgroup><option>...</option></optgroup>'
		 * 			- filter: same as batch
		 * @var array
		 */
    	protected  $fields = array(
			'checkboxes'				=> array('title'=> '', 					'type' => null,			'data' => null,			'width'=>'5%', 		'thclass' =>'center', 'forced'=> TRUE,  'class'=>'center', 'toggle' => 'e-multiselect'),
			'release_id'				=> array('title'=> ID, 					'type' => 'number',		'data' => 'int',		'width'=>'5%',		'thclass' => '',	'forced'=> TRUE, 'primary'=>TRUE/*, 'noedit'=>TRUE*/), //Primary ID is not editable
            'release_type'	   			=> array('title'=> 'Type', 				'type' => 'method', 	'data' => 'str',		'width'=>'auto',	'thclass' => '', 'batch' => TRUE, 'filter'=>TRUE),
			'release_folder' 			=> array('title'=> 'Folder', 			'type' => 'text', 		'data' => 'str',		'width' => 'auto',	'thclass' => ''),	
			'release_name' 				=> array('title'=> 'Name', 				'type' => 'text', 		'data' => 'str',		'width' => 'auto',	'thclass' => ''),
			'release_version' 			=> array('title'=> 'Version',			'type' => 'text', 		'data' => 'str',		'width' => 'auto',	'thclass' => ''),
			'release_author' 			=> array('title'=> LAN_AUTHOR,			'type' => 'text', 		'data' => 'str',		'width' => 'auto',	'thclass' => 'left'), 
         	'release_authorURL' 		=> array('title'=> LAN_AUTHOR_URL, 		'type' => 'url', 		'data' => 'str',		'width' => 'auto',	'thclass' => 'left'), 
            'release_date' 				=> array('title'=> LAN_DATE, 			'type' => 'datestamp', 	'data' => 'int',		'width' => 'auto',	'thclass' => '', 'readParms' => 'long', 'writeParms' => ''),	 
			'release_compatibility' 	=> array('title'=> 'compatib',			'type' => 'text', 		'data' => 'str',		'width' => '10%',	'thclass' => 'center' ),	 
			'release_url' 				=> array('title'=> 'release_url',		'type' => 'url', 		'data' => 'str',		'width' => '20%',	'thclass' => 'center',	'batch' => TRUE, 'filter'=>TRUE, 'parms' => 'truncate=30', 'validate' => true, 'help' => 'Enter release URL here', 'error' => 'please, ener valid URL'),	 
			'test_list_1'				=> array('title'=> 'test 1',		'type' => 'boolean', 		'data' => 'int',		'width' => '5%',	'thclass' => 'center',	'batch' => TRUE, 'filter'=>TRUE, 'noedit' => true),
			'options' 					=> array('title'=> LAN_OPTIONS, 		'type' => null, 		'data' => null,			'width' => '10%',	'thclass' => 'center last', 'class' => 'center last', 'forced'=>TRUE)
		);
		
		//required - default column user prefs 
		protected $fieldpref = array('checkboxes', 'release_id', 'release_type', 'release_url', 'release_compatibility', 'options');
		
		// FORMAT field_name=>type - optional if fields 'data' attribute is set or if custom model is set in init()
		/*protected $dataFields = array();*/
		
		// optional, could be also set directly from $fields array with attributes 'validate' => true|'rule_name', 'rule' => 'condition_name', 'error' => 'Validation Error message'
		/*protected  $validationRules = array(
			'release_url' => array('required', '', 'Release URL', 'Help text', 'not valid error message')
		);*/
		
		// optional, if $pluginName == 'core', core prefs will be used, else e107::getPluginConfig($pluginName);
		protected $prefs = array( 
			'pref_type'	   				=> array('title'=> 'type', 'type'=>'text', 'data' => 'string', 'validate' => true),
			'pref_folder' 				=> array('title'=> 'folder', 'type' => 'boolean', 'data' => 'integer'),	
			'pref_name' 				=> array('title'=> 'name', 'type' => 'text', 'data' => 'string', 'validate' => 'regex', 'rule' => '#^[\w]+$#i', 'help' => 'allowed characters are a-zA-Z and underscore')		
		);
		
		// optional
		public function init()
		{
		}
}

class plugin_release_admin_form_ui extends e_admin_form_ui
{
	function release_type($curVal,$mode) // not really necessary since we can use 'dropdown' - but just an example of a custom function. 
	{
		if($mode == 'read')
		{
			return $curVal.' (custom!)';
		}
		
		if($mode == 'batch') // Custom Batch List for release_type
		{
			return array('theme'=>"Theme","plugin"=>'Plugin');	
		}
		
		if($mode == 'filter') // Custom Filter List for release_type
		{
			return array('theme'=>"Theme","plugin"=>'Plugin');	
		}
		
		$types = array("theme","plugin");
		$text = "<select class='tbox' name='release_type' >";
		foreach($types as $val)
		{
			$selected = ($curVal == $val) ? "selected='selected'" : "";
			$text .= "<option value='{$val}' {$selected}>".$val."</option>\n";
		}
		$text .= "</select>";
		return $text;
	}
}

/* OBSOLETE - will be removed soon
class releasePlugin extends e_model_interface
{

	function __construct()
	{	
	
		$this->pluginTitle = "e107 Release";
	
		$this->table = "release";
	    
		//TODO change the release_url type back to URL before release. 
		
    	$this->fields = array(
			'checkboxes'				=> array('title'=> '', 					'type' => '',		'width'=>'5%', 		'thclass' =>'center', 'forced'=> TRUE,  'class'=>'center'),
			'release_id'				=> array('title'=> ID, 					'type' => '',		'width'=>'5%',		'thclass' => '',	'forced'=> TRUE, 'primary'=>TRUE),
            'release_type'	   			=> array('title'=> 'Type', 				'type' => 'method', 'width'=>'auto',	'thclass' => '', 'batch' => TRUE, 'filter'=>TRUE),
			'release_folder' 			=> array('title'=> 'Folder', 			'type' => 'text', 	'width' => 'auto',	'thclass' => ''),	
			'release_name' 				=> array('title'=> 'Name', 				'type' => 'text', 	'width' => 'auto',	'thclass' => ''),
			'release_version' 			=> array('title'=> 'Version',			'type' => 'text', 	'width' => 'auto',	'thclass' => ''),
			'release_author' 			=> array('title'=> LAN_AUTHOR,			'type' => 'text', 	'width' => 'auto',	'thclass' => 'left'), 
         	'release_authorURL' 		=> array('title'=> LAN_AUTHOR.'URL', 	'type' => 'url', 	'width' => 'auto',	'thclass' => 'left'), 
            'release_date' 				=> array('title'=> LAN_DATE, 			'type' => 'text', 	'width' => 'auto',	'thclass' => ''),	 
			'release_compatibility' 	=> array('title'=> 'compatib',			'type' => 'text', 	'width' => '10%',	'thclass' => 'center' ),	 
			'release_url' 				=> array('title'=> 'Userclass',				'type' => 'userclass', 	'width' => '10%',	'thclass' => 'center',	'batch' => TRUE, 'filter'=>TRUE),	 
			'options' 					=> array('title'=> LAN_OPTIONS, 		'type' => '', 		'width' => '10%',	'thclass' => 'center last', 'class' => 'center last', 'forced'=>TRUE)
		);
		
		$this->prefs = array( //TODO add option for core or plugin pref. 
		
			'pref_type'	   				=> array('title'=> 'type', 'type'=>'text'),
			'pref_folder' 				=> array('title'=> 'folder', 'type' => 'boolean'),	
			'pref_name' 				=> array('title'=> 'name', 'type' => 'text')		
		);
		
		$this->listQry = "SELECT * FROM #release"; // without any Order or Limit. 
		$this->editQry = "SELECT * FROM #release WHERE release_id = {ID}";		
	
		$this->adminMenu = array(
			'list'		=> array('caption'=>'Release List', 'perm'=>'0'),
			'create' 	=> array('caption'=>LAN_CREATE."/".LAN_EDIT, 'perm'=>'0'),
			'options' 	=> array('caption'=>LAN_OPTIONS, 'perm'=>'0'),
			'custom'	=> array('caption'=>'Custom Page', 'perm'=>0)				
		);		
	}
	
	// Custom View/Form-Element method. ie. Naming should match field/key with type=method.
	
	
	function release_type($curVal,$mode) // not really necessary since we can use 'dropdown' - but just an example of a custom function. 
	{
		if($mode == 'list')
		{
			return $curVal.' (custom!)';
		}
		
		if($mode == 'batch') // Custom Batch List for release_type
		{
			return array('theme'=>"Theme","plugin"=>'Plugin');	
		}
		
		if($mode == 'filter') // Custom Filter List for release_type
		{
			return array('theme'=>"Theme","plugin"=>'Plugin');	
		}
		
		$types = array("theme","plugin");
		$text = "<select class='tbox' name='release_type' >";
		foreach($types as $val)
		{
			$selected = ($curVal == $val) ? "selected='selected'" : "";
			$text .= "<option value='{$val}' {$selected}>".$val."</option>\n";
		}
		$text .= "</select>";
		return $text;
	}
	
	//custom Page = Naming should match $this->adminMenu key + 'Page'. 
	function customPage()
	{
		$ns = e107::getRender();
		$ns->tablerender("Custom","This is a custom Page");
	}

}
*/
//$rp = new releasePlugin;
//$rp->init();