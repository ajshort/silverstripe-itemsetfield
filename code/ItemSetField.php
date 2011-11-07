<?php

abstract class ItemSetField extends FormField {

	static $item_default_action = null;

	public static $default_options = array(
		'Pageable'       => true,
		'ItemsPerPage'   => 15,
		'FilterCallback' => false,
		'PopupWidth'     => 400,
		'PopupHeight'    => 600
	);

	static $url_handlers = array(
		'item/$ItemID!' => 'handleItem',
		'$Action!' => '$Action',
		'' => 'FieldHolder'
	);

	public function __construct($name, $title = null, $options = array()) {
		parent::__construct($name, $title);

		$defaults = Object::combined_static(
			$this->class, 'default_options', 'ItemSetField'
		);
		$this->options = array_merge($defaults, $options ? $options : array());
	}

	public function getOption($name) {
		if(array_key_exists($name, $this->options)) return $this->options[$name];
	}

	/**
	 * A template-accessable version of {@link ItemSetField::getOption()}.
	 */
	public function Option($name) {
		return $this->getOption($name);
	}

	public function setOption($name, $value) {
		$this->options[$name] = $value;
	}

	/**
	 * Returns all items displayed in the set field.
	 *
	 * @return DataObjectSet
	 */
	public function Items() {
		$query  = $this->getItemsQuery();

		if ($this->getOption('Pageable')) {
			$query->limit(array(
				'start' => $this->getPaginationStart(),
				'limit' => $this->getOption('ItemsPerPage')
			));
		}

		$result = $query->execute();
		$set   = singleton('DataObject')->buildDataObjectSet($result);

		if ($set) {
			$set->parseQueryLimit($query);
		}

		if ($callback = $this->getOption('FilterCallback')) {
			if ($set) foreach ($set as $item) {
				if (call_user_func($callback, $item) === false) {
					$set->remove($item);
				}
			}
		}

		return $set;
	}

	/**
	 * Returns a query that can be used to retrieve the items from the
	 * database. This is used rather than a plain result so that the query can
	 * be manipulated.
	 *
	 * NOTE: This is not marked as abstract as not all classes will implement
	 * it. You can also overload {@link ItemSetField::Items()}.
	 *
	 * @return SQLQuery
	 */
	public function getItemsQuery() {
		throw new Exception(
			'Please implement getItemsQuery on your ItemSetField subclass.'
		);
	}

	/**
	 * @return int
	 */
	public function getPaginationStart() {
		if (!$request = Controller::curr()->getRequest()) {
			return 0;
		}

		$vars = $request->getVars();

		if (isset($vars['ItemSetField'][$this->Name()]['start'])) {
			$start = $vars['ItemSetField'][$this->Name()]['start'];

			if (ctype_digit($start) && (int) $start > 0) {
				return (int) $start;
			}
		}

		return 0;
	}

	/**
	 * Returns a set of the actions performable on individual items.
	 *
	 * @return DataObjectSet
	 */
	public function ItemActions($item) {
		return new DataObjectSet();
	}

	/** The default action when clicking on an item. By default uses the static variable item_default_action */
	function ItemDefaultAction($item) {
		if ($action = $this->stat('item_default_action')) return new ItemSetField_Action($this, $action, $action);
		return null;
	}


	/** The fields needed for a given item. By default stores a hidden input to save ID with. */
	function ItemFields($item) {
		return new FieldSet(
			new HiddenField($this->name.'[]', null, $item->ID)
		);
	}

	public function ItemClass() {
		return 'ItemSetField_Item';
	}

	function ItemForm($item) {
		$class = $this->ItemClass();

		return new $class(
			$this,
			$item,
			$this->ItemFields($item),
			$this->ItemActions($item),
			$this->ItemDefaultAction($item));
	}

	/**
	 * @return DataObjectSet
	 */
	public function ItemForms() {
		$set   = new PaginationContextSet();
		$items = $this->Items();

		if (!$items) return $set;

		foreach ($items as $item) {
			$set->push($this->ItemForm($item));
		}

		$limits = $items->getPageLimits();
		$set->setPageLimits(
			$limits['pageStart'], $limits['pageLength'], $limits['totalSize']
		);

		// Parse the current request URI, and replace the base so it points to
		// this field for pagination.
		$request = $_SERVER['REQUEST_URI'];
		$parts   = parse_url($request);

		$set->setPaginationBaseUrl(Controller::join_links(
			$this->Link(), (isset($parts['query']) ? '?' . $parts['query'] : '')
		));
		$set->setPaginationGetVar(sprintf(
			'ItemSetField[%s][start]', $this->Name()
		));

		return $set;
	}

	function FieldHolder() {
		Requirements::add_i18n_javascript('itemsetfield/javascript/lang');

		$templates = array();
		foreach (array_reverse(ClassInfo::ancestry($this)) as $class) {
			if ($class == 'FormField') break;
			$templates[] = $class;
		}

		return $this->renderWith($templates);
	}

	public function Actions() {
		return new DataObjectSet();
	}

	function handleItem($req) {
		// Comes from search and add request
		if($this->searchClass) {
			$item = DataObject::get_by_id($this->searchClass, $req->param('ItemID'));
		}
		// Come from remove request
		else {
			$this->setOption('Pageable', false);//get full set, not just first page
			$item = $this->Items()->find('ID', $req->param('ItemID'));
		}

		if ($item) return $this->ItemForm($item);
	}

	/**
	 * @return string
	 */
	public function Attributes() {
		$attribs = array();
		$result  = '';

		$attribs['id']    = $this->id();
		$attribs['class'] = 'itemsetfield';
		$attribs['rel']   = $this->Link();

		$attribs['data-popup-width']  = $this->getOption('PopupWidth');
		$attribs['data-popup-height'] = $this->getOption('PopupHeight');

		if ($this->getOption('Sortable')) {
			$attribs['class'] .= ' itemsetfield-sortable';
		}

		foreach ($attribs as $name => $val) {
			$result .= sprintf(' %s="%s"', $name, Convert::raw2att($val));
		}

		return $result;
	}

}

class ItemSetField_Action extends ViewableData {

	protected $itemSet;
	protected $action;
	protected $name;
	protected $confirmed;

	function __construct($itemSet, $action, $name, $confirmed = false) {
		parent::__construct();

		$this->itemSet = $itemSet;
		$this->action = $action;
		$this->name = $name;
		$this->confirmed = $confirmed;
	}

	function setID($id) {
		$this->ID = $id;
	}

	public function Name() {
		return $this->name;
	}

	function Link() {
		return Controller::join_links($this->itemSet->Link(), 'item', $this->ID, $this->action);
	}

	public function Confirmed() {
		return $this->confirmed;
	}

}

class ItemSetField_Item extends RequestHandler {
	function __construct($parent, $item, $fields = null, $actions = null, $defaultAction = null) {
		parent::__construct();

		$this->parent = $parent;
		$this->item = $item;

		$this->setFields($fields);
		$this->setActions($actions);
		$this->setDefaultAction($defaultAction);
	}

	static $url_handlers = array(
		'$Action!' => 'handleAction',
	);

	function handleAction($request) {
		$action = $request->param('Action');

		if (method_exists($this, $action) && $this->checkAccessAction($action)) {
			return $this->$action($request);
		}
		else if ($this->parent->checkAccessAction($action)) {
			return $this->parent->$action($request, $this->item);
		}

		return $this->httpError(403, "Action '$action' isn't allowed on class $this->class");
	}

	function getFields() {
		return $this->fields;
	}

	function setFields($fields) {
		$this->fields = $fields;
	}

	function setActions($actions) {
		$this->actions = $actions;
		if ($this->actions) foreach ($this->actions as $action) $action->setID($this->item->ID);
	}

	function getActions() {
		return $this->actions;
	}

	function setDefaultAction($action) {
		$this->defaultAction = $action;
		if ($this->defaultAction) $this->defaultAction->setID($this->item->ID);
	}

	function getDefaultAction() {
		return $this->defaultAction;
	}

	function forTemplate() {
		return $this->renderWith('ItemSetField_Item');
	}

	public function Label() {
		if (method_exists($this->item, 'Summary')) $summary = $this->item->Summary();
		else {
			$summary = array();
			foreach ($this->item->summaryFields() as $field => $nice) {
				$val = ($this->item->XML_val($field)) ? $this->item->XML_val($field) : $this->item->$field;
				if ($val) $summary[] = $val;
			}
			$summary = implode(', ', $summary);
		}

		return $summary;
	}

	public function Link() {
		return Controller::join_links(
			$this->parent->Link(), 'item', $this->item->ID
		);
	}

}